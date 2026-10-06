<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;

class VotantsTemplateExport implements FromArray
{
    public function array(): array
    {
        return [
            ['email', 'nom', 'prenom', 'nombre_voix'],
            ['exemple@email.com', 'Dupont', 'Jean', 1],
            ['autre@domaine.com', 'Martin', 'Marie', 2],
        ];
    }
}
