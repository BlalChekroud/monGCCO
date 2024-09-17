<?php

namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class CsvImportService
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * Import a CSV file to a specific entity.
     *
     * @param UploadedFile $csvFile
     * @param string $entityClass
     * @param array $mapping
     * @param callable $findCallback
     * @param callable $createEntityCallback
     * @param array $uniqueFields
     * @return int Number of imported records
     */
    public function importCsv(
        UploadedFile $csvFile,
        string $entityClass,
        array $mapping,
        callable $findCallback,
        callable $createEntityCallback,
        array $uniqueFields = []
    ): int {
        $csvData = file_get_contents($csvFile->getPathname());
        $rows = array_map(function($row) {
            return str_getcsv($row, ';'); // Utiliser le bon séparateur de colonnes
        }, explode("\n", $csvData));

        $headers = array_shift($rows);
        $importedCount = 0;
        $processedItems = [];

        foreach ($rows as $row) {
            if (empty(array_filter($row)) || count($row) !== count($headers)) {
                continue; // Ignorez les lignes vides ou incorrectes
            }

            $data = array_combine($headers, $row);

            if ($data === false) {
                continue;
            }

            // Préparation des valeurs uniques
            $uniqueValues = [];
            foreach ($uniqueFields as $field) {
                $uniqueValues[$field] = $data[$field] ?? null;
            }

            if ($findCallback($uniqueValues)) {
                continue; // Ignorer si l'entité existe déjà
            }

            if (isset($processedItems[$uniqueValues[$uniqueFields[0]]])) {
                continue; // Ignorez les doublons dans le fichier
            }

            $entity = $createEntityCallback($data);
            $this->entityManager->persist($entity);
            $importedCount++;
            $processedItems[$uniqueValues[$uniqueFields[0]]] = true;
        }

        $this->entityManager->flush();
        return $importedCount;
    }
}
