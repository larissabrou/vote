<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Voter extends Model
{
    use HasFactory;

    protected $fillable = [
        'election_id',
        'email',
        'first_name',
        'last_name',
        'vote_count', // Conservé pour compatibilité, mais sera calculé depuis voter_positions
        'token',
        'email_sent_at',
        'email_error',
        'has_voted',
        'voted_at',
        'signature',
    ];

    protected $casts = [
        'has_voted' => 'boolean',
        'voted_at' => 'datetime',
        'email_sent_at' => 'datetime',
    ];

    /** Le lien de vote a bien été envoyé à ce votant. */
    public function hasReceivedEmail(): bool
    {
        return $this->email_sent_at !== null;
    }

    /** L'envoi d'email a échoué (adresse invalide ou erreur SMTP). */
    public function hasEmailError(): bool
    {
        return $this->email_error !== null && $this->email_error !== '';
    }

    public static function generateToken(): string
    {
        return Str::random(64);
    }

    public function election(): BelongsTo
    {
        return $this->belongsTo(Election::class);
    }

    public function votes(): HasMany
    {
        return $this->hasMany(Vote::class);
    }

    public function positions(): HasMany
    {
        return $this->hasMany(VoterPosition::class);
    }

    /**
     * Obtenir le nombre de voix pour une position donnée
     */
    public function getVoteCountForPosition(string $position): int
    {
        $voterPosition = $this->positions()->where('position', $position)->first();
        return $voterPosition ? $voterPosition->vote_count : 0;
    }

    /**
     * Obtenir le nombre total de voix (somme de toutes les positions)
     */
    public function getTotalVoteCountAttribute(): int
    {
        return $this->positions()->sum('vote_count');
    }

    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }
}

