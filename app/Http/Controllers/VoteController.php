<?php

namespace App\Http\Controllers;

use App\Models\Election;
use App\Models\Voter;
use App\Models\Vote;
use App\Models\VoterPosition;
use App\Models\Candidate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;

class VoteController extends Controller
{
    public function authenticate($token)
    {
        $voter = Voter::where('token', $token)->first();

        if (!$voter) {
            return redirect()->route('accueil')->with('error', 'Token invalide.');
        }

        if ($voter->has_voted) {
            return redirect()->route('accueil')->with('error', 'Vous avez déjà voté.');
        }

        $election = $voter->election;

        if (!$election->isActivated()) {
            return redirect()->route('accueil')->with('error', 'Cette élection n\'est pas encore activée (paiement en attente).');
        }

        if (!$election->isValidVotingTime()) {
            return redirect()->route('accueil')->with('error', 'Le vote n\'est pas encore ouvert ou est déjà fermé.');
        }

        session(['voter_token' => $token, 'voter_id' => $voter->id]);

        return redirect()->route('voter.voir', $election->id);
    }

    public function show($electionId)
    {
        $token = session('voter_token');
        
        if (!$token) {
            return redirect()->route('accueil')->with('error', 'Accès non autorisé.');
        }

        $voter = Voter::with('positions')->where('token', $token)->firstOrFail();
        $election = Election::with('candidates')->findOrFail($electionId);

        if ($voter->election_id !== $election->id) {
            return redirect()->route('accueil')->with('error', 'Accès non autorisé.');
        }

        if ($voter->has_voted) {
            return redirect()->route('accueil')->with('error', 'Vous avez déjà voté.');
        }

        if (!$election->isActivated()) {
            return redirect()->route('accueil')->with('error', 'Cette élection n\'est pas encore activée.');
        }

        if (!$election->isValidVotingTime()) {
            return redirect()->route('accueil')->with('error', 'Le vote n\'est pas encore ouvert ou est déjà fermé.');
        }

        // Vérifier que le votant a des positions (pour les anciens votants créés avant la migration)
        if ($voter->positions->isEmpty()) {
            // Chaque position reçoit le même nombre de voix (ex: 3 voix → 3 voix par position)
            $positions = $election->candidates->pluck('position')->unique();
            $voteCountPerPosition = $voter->vote_count ?? 1;
            foreach ($positions as $position) {
                $voterPosition = new VoterPosition();
                $voterPosition->voter_id = $voter->id;
                $voterPosition->position = $position;
                $voterPosition->vote_count = $voteCountPerPosition;
                $voterPosition->save();
            }
            // Recharger les positions
            $voter->load('positions');
        } else {
            // Corriger les positions existantes : chaque position doit avoir vote_count = voter.vote_count (3 → 3 par position)
            $expectedCount = (int) ($voter->vote_count ?? 1);
            foreach ($voter->positions as $voterPosition) {
                if ((int) $voterPosition->vote_count !== $expectedCount) {
                    $voterPosition->vote_count = $expectedCount;
                    $voterPosition->save();
                }
            }
            $voter->load('positions');
        }

        // Grouper les candidats par position et ajouter le nombre de voix disponibles
        $candidatesByPosition = $election->candidates->groupBy('position')->map(function ($candidates, $position) use ($voter) {
            $voteCount = $voter->getVoteCountForPosition($position);
            return [
                'candidates' => $candidates,
                'vote_count' => $voteCount,
            ];
        });

        return view('vote.show', compact('election', 'voter', 'candidatesByPosition'));
    }

