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
use App\Entity\BirdFamily;
use App\Entity\Coverage;
use App\Entity\BirdLifeTaxTreat;
use App\Entity\IucnRedListCategory;
use App\Form\BirdSpeciesType;
use App\Repository\BirdSpeciesRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/bird/species')]
#[IsGranted('ROLE_COLLECTOR', message: 'Vous n\'avez pas l\'accès.')]
class BirdSpeciesController extends AbstractController
{
    #[Route('/', name: 'app_bird_species_index', methods: ['GET'])]
    public function index(Request $request, BirdSpeciesRepository $birdSpeciesRepository, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ImportCsvType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
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
            } else {
                // Log the errors for debugging
                foreach ($form->getErrors(true) as $error) {
                    error_log($error->getMessage());
                }
                $this->addFlash('error','Une erreur s\'est produite lors de l\'importation');
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

    // private function processCsv(UploadedFile $csvFile, EntityManagerInterface $entityManager)
    // {
    //     $csvData = file_get_contents($csvFile->getPathname());
    //     $rows = array_map(function($row) {
    //         return str_getcsv($row, ';');
    //     }, explode("\n", $csvData));
        
    //     $headers = array_shift($rows);
    
    //     foreach ($rows as $row) {
    //         if (count($row) !== count($headers)) {
    //             continue; // Skip rows where the number of columns does not match the number of headers
    //         }
    
    //         $data = array_combine($headers, $row);
    
    //         if ($data === false) {
    //             continue; // Skip rows where array_combine fails
    //         }
    
    //         // Fetch or create the Birdfamily entity
    //         $familyName = $data['Family name'] ?? null;
    //         $family = $data['Family'] ?? null;
    
    //         if ($familyName && $family) {
    //             $familyRepository = $entityManager->getRepository(BirdFamily::class);
    //             $birdFamily = $familyRepository->findOneBy(['familyName' => $familyName, 'family' => $family]);
    
    //             if (!$birdFamily) {
    //                 $birdFamily = new BirdFamily();
    //                 $birdFamily->setOrdre($data['Ordre'] ?? null);
    //                 $birdFamily->setFamilyName($data['Family name'] ?? null);
    //                 $birdFamily->setFamily($data['Family'] ?? null);
    //                 $birdFamily->setSubFamily($data['Subfamily'] ?? null);
    //                 $birdFamily->setTribe($data['Tribe'] ?? null);
    //                 $birdFamily->setCreatedAt(DateTimeImmutable::createFromMutable(new DateTime()));
            
    //                 $entityManager->persist($birdFamily);
    //             }
    //         }

    //         // Fetch or create the Coverage entity
    //         $coveragName = $data['Coverage'] ?? null;
    
    //         if ($coveragName) {
    //             $coverageRepository = $entityManager->getRepository(Coverage::class);
    //             $coverage = $coverageRepository->findOneBy(['label' => $coveragName]);
    
    //             if (!$coverage) {
    //                 $coverage = new Coverage();
    //                 $coverage->setLabel($data['Coverage'] ?? '');
    //                 $coverage->setCreatedAt(DateTimeImmutable::createFromMutable(new DateTime()));
    
    //                 $entityManager->persist($coverage);
    //             }
    //         }

    //         // Fetch or create the BirdLifeTaxTreat entity
    //         $birdLifeTaxTreatName = $data['BirdLife taxonomic treatment'] ?? null;
    //         if ($birdLifeTaxTreatName) {
    //             $birdLifeTaxTreatRepository = $entityManager->getRepository(BirdLifeTaxTreat::class);
    //             $birdLifeTaxTreat = $birdLifeTaxTreatRepository->findOneBy(['label' => $birdLifeTaxTreatName]);
    
    //             if (!$birdLifeTaxTreat) {
    //                 $birdLifeTaxTreat = new BirdLifeTaxTreat();
    //                 $birdLifeTaxTreat->setLabel($data['BirdLife taxonomic treatment'] ?? '');
    //                 $birdLifeTaxTreat->setCreatedAt(DateTimeImmutable::createFromMutable(new DateTime()));
    
    //                 $entityManager->persist($birdLifeTaxTreat);
    //             }
    //         }

    //         // Fetch or create the IucnRedListCategory entity
    //         $iucnRedListCategoryName = $data['2022 IUCN Red List category'] ?? null;
    
    //         if ($iucnRedListCategoryName) {
    //             $iucnRedListCategoryRepository = $entityManager->getRepository(IucnRedListCategory::class);
    //             $iucnRedListCategory = $iucnRedListCategoryRepository->findOneBy(['label' => $iucnRedListCategoryName]);
    
    //             if (!$iucnRedListCategory) {
    //                 $iucnRedListCategory = new IucnRedListCategory();
    //                 $iucnRedListCategory->setLabel($data['2022 IUCN Red List category'] ?? '');
    //                 $iucnRedListCategory->setCreatedAt(DateTimeImmutable::createFromMutable(new DateTime()));
    
    //                 $entityManager->persist($iucnRedListCategory);
    //             }
    //         }

    //         // Check if the BirdSpecies already exists
    //         $scientificName = $data['Scientific name'] ?? null;
    //         if ($scientificName) {
    //             $birdSpecyRepository = $entityManager->getRepository(BirdSpecies::class);
    //             $birdSpecy = $birdSpecyRepository->findOneBy(['scientificName' => $scientificName]);

    //             if (!$scientificName){
    //                 // Create the BirdSpecies entity if it does not exist
    //                 $birdSpecy = new BirdSpecies();
    //                 $birdSpecy->setScientificName($data['Scientific name'] ?? null);
    //                 $birdSpecy->setFrenchName($data['French name'] ?? null);
    //                 $birdSpecy->setWispeciescode($data['Wispeciescode'] ?? null);
    //                 $birdSpecy->setAuthority($data['Authority'] ?? null);
    //                 $birdSpecy->setCreatedAt(DateTimeImmutable::createFromMutable(new DateTime()));
    //                 $birdSpecy->setCommonName($data['Common name'] ?? null);
    //                 $birdSpecy->setCommonNameAlt($data['Alternative common names'] ?? null);
    //                 $birdSpecy->setSynonyms($data['Synonyms'] ?? null);
    //                 $birdSpecy->setTaxonomicSources($data['Taxonomic source'] ?? null);
    //                 $birdSpecy->setSisRecId($data['SISRecID'] ?? null);
    //                 $birdSpecy->setSpcRecId($data['SpcRecID'] ?? null);
    //                 $birdSpecy->setSubsppId($data['SubsppID'] ?? null);

    //                 $birdSpecy->setBirdFamily($birdFamily ?? null);
    //                 $birdSpecy->setCoverage($coverage ?? null);
    //                 $birdSpecy->setBirdLifeTaxTreat($birdLifeTaxTreat ?? null);
    //                 $birdSpecy->setIucnRedListCategory($iucnRedListCategory ?? null);
            
    //                 $entityManager->persist($birdSpecy);
                    
    //             }
    //         }
            
    //     }
    
    //     $entityManager->flush();
    // }


    /* Meilleur*/
    // private function processCsv(UploadedFile $csvFile, EntityManagerInterface $entityManager)
    // {
    //     $csvData = file_get_contents($csvFile->getPathname());
    //     if (!$csvData) {
    //         error_log("Failed to read CSV file.");
    //         throw new \Exception("Failed to read CSV file.");
    //     }

    //     $rows = array_map(function($row) {
    //         return str_getcsv($row, ';');
    //     }, explode("\n", $csvData));

    //     $headers = array_shift($rows);
    //     if (!$headers) {
    //         error_log("No headers found in CSV file.");
    //         throw new \Exception("No headers found in CSV file.");
    //     }

    //     foreach ($rows as $row) {
    //         if (count($row) !== count($headers)) {
    //             error_log("Row column count does not match header count: " . implode(";", $row));
    //             continue; // Skip rows where the number of columns does not match the number of headers
    //         }

    //         $data = array_combine($headers, $row);
    //         if ($data === false) {
    //             error_log("Failed to combine headers and row: " . implode(";", $row));
    //             continue; // Skip rows where array_combine fails
    //         }

    //         try {
    //             // Fetch or create the BirdFamily entity
    //             $familyName = $data['Family name'] ?? null;
    //             $family = $data['Family'] ?? null;
        
    //             if ($familyName && $family) {
    //                 $familyRepository = $entityManager->getRepository(BirdFamily::class);
    //                 $birdFamily = $familyRepository->findOneBy(['familyName' => $familyName, 'family' => $family]);
        
    //                 if (!$birdFamily) {
    //                     $birdFamily = new BirdFamily();
    //                     $birdFamily->setOrdre($data['Ordre'] ?? null);
    //                     $birdFamily->setFamilyName($data['Family name'] ?? null);
    //                     $birdFamily->setFamily($data['Family'] ?? null);
    //                     $birdFamily->setSubFamily($data['Subfamily'] ?? null);
    //                     $birdFamily->setTribe($data['Tribe'] ?? null);
    //                     $birdFamily->setCreatedAt(DateTimeImmutable::createFromMutable(new DateTime()));
                
    //                     $entityManager->persist($birdFamily);
    //                 }
    //             }

    //             // Fetch or create the Coverage entity
    //             $coverageName = $data['Coverage'] ?? null;
        
    //             if ($coverageName) {
    //                 $coverageRepository = $entityManager->getRepository(Coverage::class);
    //                 $coverage = $coverageRepository->findOneBy(['label' => $coverageName]);
        
    //                 if (!$coverage) {
    //                     $coverage = new Coverage();
    //                     $coverage->setLabel($coverageName);
    //                     $coverage->setCreatedAt(DateTimeImmutable::createFromMutable(new DateTime()));
        
    //                     $entityManager->persist($coverage);
    //                 }
    //             }

    //             // Fetch or create the BirdLifeTaxTreat entity
    //             $birdLifeTaxTreatName = $data['BirdLife taxonomic treatment'] ?? null;
    //             if ($birdLifeTaxTreatName) {
    //                 $birdLifeTaxTreatRepository = $entityManager->getRepository(BirdLifeTaxTreat::class);
    //                 $birdLifeTaxTreat = $birdLifeTaxTreatRepository->findOneBy(['label' => $birdLifeTaxTreatName]);
        
    //                 if (!$birdLifeTaxTreat) {
    //                     $birdLifeTaxTreat = new BirdLifeTaxTreat();
    //                     $birdLifeTaxTreat->setLabel($birdLifeTaxTreatName);
    //                     $birdLifeTaxTreat->setCreatedAt(DateTimeImmutable::createFromMutable(new DateTime()));
        
    //                     $entityManager->persist($birdLifeTaxTreat);
    //                 }
    //             }

    //             // Fetch or create the IucnRedListCategory entity
    //             $iucnRedListCategoryName = $data['2022 IUCN Red List category'] ?? null;
        
    //             if ($iucnRedListCategoryName) {
    //                 $iucnRedListCategoryRepository = $entityManager->getRepository(IucnRedListCategory::class);
    //                 $iucnRedListCategory = $iucnRedListCategoryRepository->findOneBy(['label' => $iucnRedListCategoryName]);
        
    //                 if (!$iucnRedListCategory) {
    //                     $iucnRedListCategory = new IucnRedListCategory();
    //                     $iucnRedListCategory->setLabel($iucnRedListCategoryName);
    //                     $iucnRedListCategory->setCreatedAt(DateTimeImmutable::createFromMutable(new DateTime()));
        
    //                     $entityManager->persist($iucnRedListCategory);
    //                 }
    //             }

    //             // Check if the BirdSpecies already exists
    //             $scientificName = $data['Scientific name'] ?? null;
    //             if ($scientificName) {
    //                 $birdSpeciesRepository = $entityManager->getRepository(BirdSpecies::class);
    //                 $birdSpecy = $birdSpeciesRepository->findOneBy(['scientificName' => $scientificName]);

    //                 if (!$birdSpecy) {
    //                     // Create the BirdSpecies entity if it does not exist
    //                     $birdSpecy = new BirdSpecies();
    //                     $birdSpecy->setScientificName($scientificName);
    //                     $birdSpecy->setFrenchName($data['French name'] ?? null);
    //                     $birdSpecy->setWispeciescode($data['Wispeciescode'] ?? null);
    //                     $birdSpecy->setAuthority($data['Authority'] ?? null);
    //                     $birdSpecy->setCreatedAt(DateTimeImmutable::createFromMutable(new DateTime()));
    //                     $birdSpecy->setCommonName($data['Common name'] ?? null);
    //                     $birdSpecy->setCommonNameAlt($data['Alternative common names'] ?? null);
    //                     $birdSpecy->setSynonyms($data['Synonyms'] ?? null);
    //                     $birdSpecy->setTaxonomicSources($data['Taxonomic source'] ?? null);
    //                     $birdSpecy->setSisRecId($data['SISRecID'] ?? null);
    //                     $birdSpecy->setSpcRecId($data['SpcRecID'] ?? null);
    //                     $birdSpecy->setSubsppId($data['SubsppID'] ?? null);

    //                     $birdSpecy->setBirdFamily($birdFamily ?? null);
    //                     $birdSpecy->setCoverage($coverage ?? null);
    //                     $birdSpecy->setBirdLifeTaxTreat($birdLifeTaxTreat ?? null);
    //                     $birdSpecy->setIucnRedListCategory($iucnRedListCategory ?? null);
                
    //                     $entityManager->persist($birdSpecy);
    //                 }
    //             }
                
    //         } catch (\Exception $e) {
    //             error_log("Failed to process row: " . implode(";", $row) . " - Error: " . $e->getMessage());
    //         }
    //     }

    //     $entityManager->flush();
    // }


    private function processCsv(UploadedFile $csvFile, EntityManagerInterface $entityManager)
    {
        $csvData = file_get_contents($csvFile->getPathname());
        if (!$csvData) {
            error_log("Failed to read CSV file.");
            throw new \Exception("Failed to read CSV file.");
        }
    
        $rows = array_map(function($row) {
            return str_getcsv($row, ';');
        }, explode("\n", $csvData));
    
        $headers = array_shift($rows);
        if (!$headers) {
            error_log("No headers found in CSV file.");
            throw new \Exception("No headers found in CSV file.");
        }
    
        $linesImported = 0;
        $linesFailed = 0;
    
        foreach ($rows as $row) {
            if (count($row) !== count($headers)) {
                error_log("Row column count does not match header count: " . implode(";", $row));
                $linesFailed++;
                continue; // Skip rows where the number of columns does not match the number of headers
            }
    
            $data = array_combine($headers, $row);
            if ($data === false) {
                error_log("Failed to combine headers and row: " . implode(";", $row));
                $linesFailed++;
                continue; // Skip rows where array_combine fails
            }
    
            try {
                // Fetch or create the BirdFamily entity
                $familyName = $data['Family name'] ?? null;
                $family = $data['Family'] ?? null;
        
                if ($familyName && $family) {
                    $familyRepository = $entityManager->getRepository(BirdFamily::class);
                    $birdFamily = $familyRepository->findOneBy(['familyName' => $familyName, 'family' => $family]);
        
                    if (!$birdFamily) {
                        $birdFamily = new BirdFamily();
                        $birdFamily->setOrdre($data['Ordre'] ?? null);
                        $birdFamily->setFamilyName($data['Family name'] ?? null);
                        $birdFamily->setFamily($data['Family'] ?? null);
                        $birdFamily->setSubFamily($data['Subfamily'] ?? null);
                        $birdFamily->setTribe($data['Tribe'] ?? null);
                        $birdFamily->setCreatedAt(DateTimeImmutable::createFromMutable(new DateTime()));
                
                        $entityManager->persist($birdFamily);
                    }
                }
    
                // Fetch or create the Coverage entity
                $coverageName = $data['Coverage'] ?? null;
        
                if ($coverageName) {
                    $coverageRepository = $entityManager->getRepository(Coverage::class);
                    $coverage = $coverageRepository->findOneBy(['label' => $coverageName]);
        
                    if (!$coverage) {
                        $coverage = new Coverage();
                        $coverage->setLabel($coverageName);
                        $coverage->setCreatedAt(DateTimeImmutable::createFromMutable(new DateTime()));
        
                        $entityManager->persist($coverage);
                    }
                }
    
                // Fetch or create the BirdLifeTaxTreat entity
                $birdLifeTaxTreatName = $data['BirdLife taxonomic treatment'] ?? null;
                if ($birdLifeTaxTreatName) {
                    $birdLifeTaxTreatRepository = $entityManager->getRepository(BirdLifeTaxTreat::class);
                    $birdLifeTaxTreat = $birdLifeTaxTreatRepository->findOneBy(['label' => $birdLifeTaxTreatName]);
        
                    if (!$birdLifeTaxTreat) {
                        $birdLifeTaxTreat = new BirdLifeTaxTreat();
                        $birdLifeTaxTreat->setLabel($birdLifeTaxTreatName);
                        $birdLifeTaxTreat->setCreatedAt(DateTimeImmutable::createFromMutable(new DateTime()));
        
                        $entityManager->persist($birdLifeTaxTreat);
                    }
                }
    
                // Fetch or create the IucnRedListCategory entity
                $iucnRedListCategoryName = $data['2022 IUCN Red List category'] ?? null;
        
                if ($iucnRedListCategoryName) {
                    $iucnRedListCategoryRepository = $entityManager->getRepository(IucnRedListCategory::class);
                    $iucnRedListCategory = $iucnRedListCategoryRepository->findOneBy(['label' => $iucnRedListCategoryName]);
        
                    if (!$iucnRedListCategory) {
                        $iucnRedListCategory = new IucnRedListCategory();
                        $iucnRedListCategory->setLabel($iucnRedListCategoryName);
                        $iucnRedListCategory->setCreatedAt(DateTimeImmutable::createFromMutable(new DateTime()));
        
                        $entityManager->persist($iucnRedListCategory);
                    }
                }
    
                // Check if the BirdSpecies already exists
                $scientificName = $data['Scientific name'] ?? null;
                if ($scientificName) {
                    $birdSpeciesRepository = $entityManager->getRepository(BirdSpecies::class);
                    $birdSpecy = $birdSpeciesRepository->findOneBy(['scientificName' => $scientificName]);
    
                    if (!$birdSpecy) {
                        // Create the BirdSpecies entity if it does not exist
                        $birdSpecy = new BirdSpecies();
                        $birdSpecy->setScientificName($scientificName);
                        $birdSpecy->setFrenchName($data['French name'] ?? null);
                        $birdSpecy->setWispeciescode($data['Wispeciescode'] ?? null);
                        $birdSpecy->setAuthority($data['Authority'] ?? null);
                        $birdSpecy->setCreatedAt(DateTimeImmutable::createFromMutable(new DateTime()));
                        $birdSpecy->setCommonName($data['Common name'] ?? null);
                        $birdSpecy->setCommonNameAlt($data['Alternative common names'] ?? null);
                        $birdSpecy->setSynonyms($data['Synonyms'] ?? null);
                        $birdSpecy->setTaxonomicSources($data['Taxonomic source'] ?? null);
                        $birdSpecy->setSisRecId($data['SISRecID'] ?? null);
                        $birdSpecy->setSpcRecId($data['SpcRecID'] ?? null);
                        $birdSpecy->setSubsppId($data['SubsppID'] ?? null);
    
                        $birdSpecy->setBirdFamily($birdFamily ?? null);
                        $birdSpecy->setCoverage($coverage ?? null);
                        $birdSpecy->setBirdLifeTaxTreat($birdLifeTaxTreat ?? null);
                        $birdSpecy->setIucnRedListCategory($iucnRedListCategory ?? null);
                
                        $entityManager->persist($birdSpecy);
                        $linesImported++;
                    }
                }
                
            } catch (\Exception $e) {
                error_log("Failed to process row: " . implode(";", $row) . " - Error: " . $e->getMessage());
                $linesFailed++;
            }
        }
    
        $entityManager->flush();
    
        if ($linesImported > 0) {
            echo "Les données ont été importées avec succès. Lignes importées : $linesImported, Lignes échouées : $linesFailed.";
        } else {
            echo "Aucune nouvelle donnée n'a été importée. Lignes échouées : $linesFailed.";
        }
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
