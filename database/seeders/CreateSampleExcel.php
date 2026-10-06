<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class CreateSampleExcel extends Seeder
{
    public function run()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // En-têtes
        $sheet->setCellValue('A1', 'email');
        $sheet->setCellValue('B1', 'nom');
        $sheet->setCellValue('C1', 'prenom');
        $sheet->setCellValue('D1', 'nombre_voix');

        // Données d'exemple
        $voters = [
            ['jean.dupont@example.com', 'Dupont', 'Jean', 1],
            ['marie.martin@example.com', 'Martin', 'Marie', 2],
            ['pierre.bernard@example.com', 'Bernard', 'Pierre', 1],
            ['sophie.durand@example.com', 'Durand', 'Sophie', 3],
            ['lucas.moreau@example.com', 'Moreau', 'Lucas', 1],
            ['emma.petit@example.com', 'Petit', 'Emma', 2],
            ['thomas.laurent@example.com', 'Laurent', 'Thomas', 1],
            ['laura.simon@example.com', 'Simon', 'Laura', 1],
            ['antoine.michel@example.com', 'Michel', 'Antoine', 2],
            ['julie.lefebvre@example.com', 'Lefebvre', 'Julie', 1],
        ];

        // Remplir les données
        $row = 2;
        foreach ($voters as $voter) {
            $sheet->setCellValue('A' . $row, $voter[0]);
            $sheet->setCellValue('B' . $row, $voter[1]);
            $sheet->setCellValue('C' . $row, $voter[2]);
            $sheet->setCellValue('D' . $row, $voter[3]);
            $row++;
        }

        // Style des en-têtes
        $sheet->getStyle('A1:D1')->getFont()->setBold(true);
        $sheet->getStyle('A1:D1')->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFE0E0E0');

        // Ajuster la largeur des colonnes
        $sheet->getColumnDimension('A')->setWidth(30);
        $sheet->getColumnDimension('B')->setWidth(20);
        $sheet->getColumnDimension('C')->setWidth(20);
        $sheet->getColumnDimension('D')->setWidth(15);

        // Sauvegarder le fichier
        $writer = new Xlsx($spreadsheet);
        $filePath = public_path('modele_votants.xlsx');
        $writer->save($filePath);

        $this->command->info('Fichier Excel créé : ' . $filePath);
    }
}

