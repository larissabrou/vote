<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Payment extends Model
{
    const AMOUNT_ELECTION_FCFA = 200; // 5000 en production, 200 pour tests
    const STATUS_PENDING = 'pending';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';

    const METHOD_ORANGE_MONEY = 'orange_money';
    const METHOD_MOOV = 'moov';
    const METHOD_MTN_MOMO = 'mtn_momo';
    const METHOD_WAVE = 'wave';

    public static function getPaymentMethods(): array
    {
        return [
            self::METHOD_ORANGE_MONEY => [
                'label' => 'Orange Money',
                'image' => 'images/orange-money.png',
                'placeholder' => 'Ex: 07 XX XXX XX XX',
                'syntax' => 'Composez #144*82# pour générer un code OTP. Entrez le code OTP reçu par SMS ci-dessous.',
                'validation_syntax' => 'Validez avec le code OTP reçu par SMS (composez #144*82# si besoin).',
                'fields' => ['phone', 'otp'],
            ],
            self::METHOD_MOOV => [
                'label' => 'Moov Money',
                'image' => 'images/moov-money.png',
                'placeholder' => 'Ex: 01 XX XXX XX XX',
                'syntax' => 'Composez *155*15# pour valider le paiement.',
                'validation_syntax' => '*155*15#',
                'fields' => ['phone'],
            ],
            self::METHOD_MTN_MOMO => [
                'label' => 'MTN MoMo',
                'image' => 'images/mtn-money.png',
                'placeholder' => 'Ex: 05 XX XXX XX XX',
                'syntax' => 'Composez *133# puis choisissez l\'option 1 pour valider le paiement.',
                'validation_syntax' => '*133# option 1',
                'fields' => ['phone'],
            ],
            self::METHOD_WAVE => [
                'label' => 'Wave',
                'image' => 'images/wave.png',
                'placeholder' => 'Ex: 07 XX XXX XX XX',
                'syntax' => 'Ouvrez l\'application Wave, choisissez Paiement et entrez le numéro ci-dessous.',
                'fields' => ['phone'],
            ],
        ];
    }

    protected $fillable = [
        'amount',
        'currency',
        'status',
        'phone',
        'otp',
        'reference',
        'provider_order_id',
        'payment_method',
        'election_id',
        'paid_at',
    ];

    protected $casts = [
        'paid_at' => 'datetime',
    ];

    public function election(): BelongsTo
    {
        return $this->belongsTo(Election::class);
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public static function generateReference(): string
    {
        return 'VOTE-' . strtoupper(Str::random(8)) . '-' . time();
    }
}
