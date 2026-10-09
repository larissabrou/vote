<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Candidate extends Model
{
    use HasFactory;

    protected $fillable = [
        'election_id',
        'photo',
        'first_name',
        'last_name',
        'position',
    ];

    public function election(): BelongsTo
    {
        return $this->belongsTo(Election::class);
    }

    public function votes(): HasMany
    {
        return $this->hasMany(Vote::class)->where('is_null', false);
    }

    /** URL publique de la photo (public/election_assets/photos ou anciennement storage). */
    public function getPhotoUrlAttribute(): ?string
    {
        if (!$this->photo) {
            return null;
        }
        if (str_starts_with($this->photo, 'http://') || str_starts_with($this->photo, 'https://')) {
            return $this->photo;
        }
        if (file_exists(public_path($this->photo))) {
            return asset($this->photo);
        }
        if (Storage::disk('public')->exists($this->photo)) {
            return Storage::url($this->photo);
        }

        return asset($this->photo);
    }

    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function getVoteCountAttribute(): int
    {
        return $this->votes()->count();
    }
}

