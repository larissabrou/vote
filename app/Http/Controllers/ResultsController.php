<?php

namespace App\Http\Controllers;

use App\Models\Election;
use App\Models\Voter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Résultats en temps réel.
 * Accessibles uniquement par lien unique (token votant) ou par le créateur de l'élection (admin).
 */
class ResultsController extends Controller
{
    /**
     * Résultats pour un votant : accès par lien unique (token).
     */
    public function showByToken(string $token)
    {
        $voter = Voter::where('token', $token)->first();

        if (!$voter) {
            return redirect()->route('accueil')->with('error', 'Lien de résultats invalide ou expiré.');
        }

        $election = $voter->election;
        $election->load(['candidates', 'voters']);
        $election->updateStatus();

        return view('results.live', [
            'election' => $election,
            'apiUrl' => route('election.resultats.api.token', ['token' => $token]),
        ]);
    }

    /**
     * API des résultats pour un votant (token).
     */
    public function apiByToken(string $token)
    {
        $voter = Voter::where('token', $token)->first();

        if (!$voter) {
            return response()->json(['error' => 'Lien invalide.'], 403);
        }

        $election = $voter->election;
        return $this->buildApiResponse($election);
    }

    /**
     * Résultats pour le créateur de l'élection (admin, propriétaire uniquement).
     */
    public function show(Election $election)
    {
        if (!Auth::guard('web')->check() || $election->user_id !== Auth::id()) {
            return redirect()->route('accueil')->with('error', 'Accès non autorisé.');
        }

        $election->load(['candidates', 'voters']);
        $election->updateStatus();

        return view('results.live', [
            'election' => $election,
            'apiUrl' => route('elections.resultats.api', $election),
        ]);
    }

    /**
     * API des résultats pour le créateur (admin).
     */
    public function api(Election $election)
    {
        if (!Auth::guard('web')->check() || $election->user_id !== Auth::id()) {
            return response()->json(['error' => 'Non autorisé.'], 403);
        }

        return $this->buildApiResponse($election);
    }

    /**
     * Construit la réponse JSON des résultats (partagée entre api et apiByToken).
     */
    private function buildApiResponse(Election $election)
    {
        $election->load(['candidates.votes', 'voters.positions', 'votes']);
        $election->updateStatus();

        $total_voices = $election->voters()
            ->where('has_voted', true)
            ->sum('vote_count');

        $voterIdsAyantVote = $election->voters()->where('has_voted', true)->pluck('id');
        $voixParPosition = \Illuminate\Support\Facades\DB::table('voter_positions')
            ->whereIn('voter_id', $voterIdsAyantVote)
            ->groupBy('position')
            ->selectRaw('position, sum(vote_count) as total')
            ->pluck('total', 'position')
            ->all();

        $candidates = $election->candidates->map(function ($candidate) use ($voixParPosition) {
            $voteCount = $candidate->vote_count;
            $position = $candidate->position;
            $voixPosition = $voixParPosition[$position] ?? 0;
            $percentage = $voixPosition > 0 ? ($voteCount / $voixPosition) * 100 : 0;

            return [
                'id' => $candidate->id,
                'name' => $candidate->full_name,
                'position' => $candidate->position,
                'photo' => $candidate->photo,
                'votes' => $voteCount,
                'total_voices' => (int) $voixPosition,
                'percentage' => round($percentage, 2),
            ];
        });

        $blank_votes = $election->votes()
            ->whereNull('candidate_id')
            ->where('is_null', false)
            ->count();

        $voted_count = $election->voted_count ?? 0;
        $null_votes = $election->null_votes ?? 0;
        $valid_votes_raw = $election->votes()
            ->where('is_null', false)
            ->whereNotNull('candidate_id')
            ->count();
        $valid_votes = min($valid_votes_raw, (int) $total_voices);

        return response()->json([
            'election_id' => $election->id,
            'election_title' => $election->title,
            'total_voters' => $election->total_voters,
            'voted_count' => $voted_count,
            'null_votes' => $null_votes,
            'blank_votes' => $blank_votes,
            'total_voices' => $total_voices,
            'valid_votes' => $valid_votes,
            'candidates' => $candidates->toArray(),
            'progress' => $election->total_voters > 0
                ? round(($voted_count / $election->total_voters) * 100, 2)
                : 0,
        ]);
    }
}
