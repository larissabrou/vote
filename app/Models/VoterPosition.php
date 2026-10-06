<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VoterPosition extends Model
{
    use HasFactory;

    protected $fillable = [
        'voter_id',
        'position',
        'vote_count',
    ];

    public function voter(): BelongsTo
    {
        return $this->belongsTo(Voter::class);
    }
}
