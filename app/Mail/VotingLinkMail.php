<?php

namespace App\Mail;

use App\Models\Voter;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class VotingLinkMail extends Mailable
{
    use Queueable, SerializesModels;

    public $voter;
    public $election;
    public $voteUrl;
    public $resultsUrl;

    public function __construct(Voter $voter, $voteUrl, $resultsUrl = null)
    {
        $this->voter = $voter;
        $this->election = $voter->election;
        $this->voteUrl = $voteUrl;
        $this->resultsUrl = $resultsUrl ?? route('election.resultats.token', ['token' => $voter->token]);
    }

    public function build()
    {
        return $this->view('emails.voting-link')
                    ->subject("Lien de vote - {$this->election->title}");
    }
}

