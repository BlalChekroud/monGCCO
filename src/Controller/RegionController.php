<?php

namespace App\Controller;

use App\Entity\Country;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use App\Entity\Region;
use App\Form\ImportCsvType;
use App\Form\RegionType;
use App\Repository\RegionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/region')]
class RegionController extends AbstractController
{
    #[Route('/', name: 'app_region_index', methods: ['GET', 'POST'])]
    public function index(RegionRepository $regionRepository, Request $request, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ImportCsvType::class);
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $csvFile */
            $csvFile = $form->get('csvFile')->getData();
            
            if ($csvFile) {
                $csvData = file_get_contents($csvFile->getPathname());
                
                // Convertir l'encodage si nécessaire
                if (!mb_check_encoding($csvData, 'UTF-8')) {
                    $csvData = mb_convert_encoding($csvData, 'UTF-8', 'ISO-8859-1'); // Changez 'ISO-8859-1' si besoin
                }

                // Vérifiez si la conversion a réussi
                if (!mb_check_encoding($csvData, 'UTF-8')) {
                    $this->addFlash('error', 'Le fichier CSV contient des caractères non valides. Veuillez vérifier l\'encodage du fichier.');
                    return $this->redirectToRoute('app_region_index');
                }
                
                $rows = array_map(function($row) {
                    return str_getcsv($row, ';'); // Assurez-vous que le séparateur correspond au fichier CSV
                }, explode("\n", $csvData));
                
                $headers = array_shift($rows); // Enlever la première ligne qui contient les en-têtes
    
                return $this->render('region/index.html.twig', [
                    'regions' => $regionRepository->findAll(),
                    'form' => $form->createView(),
                    'headers' => $headers,
                    'rows' => $rows,
                    'csvData' => $csvData,
                ]);
            }
        }
    
        if ($request->isMethod('POST') && $request->request->get('action') === 'import') {
            $csvData = $request->request->get('csvData');
            $rows = array_map(function($row) {
                return str_getcsv($row, ';');
            }, explode("\n", $csvData));
    
            $headers = array_shift($rows);
    
            $importedCount = 0; // Compteur de régions importées
            $processedRegions = []; // Tableau pour suivre les régions déjà traitées
            $invalidCount = 0; // Compteur de lignes non importées
            $invalidRows = []; // Tableau pour stocker les numéros des lignes invalides
            $processedCountries = [];
    
            foreach ($rows as $lineNumber => $row) {
                // Ignorer les lignes vides
                if (empty(array_filter($row))) {
                    continue;
                }
    
                // Vérifiez si la ligne a le même nombre de colonnes que les en-têtes
                if (count($row) !== count($headers)) {
                    $invalidRows[] = $lineNumber + 2; // Ajouter 2 pour compenser les décalages d'index et la ligne d'en-tête
                    $invalidCount++;
                    continue; // Ignorez cette ligne
                }
        
                $data = array_combine($headers, $row);
        
                if ($data === false) {
                    $invalidRows[] = $lineNumber + 2;
                    $invalidCount++;
                    continue; // Ignorez les lignes où array_combine échoue
                }
    
                $regionName = $data['region'];
                $regionCode = $data['regionCode'];
                $countryName = $data['country'] ?? null;
                $iso2 = $data['iso2'] ?? null;
    
                if (empty($regionName) || empty($regionCode) || empty($countryName)) {
                    $invalidRows[] = $lineNumber + 2;
                    $invalidCount++;
                    continue;
                }
    
                // Vérifier si la région a déjà été traitée dans ce fichier
                if (isset($processedRegions[$regionName])) {
                    $invalidRows[] = $lineNumber + 2;
                    $invalidCount++;
                    continue; // Si oui, ignorer cette entrée
                }
    
                // Vérifier si la région existe déjà dans la base de données
                $existingRegion = $regionRepository->findOneBy(['name' => $regionName]);
                if ($existingRegion) {
                    $processedRegions[$regionName] = true;
                    $invalidRows[] = $lineNumber + 2;
                    $invalidCount++;
                    continue; // Si oui, ignorer cette entrée
                }
    
                // Récupérer ou créer le pays associé
                $countryRepository = $entityManager->getRepository(Country::class);
                $existingCountry = $countryRepository->findOneBy(['name' => $countryName]);
    
                // Vérifier si le pays a déjà été traité
                if (!$existingCountry) {
                    // Si le pays n'existe pas, créez un nouveau pays
                    if (!isset($processedCountries[$countryName])) {
                        $country = new Country();
                        $country->setName($countryName);
                        $country->setIso2($iso2);
                        $country->setCreatedAt(new \DateTimeImmutable());

                        $entityManager->persist($country);
                        $processedCountries[$countryName] = $country; // Marquer comme traité
                        $existingCountry = $country; // Réassigner pour utiliser l'objet persisté
                    } else {
                        $existingCountry = $processedCountries[$countryName]; // Utiliser le pays déjà traité
                    }
                } else {
                    $processedCountries[$countryName] = $existingCountry; // Marquer comme traité
                }
                
                // Créez et persistez une nouvelle région
                $region = new Region();
                $region->setName($regionName);
                $region->setRegionCode($regionCode);
                $region->setCreatedAt(new \DateTimeImmutable());
    
                // Associer la région au pays
                $region->setCountry($existingCountry); // Utilisez setCountry au lieu de addCountry
    
                $entityManager->persist($region);
                $importedCount++; // Incrémentez le compteur
    
                // Marquer cette région comme traitée
                $processedRegions[$regionName] = true;
            }
    
            $entityManager->flush();
    
            $this->addFlash('success', "$importedCount régions ont été importées avec succès.  $invalidCount lignes n'ont pas pu être importées.");
    
            if ($invalidCount > 0) {
                $this->addFlash('error', "$invalidCount lignes n'ont pas pu être importées. Numéros des lignes : " . implode(', ', $invalidRows));
            }
            
            return $this->redirectToRoute('app_region_index');
        }
    
        return $this->render('region/index.html.twig', [
            'regions' => $regionRepository->findAll(),
            'form' => $form->createView(),
        ]);
    }
    
    
    #[Route('/new', name: 'app_region_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $region = new Region();
        $form = $this->createForm(RegionType::class, $region);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $region->setCreatedAt(new \DateTimeImmutable());
                $entityManager->persist($region);
                $entityManager->flush();
                $this->addFlash('success', "La région a bien été créée.");
    
                return $this->redirectToRoute('app_region_index', [], Response::HTTP_SEE_OTHER);
            } else {
                $this->addFlash('error','Une erreur s\'est produite lors de la création de la région.');
            }
        }

        return $this->render('region/new.html.twig', [
            'region' => $region,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_region_show', methods: ['GET'])]
    public function show(Region $region): Response
    {
        return $this->render('region/show.html.twig', [
            'region' => $region,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_region_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Region $region, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(RegionType::class, $region);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $region->setUpdatedAt(new \DateTimeImmutable());
                $entityManager->flush();
                $this->addFlash('success', "La région a bien été modifiée.");
    
                return $this->redirectToRoute('app_region_index', [], Response::HTTP_SEE_OTHER);
            } else {
                $this->addFlash('error','Une erreur s\'est produite lors de la création de la région.');
            }
        }

        return $this->render('region/edit.html.twig', [
            'region' => $region,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_region_delete', methods: ['POST'])]
    public function delete(Request $request, Region $region, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$region->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($region);
            $entityManager->flush();
            $this->addFlash('success', "La région a bien été supprimée.");
        }

        return $this->redirectToRoute('app_region_index', [], Response::HTTP_SEE_OTHER);
    }
}
