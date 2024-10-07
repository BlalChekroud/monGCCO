<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ImportService extends AbstractController
{
    public function importData(
        Request $request,
        EntityManagerInterface $entityManager,
        $form,
        $repository,
        string $entityClass,
        string $viewTemplate,
        array $headersMap,
        string $uniqueField
    ): Response {
        $form->handleRequest($request);

        // Traitement du fichier CSV lorsque le formulaire est soumis et valide
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $csvFile */
            $csvFile = $form->get('csvFile')->getData();

            if ($csvFile) {
                // Lire le contenu du fichier CSV
                $csvData = file_get_contents($csvFile->getPathname());
                $rows = array_map(function($row) {
                    return str_getcsv($row, ';');
                }, explode("\n", $csvData));

                $headers = array_shift($rows); // Enlever la première ligne qui contient les en-têtes

                // Créer un tableau pour les entrées existantes
                $existingEntries = [];
                foreach ($repository->findAll() as $entry) {
                    // Utilisez le champ unique comme clé
                    $existingEntries[strtolower(trim($entry->{'get' . ucfirst($uniqueField)}()))] = $entry;
                }

                return $this->render($viewTemplate, [
                    'entities' => $repository->findAll(),
                    'form' => $form->createView(),
                    'headers' => $headers,
                    'rows' => $rows,
                    'csvData' => $csvData,
                ]);
            }
        }

        // Traitement de l'importation des données lorsque le formulaire est soumis
        if ($request->isMethod('POST') && $request->request->get('action') === 'import') {
            $csvData = $request->request->get('csvData');
            $rows = array_map(function($row) {
                return str_getcsv($row, ';');
            }, explode("\n", $csvData));

            $headers = array_shift($rows);
            $importedCount = 0; // Compteur des entités importées

            // Utiliser un tableau pour suivre les valeurs uniques traitées
            $uniqueValuesSet = [];

            foreach ($rows as $row) {
                // Ignorer les lignes vides
                if (empty(array_filter($row))) {
                    continue;
                }

                // Vérifiez si la ligne a le même nombre de colonnes que les en-têtes
                if (count($row) !== count($headers)) {
                    $this->addFlash('error', 'Ligne incorrecte : ' . implode(', ', $row));
                    continue; // Ignorez cette ligne
                }

                $data = array_combine($headers, $row);

                if ($data === false) {
                    continue; // Ignorez les lignes où array_combine échoue
                }

                // Identifier l'entrée via le champ unique
                $uniqueValue = strtolower(trim($data[$uniqueField] ?? '')); // Obtenez la valeur du champ unique, normalisée

                // Vérifiez si l'entité existe déjà ou si elle a déjà été traitée
                if ($uniqueValue !== '' && (isset($existingEntries[$uniqueValue]) || in_array($uniqueValue, $uniqueValuesSet))) {
                    // Doublon trouvé
                    $this->addFlash('warning', 'Doublon trouvé pour : ' . $uniqueValue);
                    continue; // Ignorer cette entrée
                }

                // Vérifiez si l'entité existe déjà pour mise à jour
                if (isset($existingEntries[$uniqueValue])) {
                    
                    // Créer un tableau pour les entrées existantes
                    $existingEntries = [];
                    // Mise à jour de l'entrée existante
                    $entity = $existingEntries[$uniqueValue];
                    // Logique de mise à jour des propriétés
                    foreach ($headersMap as $header => $property) {
                        if (isset($data[$header]) && !empty($data[$header])) {
                            $setter = 'set' . ucfirst($property);
                            if (method_exists($entity, $setter)) {
                                $entity->$setter($data[$header]);
                            }
                        }
                    }
                } else {
                    // Créer une nouvelle instance de l'entité
                    $entity = new $entityClass();
                    foreach ($headersMap as $header => $property) {
                        if (isset($data[$header]) && !empty($data[$header])) {
                            $setter = 'set' . ucfirst($property);
                            if (method_exists($entity, $setter)) {
                                $entity->$setter($data[$header]);
                            }
                        }
                    }
                }

                // Définir la date de création
                $entity->setCreatedAt(new \DateTimeImmutable());

                // Persist the entity
                $entityManager->persist($entity);
                $importedCount++; // Incrémentez le compteur

                // Ajouter la valeur unique à l'ensemble
                $uniqueValuesSet[] = $uniqueValue;
            }

            // Valider toutes les entités persistées
            $entityManager->flush();

            $this->addFlash('success', $importedCount . ' entités ont été importées avec succès.');

            return $this->redirectToRoute('app_' . strtolower((new \ReflectionClass($entityClass))->getShortName()) . '_index');
        }

        // Rendre la vue avec les entités existantes et le formulaire
        return $this->render($viewTemplate, [
            'entities' => $repository->findAll(),
            'form' => $form->createView(),
        ]);
    }
}
