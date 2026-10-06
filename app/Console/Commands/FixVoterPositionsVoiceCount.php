<?php

namespace App\Console\Commands;

use App\Models\Voter;
use App\Models\VoterPosition;
use Illuminate\Console\Command;

class FixVoterPositionsVoiceCount extends Command
{
    protected $signature = 'voters:fix-positions-voice-count {--election= : ID de l\'élection (optionnel, sinon toutes)}';
    protected $description = 'Met à jour voter_positions : chaque position reçoit le même nombre de voix que le votant (vote_count), au lieu d\'une répartition.';

    public function handle(): int
    {
        $electionId = $this->option('election');

        $query = Voter::with('positions');
        if ($electionId) {
            $query->where('election_id', $electionId);
        }
        $voters = $query->get();

        if ($voters->isEmpty()) {
            $this->warn('Aucun votant trouvé.');
            return self::FAILURE;
        }

        $updated = 0;
        foreach ($voters as $voter) {
            $voteCount = (int) ($voter->vote_count ?? 1);
            foreach ($voter->positions as $position) {
                if ((int) $position->vote_count !== $voteCount) {
                    $position->vote_count = $voteCount;
                    $position->save();
                    $updated++;
                }
            }
        }

        $this->info("Mise à jour effectuée : {$updated} ligne(s) voter_positions corrigée(s) pour " . $voters->count() . " votant(s).");
        return self::SUCCESS;
    }
}
