<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;
use App\Form\ImportCsvType;
use App\Service\FileUploader;

use Monolog\DateTimeImmutable;
use DateTime;
use Vich\UploaderBundle\Templating\Helper\UploaderHelper;
use App\Form\CoverageType;
use App\Form\BirdLifeTaxTreatType;
use App\Form\IucnRedListCategoryType;
use Symfony\Component\Security\Http\Attribute\IsGranted;

use App\Entity\BirdSpecies;
use App\Form\BirdSpeciesType;
use App\Repository\BirdSpeciesRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/bird/species')]
#[IsGranted('ROLE_COLLECTOR')]
class BirdSpeciesController extends AbstractController
{
    #[Route('/', name: 'app_bird_species_index', methods: ['GET'])]
    public function index(Request $request, BirdSpeciesRepository $birdSpeciesRepository, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ImportCsvType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $csvFile */
            $csvFile = $form->get('csvFile')->getData();

            if ($csvFile) {
                try {
                    $this->processCsv($csvFile, $entityManager);
                    $this->addFlash('success', 'Les données ont été importées avec succès.');
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Une erreur s\'est produite lors de l\'importation du fichier CSV : ' . $e->getMessage());
                }

                return $this->redirectToRoute('app_bird_species_index');
            }
        }
        return $this->render('bird_species/index.html.twig', [
            'bird_species' => $birdSpeciesRepository->findAll(),
            'form' => $form->createView(),
        ]);
    }

    #[Route('/import', name: 'app_bird_species_import', methods: ['POST'])]
    public function import(Request $request, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ImportCsvType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $csvFile = $form->get('csvFile')->getData();
                $extension = $csvFile->getClientOriginalExtension();

                if (!in_array($extension, ['csv', 'txt'])) {
                    $this->addFlash('error', 'Veuillez charger un fichier CSV ou TXT valide.');
                    return $this->redirectToRoute('app_bird_species_import');
                }

                if ($csvFile) {
                    try {
                        $this->processCsv($csvFile, $entityManager);
                        $this->addFlash('success', 'Les données ont été importées avec succès.');
                        return $this->redirectToRoute('app_bird_species_index');
                    } catch (\Exception $e) {
                        $this->addFlash('error', 'Une erreur s\'est produite lors de l\'importation du fichier CSV : ' . $e->getMessage());
                    }
                }
            } else {
                $this->addFlash('error', 'Le fichier CSV contient des erreurs de validation.');
            }
        }

        return $this->render('bird_species/import.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    private function processCsv(UploadedFile $csvFile, EntityManagerInterface $entityManager)
    {
        $csvData = file_get_contents($csvFile->getPathname());
        $rows = array_map(function($row) {
            return str_getcsv($row, ';');
        }, explode("\n", $csvData));
        
        $headers = array_shift($rows);
    
        foreach ($rows as $row) {
            if (count($row) !== count($headers)) {
                continue; // Skip rows where the number of columns does not match the number of headers
            }
    
            $data = array_combine($headers, $row);
    
            if ($data === false) {
                continue; // Skip rows where array_combine fails
            }
    
            $birdSpecy = new BirdSpecies();
            $birdSpecy->setScientificName($data['Scientific name'] ?? null);
            $birdSpecy->setFrenchName($data['....'] ?? null);
            $birdSpecy->setWispeciescode($data['....'] ?? null);
            $birdSpecy->setAuthority($data['Authority'] ?? null);
            $birdSpecy->setCreatedAt(DateTimeImmutable::createFromMutable(new DateTime()));
            $birdSpecy->setCommonName($data['Common name'] ?? null);
            $birdSpecy->setCommonNameAlt($data['Subfamily'] ?? null);
            $birdSpecy->setSynonyms($data['Synonyms'] ?? null);
            // $birdSpecy->setAlternativeCommonNames($data['Alternative common names'] ?? null);
            $birdSpecy->setTaxonomicSources($data['Taxonomic source'] ?? null);
            $birdSpecy->setSisRecId($data['SISRecID'] ?? null);
            $birdSpecy->setSpcRecId($data['SpcRecID'] ?? null);
            $birdSpecy->setSubsppId($data['SubsppID'] ?? null);
            $birdSpecy->setBirdFamily($data['Family name'] ?? null);
            $birdSpecy->setCoverage($data['Coverage'] ?? null);
            $birdSpecy->setBirdLifeTaxTreat($data['BirdLife taxonomic treatment'] ?? null);
            $birdSpecy->setIucnRedListCategory($data['IUCN 2022 Red List category'] ?? null);
    
            $entityManager->persist($birdSpecy);
        }
    
        $entityManager->flush();
    }

    #[Route('/preview', name: 'app_bird_species_preview', methods: ['POST'])]
    public function preview(Request $request): Response
    {
        $form = $this->createForm(ImportCsvType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $csvFile */
            $csvFile = $form->get('csvFile')->getData();
            if ($csvFile) {
                $csvData = file_get_contents($csvFile->getPathname());
                $rows = array_map(function($row) {
                    return str_getcsv($row, ';');
                }, explode("\n", $csvData));
                
                $headers = array_shift($rows);

                return $this->render('bird_species/preview.html.twig', [
                    'headers' => $headers,
                    'rows' => $rows,
                    'csvData' => $csvData, // Pass the raw CSV data to the template
                ]);
            }
        }

        return $this->redirectToRoute('app_bird_species_index');
    }

    #[Route('/new', name: 'app_bird_species_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $birdSpecy = new BirdSpecies();
        $form = $this->createForm(BirdSpeciesType::class, $birdSpecy);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $imageFile */
            $imageFile = $form->get('imageFile')->getData();

            if ($imageFile) {
                $birdSpecy->setImageFile($imageFile);
            }
            $birdSpecy->setCreatedAt(DateTimeImmutable::createFromMutable(new DateTime()));
            $entityManager->persist($birdSpecy);
            $entityManager->flush();
            $this->addFlash('success', "Espèse d'oiseau a bien été crée");

            return $this->redirectToRoute('app_bird_species_index', [], Response::HTTP_SEE_OTHER);
        }

        $coverageForm = $this->createForm(CoverageType::class);
        $birdLifeTaxTreatForm = $this->createForm(BirdLifeTaxTreatType::class);
        $iucnRedListCategoryForm = $this->createForm(IucnRedListCategoryType::class);

        return $this->render('bird_species/new.html.twig', [
            'bird_specy' => $birdSpecy,
            'form' => $form,
            'coverageForm' => $coverageForm->createView(),
            'birdLifeTaxTreatForm' => $birdLifeTaxTreatForm->createView(),
            'iucnRedListCategoryForm' => $iucnRedListCategoryForm->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_bird_species_show', methods: ['GET'])]
    public function show(BirdSpecies $birdSpecy): Response
    {
        return $this->render('bird_species/show.html.twig', [
            'bird_specy' => $birdSpecy,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_bird_species_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, BirdSpecies $birdSpecy, EntityManagerInterface $entityManager): Response
    {
        
        $form = $this->createForm(BirdSpeciesType::class, $birdSpecy);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $imageFile */
            $imageFile = $form->get('imageFile')->getData();

            if ($imageFile) {
                $birdSpecy->setImageFile($imageFile);
            }
            $birdSpecy->setUpdatedAt(DateTimeImmutable::createFromMutable(new DateTime()));
            $entityManager->flush();
            $this->addFlash('success', "L'espèse a bien été modifié");

            return $this->redirectToRoute('app_bird_species_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('bird_species/edit.html.twig', [
            'bird_specy' => $birdSpecy,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_bird_species_delete', methods: ['POST'])]
    public function delete(Request $request, BirdSpecies $birdSpecy, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$birdSpecy->getId(), $request->getPayload()->get('_token'))) {
            $entityManager->remove($birdSpecy);
            $entityManager->flush();
            $this->addFlash('success', "L'espèse a bien été supprimée");
        }

        return $this->redirectToRoute('app_bird_species_index', [], Response::HTTP_SEE_OTHER);
    }
}
