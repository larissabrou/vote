<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class CreateExcelTemplate extends Command
{
    protected $signature = 'excel:template';
    protected $description = 'Créer un fichier Excel modèle pour l\'import des votants';

    public function handle()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // En-têtes directement sur la première ligne (sans titre)
        $sheet->setCellValue('A1', 'email');
        $sheet->setCellValue('B1', 'nom');
        $sheet->setCellValue('C1', 'prenom');
        $sheet->setCellValue('D1', 'nombre_voix');

        // Style des en-têtes
        $sheet->getStyle('A1:D1')->getFont()->setBold(true);
        $sheet->getStyle('A1:D1')->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FF4472C4');
        $sheet->getStyle('A1:D1')->getFont()->getColor()->setARGB('FFFFFFFF');
        $sheet->getStyle('A1:D1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Données d'exemple
        $voters = [
            ['jean.dupont@example.com', 'Dupont', 'Jean', 1],
            ['marie.martin@example.com', 'Martin', 'Marie', 2],
            ['pierre.bernard@example.com', 'Bernard', 'Pierre', 1],
            ['sophie.durand@example.com', 'Durand', 'Sophie', 3],
            ['lucas.moreau@example.com', 'Moreau', 'Lucas', 1],
        ];

        // Remplir les données d'exemple
        $row = 2;
        foreach ($voters as $voter) {
            $sheet->setCellValue('A' . $row, $voter[0]);
            $sheet->setCellValue('B' . $row, $voter[1]);
            $sheet->setCellValue('C' . $row, $voter[2]);
            $sheet->setCellValue('D' . $row, $voter[3]);
            $row++;
        }

        // Ajuster la largeur des colonnes
        $sheet->getColumnDimension('A')->setWidth(35);
        $sheet->getColumnDimension('B')->setWidth(20);
        $sheet->getColumnDimension('C')->setWidth(20);
        $sheet->getColumnDimension('D')->setWidth(15);

        // Centrer la colonne nombre_voix
        $sheet->getStyle('D2:D' . ($row - 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Ajouter des bordures
        $sheet->getStyle('A1:D' . ($row - 1))->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['argb' => 'FF000000'],
                ],
            ],
        ]);

        // Sauvegarder le fichier
        $filePath = public_path('modele_votants.xlsx');
        $writer = new Xlsx($spreadsheet);
        $writer->save($filePath);

        $this->info('✅ Fichier Excel modèle créé avec succès !');
        $this->info('📁 Emplacement : ' . $filePath);
        $this->newLine();
        $this->info('📋 Format requis :');
        $this->line('   - Colonne A : email (adresse email valide)');
        $this->line('   - Colonne B : nom (nom de famille)');
        $this->line('   - Colonne C : prenom (prénom)');
        $this->line('   - Colonne D : nombre_voix (nombre entier >= 1)');
        $this->newLine();
        $this->info('💡 Le fichier contient des exemples de données que vous pouvez modifier ou supprimer.');

        return 0;
    }
}
