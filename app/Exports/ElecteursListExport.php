<?php

namespace App\Exports;

use App\Models\Election;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;

class ElecteursListExport implements FromArray, WithTitle
{
    public function __construct(
        private Election $election
    ) {}

    public function array(): array
    {
        $header = [
            'email',
            'nom',
            'prenom',
            'nombre_voix',
            'a_vote',
            'date_vote',
        ];

        $rows = [$header];

        foreach ($this->election->voters()->orderBy('last_name')->orderBy('first_name')->orderBy('id')->get() as $voter) {
            $rows[] = [
                $voter->email,
                $voter->last_name,
                $voter->first_name,
                (int) ($voter->vote_count ?? 1),
                $voter->has_voted ? 'oui' : 'non',
                $voter->voted_at ? $voter->voted_at->format('d/m/Y H:i') : '',
            ];
        }

        return $rows;
    }

    public function title(): string
    {
        return mb_substr('Electeurs ' . $this->election->id, 0, 31);
    }
}
