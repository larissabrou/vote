<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Election extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'logo',
        'election_date',
        'end_date',
        'start_time',
        'end_time',
        'status',
        'voters_source',
        'emails_sent_at',
    ];

    protected $casts = [
        'election_date' => 'date',
        'end_date' => 'date',
        'start_time' => 'string',
        'end_time' => 'string',
        'voters_source' => 'string',
        'emails_sent_at' => 'datetime',
    ];

    /** Date de fin effective (pour élection sur plusieurs jours). Utilise getAttribute pour rester compatible si la colonne end_date n'existe pas encore (migration non exécutée). */
    public function getEffectiveEndDateAttribute(): \Carbon\Carbon
    {
        $endDate = $this->getAttribute('end_date');
        return $endDate ? \Carbon\Carbon::parse($endDate)->copy() : $this->election_date->copy();
    }

    /** Normalise une heure stockée (H:i ou H:i:s) en H:i:s pour le parsing. */
    protected static function normalizeTimeString($time): string
    {
        if ($time instanceof \Carbon\Carbon || $time instanceof \DateTime) {
            return $time->format('H:i:s');
        }
        $time = (string) $time;
        if (preg_match('/\d{4}-\d{2}-\d{2}\s+(\d{2}):(\d{2})(?::(\d{2}))?/', $time, $m)) {
            return sprintf('%02d:%02d:%02d', (int) $m[1], (int) $m[2], isset($m[3]) ? (int) $m[3] : 0);
        }
        if (preg_match('/^(\d{1,2}):(\d{2})(?::(\d{2}))?/', trim($time), $m)) {
            return sprintf('%02d:%02d:%02d', (int) $m[1], (int) $m[2], isset($m[3]) ? (int) $m[3] : 0);
        }
        return '00:00:00';
    }

    /** Libellé des dates pour l'affichage (ex: "22/02/2026" ou "22/02/2026 - 25/02/2026"). Compatible si la colonne end_date n'existe pas encore. */
    public function getDateRangeDisplayAttribute(): string
    {
        $start = $this->election_date->format('d/m/Y');
        $endDate = $this->getAttribute('end_date');
        if ($endDate) {
            $end = $endDate instanceof \Carbon\Carbon ? $endDate : \Carbon\Carbon::parse($endDate);
            if ($end->format('Y-m-d') !== $this->election_date->format('Y-m-d')) {
                return $start . ' - ' . $end->format('d/m/Y');
            }
        }
        return $start;
    }

    /** Libellé date + heure pour affichage (multi-jours : "Du DD/MM 08:00 au DD/MM 18:00", un seul jour : "DD/MM/YYYY 08:00 - 18:00"). */
    public function getDateTimeRangeDisplayAttribute(): string
    {
        $startDate = $this->election_date;
        $endDate = $this->effective_end_date;
        $startTime = self::normalizeTimeString($this->getRawOriginal('start_time') ?? $this->start_time);
        $endTime = self::normalizeTimeString($this->getRawOriginal('end_time') ?? $this->end_time);
        $startTimeShort = preg_replace('/:00$/', '', $startTime);
        $endTimeShort = preg_replace('/:00$/', '', $endTime);

        if ($endDate->format('Y-m-d') !== $startDate->format('Y-m-d')) {
            return sprintf('Du %s %s au %s %s', $startDate->format('d/m/Y'), $startTimeShort, $endDate->format('d/m/Y'), $endTimeShort);
        }
        return $startDate->format('d/m/Y') . ' ' . $startTimeShort . ' - ' . $endTimeShort;
    }

    /** URL publique du logo (stocké dans public/election_assets/logos ou anciennement storage). */
    public function getLogoUrlAttribute(): ?string
    {
        if (!$this->logo) {
            return null;
        }
        if (file_exists(public_path($this->logo))) {
            return asset($this->logo);
        }
        if (Storage::disk('public')->exists($this->logo)) {
            return Storage::url($this->logo);
        }
        return asset($this->logo);
    }

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** L'élection est activée (payée) : les votants ont reçu les emails et le vote est autorisé. */
    public function isActivated(): bool
    {
        // Source de vérité: un paiement complété active l'élection.
        // emails_sent_at est conservé pour compatibilité avec les anciennes données.
        return $this->payments()
            ->where('status', Payment::STATUS_COMPLETED)
            ->exists() || $this->emails_sent_at !== null;
    }

    /** True si l'élection peut être modifiée : pas encore payée OU (payée mais aucun vote). Une élection passée mais non activée peut être modifiée (pour corriger la date puis payer). */
    public function isEditable(): bool
    {
        if (!$this->isActivated()) {
            return true;
        }
        if ($this->hasEnded()) {
            return false;
        }
        return $this->voted_count === 0;
    }

    /** True si la date ou l'heure de fin de l'élection est passée (on ne peut plus payer sans modifier). */
    public function hasEnded(): bool
    {
        $this->updateStatus();
        return $this->status === 'completed';
    }

    public function candidates(): HasMany
    {
        return $this->hasMany(Candidate::class);
    }

    public function voters(): HasMany
    {
        return $this->hasMany(Voter::class);
    }

    public function votes(): HasMany
    {
        return $this->hasMany(Vote::class);
    }

    public function payment(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Payment::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function updateStatus(): void
    {
        $now = now();

        $startTime = self::normalizeTimeString($this->getRawOriginal('start_time') ?? $this->start_time);
        $endTime = self::normalizeTimeString($this->getRawOriginal('end_time') ?? $this->end_time);

        // Période : du premier jour à l'heure de début jusqu'au dernier jour à l'heure de fin
        $start = \Carbon\Carbon::parse($this->election_date->format('Y-m-d') . ' ' . $startTime);
        $end = \Carbon\Carbon::parse($this->effective_end_date->format('Y-m-d') . ' ' . $endTime);

        if ($now < $start) {
            if ($this->status !== 'pending') {
                $this->status = 'pending';
                $this->save();
            }
        } elseif ($now >= $start && $now <= $end) {
            if ($this->status !== 'active') {
                $this->status = 'active';
                $this->save();
            }
        } else {
            if ($this->status !== 'completed') {
                $this->status = 'completed';
                $this->save();
            }
        }
    }

    public function isValidVotingTime(): bool
    {
        $this->updateStatus();

        if ($this->status !== 'active') {
            return false;
        }

        $now = now();
        $startTime = self::normalizeTimeString($this->getRawOriginal('start_time') ?? $this->start_time);
        $endTime = self::normalizeTimeString($this->getRawOriginal('end_time') ?? $this->end_time);

        $start = \Carbon\Carbon::parse($this->election_date->format('Y-m-d') . ' ' . $startTime);
        $end = \Carbon\Carbon::parse($this->effective_end_date->format('Y-m-d') . ' ' . $endTime);

        return $now >= $start && $now <= $end;
    }

    public function getTotalVotersAttribute(): int
    {
        return $this->voters()->count();
    }

    public function getVotedCountAttribute(): int
    {
        return $this->voters()->where('has_voted', true)->count();
    }

    public function getNullVotesAttribute(): int
    {
        return $this->votes()->where('is_null', true)->count();
    }
}