    public function submit(Request $request, $electionId)
    {
        $token = session('voter_token');
        
        if (!$token) {
            return response()->json(['error' => 'Accès non autorisé.'], 403);
        }

        $voter = Voter::where('token', $token)->firstOrFail();
        $election = Election::findOrFail($electionId);

        if ($voter->election_id !== $election->id) {
            return response()->json(['error' => 'Accès non autorisé.'], 403);
        }

        if ($voter->has_voted) {
            return response()->json(['error' => 'Vous avez déjà voté.'], 400);
        }

        if (!$election->isActivated()) {
            return response()->json(['error' => 'Cette élection n\'est pas encore activée.'], 400);
        }

        if (!$election->isValidVotingTime()) {
            return response()->json(['error' => 'Le vote n\'est pas encore ouvert ou est déjà fermé.'], 400);
        }

        $request->validate([
            'votes' => 'required|array|min:1',
            'votes.*.candidate_id' => 'nullable|exists:candidates,id',
            'votes.*.is_null' => 'boolean',
            'votes.*.position' => 'nullable|string',
            'signature' => 'required|string',
        ]);

        // Charger les positions du votant
        $voter->load('positions');
        
        // Grouper les votes par position (candidats, bulletins nuls, bulletins blancs) et vérifier les limites
        $votesByPosition = [];
        $nullVotesByPosition = [];
        $blankVotesByPosition = [];

        foreach ($request->votes as $voteData) {
            if ($voteData['is_null'] ?? false) {
                $position = $voteData['position'] ?? null;
                if ($position) {
                    $nullVotesByPosition[$position] = ($nullVotesByPosition[$position] ?? 0) + 1;
                }
                continue;
            }

            $candidateId = $voteData['candidate_id'] ?? null;
            if ($candidateId) {
                $candidate = Candidate::find($candidateId);
                if ($candidate && $candidate->election_id === $election->id) {
                    $position = $candidate->position;
                    $votesByPosition[$position] = ($votesByPosition[$position] ?? 0) + 1;
                }
                continue;
            }

            // Bulletin blanc : candidate_id null, is_null false (voix non utilisée)
            $position = $voteData['position'] ?? null;
            if ($position) {
                $blankVotesByPosition[$position] = ($blankVotesByPosition[$position] ?? 0) + 1;
            }
        }

        // Vérifier que le total (candidats + bulletins nuls + bulletins blancs) par position ne dépasse pas la limite
        $allPositions = array_unique(array_merge(
            array_keys($votesByPosition),
            array_keys($nullVotesByPosition),
            array_keys($blankVotesByPosition)
        ));
        foreach ($allPositions as $position) {
            $candidateVotes = $votesByPosition[$position] ?? 0;
            $nullVotes = $nullVotesByPosition[$position] ?? 0;
            $blankVotes = $blankVotesByPosition[$position] ?? 0;
            $totalVotes = $candidateVotes + $nullVotes + $blankVotes;
            $maxVotes = $voter->getVoteCountForPosition($position);

            if ($totalVotes > $maxVotes) {
                return response()->json([
                    'error' => "Vous avez dépassé votre nombre de voix autorisé pour la position '{$position}'. Maximum: {$maxVotes}, Vous avez voté: {$totalVotes} (candidats: {$candidateVotes}, bulletins nuls: {$nullVotes}, bulletins blancs: {$blankVotes})."
                ], 400);
            }
        }

        // Enregistrer les votes
        foreach ($request->votes as $voteData) {
            $vote = new Vote();
            $vote->election_id = $election->id;
            $vote->voter_id = $voter->id;
            $vote->candidate_id = $voteData['candidate_id'] ?? null;
            $vote->is_null = $voteData['is_null'] ?? false;
            $vote->save();
        }

        // Marquer le votant comme ayant voté et enregistrer la signature
        $voter->has_voted = true;
        $voter->voted_at = now();
        $voter->signature = $request->signature;
        $voter->save();

        // Publier la mise à jour via Redis pour Socket.io (si Redis est disponible)
        try {
            $this->broadcastVoteUpdate($election->id);
        } catch (\Exception $e) {
            // Redis n'est pas disponible, continuer sans broadcast
            Log::warning('Redis non disponible pour le broadcast: ' . $e->getMessage());
        }

        session()->forget(['voter_token', 'voter_id']);

        return response()->json([
            'success' => true,
            'message' => 'Vote enregistré avec succès!',
            'redirect_url' => route('election.resultats.token', ['token' => $voter->token]),
        ]);
    }

    protected function broadcastVoteUpdate($electionId)
    {
        $election = Election::with(['candidates.votes', 'voters', 'votes'])->find($electionId);
        
        $progress = $election->total_voters > 0 
            ? round(($election->voted_count / $election->total_voters) * 100, 2) 
            : 0;
        
        // Voix par position = total des voix pour cette position (tous les inscrits) — varie par élection
        $voterIdsInscrits = $election->voters()->pluck('id');
        $voixParPosition = \Illuminate\Support\Facades\DB::table('voter_positions')
            ->whereIn('voter_id', $voterIdsInscrits)
            ->groupBy('position')
            ->selectRaw('position, sum(vote_count) as total')
            ->pluck('total', 'position')
            ->all();

        // Votes effectifs = total des voix (somme du vote_count des votants qui ont voté)
        $total_voices = $election->voters()->where('has_voted', true)->sum('vote_count');

        // Bulletins blancs = voix non utilisées (candidate_id null, is_null false)
        $blank_votes = $election->votes()
            ->whereNull('candidate_id')
            ->where('is_null', false)
            ->count();

        // Suffrages exprimés = voix attribuées à des candidats, plafonné au total de voix (ne peut pas dépasser le nombre de voix)
        $valid_votes_raw = $election->votes()
            ->where('is_null', false)
            ->whereNotNull('candidate_id')
            ->count();
        $valid_votes = min($valid_votes_raw, (int) $total_voices);

        $data = [
            'election_id' => $electionId,
            'total_voters' => $election->total_voters,
            'voted_count' => $election->voted_count,
            'null_votes' => $election->null_votes,
            'blank_votes' => $blank_votes,
            'total_voices' => $total_voices,
            'valid_votes' => $valid_votes,
            'progress' => $progress,
            'candidates' => $election->candidates->map(function ($candidate) use ($voixParPosition) {
                $voteCount = $candidate->vote_count;
                $position = $candidate->position;
                $voixPosition = $voixParPosition[$position] ?? 0;
                $percentage = $voixPosition > 0 ? ($voteCount / $voixPosition) * 100 : 0;
                
                return [
                    'id' => $candidate->id,
                    'name' => $candidate->full_name,
                    'position' => $position,
                    'votes' => $voteCount,
                    'percentage' => round($percentage, 2),
                ];
            }),
        ];

        Redis::publish('vote-updates', json_encode($data));
    }
}

