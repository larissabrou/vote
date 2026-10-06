<?php

namespace App\Services;

use App\Models\Voter;
use Illuminate\Support\Facades\Mail;

class EmailService
{
    public function sendVotingLink(Voter $voter)
    {
        $election = $voter->election;
        $voteUrl = route('voter.authentifier', ['token' => $voter->token]);
        $resultsUrl = route('election.resultats.token', ['token' => $voter->token]);

        Mail::send('emails.voting-link', [
            'voter' => $voter,
            'election' => $election,
            'voteUrl' => $voteUrl,
            'resultsUrl' => $resultsUrl,
        ], function ($message) use ($voter, $election) {
            $message->to($voter->email, $voter->full_name)
                    ->subject("Lien de vote - {$election->title}");
        });
    }
}

