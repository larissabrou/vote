<?php

namespace App\Services;

use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Validator;

class ExcelImportService
{
    public function import($file)
    {
        try {
            return $this->doImport($file);
        } catch (\Exception $e) {
            // Réutiliser les messages déjà explicites du service
            throw $e;
        } catch (\Throwable $e) {
            throw new \Exception(
                'Le fichier Excel ne correspond pas au modèle attendu. ' .
                'Téléchargez le modèle « Modèle votants » sur la page et respectez exactement les colonnes : email, nom, prenom, nombre_voix (première ligne = en-têtes). ' .
                'Erreur technique : ' . $e->getMessage()
            );
        }
    }

    /**
     * Effectue l'import après vérifications.
     */
    private function doImport($file)
    {
        // Vérifier que l'extension zip est disponible
        if (!class_exists('ZipArchive')) {
            throw new \Exception(
                'L\'extension PHP "zip" n\'est pas activée. ' .
                'Veuillez activer l\'extension zip dans php.ini et redémarrer Apache. ' .
                'Consultez ACTIVER_ZIP_URGENT.md pour les instructions.'
            );
        }

        $data = Excel::toArray([], $file);
        
        if (empty($data) || empty($data[0])) {
            throw new \Exception('Le fichier Excel est vide.');
        }

        $rows = $data[0];
        
        // Trouver la ligne des en-têtes (chercher une ligne contenant "email")
        $headerRowIndex = null;
        $possibleEmailHeaders = ['email', 'e-mail', 'mail', 'courriel'];
        
        for ($i = 0; $i < min(10, count($rows)); $i++) {
            $row = array_map(function($cell) {
                return trim(mb_strtolower($this->removeAccents($cell ?? ''), 'UTF-8'));
            }, $rows[$i]);
            
            foreach ($possibleEmailHeaders as $emailHeader) {
                if (in_array($emailHeader, $row)) {
                    $headerRowIndex = $i;
                    break 2;
                }
            }
        }
        
        if ($headerRowIndex === null) {
            throw new \Exception(
                'Impossible de trouver la ligne des en-têtes dans le fichier Excel. ' .
                'Assurez-vous que votre fichier contient une colonne "email" dans la première ligne de données.'
            );
        }
        
        // Nettoyer et normaliser les en-têtes
        $headers = array_map(function($header) {
            // Supprimer les espaces, convertir en minuscules, supprimer les accents
            $header = trim($header ?? '');
            $header = mb_strtolower($header, 'UTF-8');
            $header = $this->removeAccents($header);
            // Remplacer les espaces multiples par un seul espace
            $header = preg_replace('/\s+/', ' ', $header);
            return trim($header);
        }, $rows[$headerRowIndex]);
        
        // Mapping flexible des colonnes
        $columnMapping = [
            'email' => ['email', 'e-mail', 'mail', 'courriel'],
            'nom' => ['nom', 'name', 'lastname', 'last name', 'nom de famille', 'surname'],
            'prenom' => ['prenom', 'firstname', 'first name', 'prenom', 'prénom'],
            'nombre_voix' => ['nombre_voix', 'nombre de voix', 'nombre voix', 'voix', 'vote count', 'votes', 'nb_voix', 'nb voix']
        ];
        
        // Trouver les index des colonnes
        $columnIndexes = [];
        foreach ($columnMapping as $key => $possibleNames) {
            $found = false;
            foreach ($possibleNames as $possibleName) {
                $index = array_search($possibleName, $headers);
                if ($index !== false) {
                    $columnIndexes[$key] = $index;
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                throw new \Exception(
                    "Colonne requise non trouvée: '$key'. " .
                    "Colonnes trouvées: " . implode(', ', $headers) . ". " .
                    "Assurez-vous que votre fichier contient les colonnes: email, nom, prenom, nombre_voix"
                );
            }
        }

        $voters = [];
        
        // Commencer après la ligne des en-têtes
        for ($i = $headerRowIndex + 1; $i < count($rows); $i++) {
            $row = $rows[$i];
            
            if (empty(array_filter($row))) {
                continue; // Ignorer les lignes vides
            }

            // Extraire les données en utilisant les index trouvés
            $voter = [
                'email' => trim($row[$columnIndexes['email']] ?? ''),
                'nom' => trim($row[$columnIndexes['nom']] ?? ''),
                'prenom' => trim($row[$columnIndexes['prenom']] ?? ''),
                'nombre_voix' => $row[$columnIndexes['nombre_voix']] ?? 1,
            ];

            // Convertir nombre_voix en entier
            $voter['nombre_voix'] = is_numeric($voter['nombre_voix']) ? (int)$voter['nombre_voix'] : 1;

            // Valider les données
            $validator = Validator::make($voter, [
                'email' => 'required|email',
                'nom' => 'required|string',
                'prenom' => 'required|string',
                'nombre_voix' => 'required|integer|min:1',
            ]);

            if ($validator->fails()) {
                continue; // Ignorer les lignes invalides
            }

            $voters[] = $voter;
        }

        if (empty($voters)) {
            throw new \Exception('Aucun votant valide trouvé dans le fichier Excel. Vérifiez que les données sont correctement formatées.');
        }

        return $voters;
    }

    /**
     * Supprimer les accents d'une chaîne
     */
    private function removeAccents($string)
    {
        $accents = [
            'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'å' => 'a',
            'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e',
            'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i',
            'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o',
            'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ü' => 'u',
            'ý' => 'y', 'ÿ' => 'y',
            'ç' => 'c', 'ñ' => 'n',
        ];
        
        return strtr($string, $accents);
    }
}

