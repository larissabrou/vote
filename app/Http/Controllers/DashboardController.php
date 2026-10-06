<?php

namespace App\Http\Controllers;

use App\Models\Election;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function show()
    {
        if (!Auth::guard('web')->check()) {
            return redirect()->route('administration.connexion')->with('error', 'Accès non autorisé.');
        }

        $elections = Election::with(['candidates', 'voters'])
            ->where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->get();

        return view('dashboard.show', compact('elections'));
    }

    public function api($electionId)
    {
        if (!Auth::guard('web')->check()) {
            return response()->json(['error' => 'Accès non autorisé.'], 403);
        }

        $election = Election::with(['candidates.votes', 'voters.positions', 'votes'])->findOrFail($electionId);
        if ($election->user_id !== Auth::id()) {
            return response()->json(['error' => 'Accès non autorisé.'], 403);
        }
        $election->updateStatus();
        
        // Votes effectifs = total des voix (somme du vote_count des votants qui ont voté)
        $total_voices = $election->voters()
            ->where('has_voted', true)
            ->sum('vote_count');

        // Voix par position = total des voix pour cette position par les votants ayant effectivement voté (même règle que la page show)
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

        // Bulletins blancs = voix non utilisées (candidate_id null, is_null false)
        $blank_votes = $election->votes()
            ->whereNull('candidate_id')
            ->where('is_null', false)
            ->count();

        $voted_count = $election->voted_count ?? 0;
        $null_votes = $election->null_votes ?? 0;
        // Suffrages exprimés = voix attribuées à des candidats, plafonné au total de voix (ne peut pas dépasser le nombre de voix)
        $valid_votes_raw = $election->votes()
            ->where('is_null', false)
            ->whereNotNull('candidate_id')
            ->count();
        $valid_votes = min($valid_votes_raw, (int) $total_voices);

        return response()->json([
            'election_id' => $election->id,
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

