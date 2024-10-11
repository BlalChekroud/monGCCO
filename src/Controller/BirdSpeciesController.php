<?php

namespace App\Controller;

use App\Entity\Image;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use App\Form\ImportCsvType;
use DateTime;
use App\Form\CoverageType;
use App\Form\BirdLifeTaxTreatType;
use App\Form\IucnRedListCategoryType;
use Symfony\Component\HttpFoundation\JsonResponse;
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
class BirdSpeciesController extends AbstractController
{
    #[Route('/', name: 'app_bird_species_index', methods: ['GET', 'POST'])]
public function index(Request $request, BirdSpeciesRepository $birdSpeciesRepository, EntityManagerInterface $entityManager): Response
{
    $form = $this->createForm(ImportCsvType::class);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        /** @var UploadedFile $csvFile */
        $csvFile = $form->get('csvFile')->getData();

        if ($csvFile) {
            if (!$this->isGranted('ROLE_IMPORT')) {
                throw $this->createNotFoundException('Vous n\'avez pas l\'autorisation d\'importer des données.');
            }

            $csvData = file_get_contents($csvFile->getPathname());
            $rows = array_map(function($row) {
                return str_getcsv($row, ';'); // Assurez-vous que le séparateur correspond au fichier CSV
            }, explode("\n", $csvData));

            $headers = array_shift($rows); // Enlever la première ligne qui contient les en-têtes

            return $this->render('bird_species/index.html.twig', [
                'bird_species' => $birdSpeciesRepository->findAll(),
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

        $importedCount = 0; // Compteur d'espèces d'oiseaux importées
        $invalidCount = 0; // Compteur de lignes non importées
        $processedSpecies = []; // Tableau pour suivre les espèces déjà traitées

        foreach ($rows as $row) {
            // Ignorez les lignes vides, mais ne comptez pas la dernière ligne vide
            if (empty(array_filter($row))) {
                continue; // Ignorez cette ligne sans l'incrémenter à invalidCount
            }

            // Vérifiez si la ligne a le même nombre de colonnes que les en-têtes
            if (count($row) !== count($headers)) {
                $invalidCount++; // Compter comme ligne non valide
                continue; // Ignorez cette ligne
            }

            $data = array_combine($headers, $row);

            if ($data === false) {
                $invalidCount++; // Compter comme ligne non valide
                continue; // Ignorez les lignes où array_combine échoue
            }

            // Fetch or create the BirdSpecies entity
            $scientificName = $data['Scientific name'] ?? null;

            if (empty($scientificName)) {
                $invalidCount++; // Compter comme ligne non valide
                continue; // Ignorez si les champs nécessaires sont vides
            }

            // Vérifier si l'espèce a déjà été traitée dans ce fichier
            if (isset($processedSpecies[$scientificName])) {
                $invalidCount++; // Compter comme ligne non valide
                continue; // Si oui, ignorer cette entrée
            }

            // Vérifier si l'espèce existe déjà dans la base de données
            $existingSpecies = $birdSpeciesRepository->findOneBy(['scientificName' => $scientificName]);
            if ($existingSpecies) {
                $processedSpecies[$scientificName] = true;
                $invalidCount++; // Compter comme ligne non valide
                continue; // Si oui, ignorer cette entrée
            }

            // Créez et persistez une nouvelle espèce d'oiseaux
            $birdSpecy = new BirdSpecies();
            $birdSpecy->setScientificName($scientificName);
            $birdSpecy->setFrenchName($data['French name'] ?? null);
            $birdSpecy->setWispeciescode($data['Wispeciescode'] ?? null);
            $birdSpecy->setAuthority($data['Authority'] ?? null);
            $birdSpecy->setCreatedAt(\DateTimeImmutable::createFromMutable(new DateTime()));
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
            $importedCount++; // Incrémentez le compteur

            // Marquer cette espèce comme traitée
            $processedSpecies[$scientificName] = true;
        }

        $entityManager->flush();

        // Affichez le nombre de lignes importées et non importées
        $this->addFlash('success', "$importedCount espèces d'oiseaux ont été importées avec succès. $invalidCount lignes n'ont pas pu être importées.");

        return $this->redirectToRoute('app_bird_species_index');
    }

    return $this->render('bird_species/index.html.twig', [
        'bird_species' => $birdSpeciesRepository->findAll(),
        'form' => $form->createView(),
    ]);
}

    // #[Route('/', name: 'app_bird_species_index', methods: ['GET', 'POST'])]
    // public function index(Request $request, BirdSpeciesRepository $birdSpeciesRepository, EntityManagerInterface $entityManager): Response
    // {
    //     $form = $this->createForm(ImportCsvType::class);
    //     $form->handleRequest($request);

    //     $importedCount = 0; // Compteur d'espèces importées
    //     $invalidCount = 0;  // Compteur d'espèces non importées

    //     if ($form->isSubmitted() && $form->isValid()) {
    //         // if (!$this->isGranted('ROLE_IMPORT')) {
    //         //     throw $this->createNotFoundException('Vous n\'avez pas l\'autorisation d\'importer des données.');
    //         // }

    //         /** @var UploadedFile $csvFile */
    //         $csvFile = $form->get('csvFile')->getData();
    //         if ($csvFile) {
    //             // Debug: Vérifiez si le fichier a bien été chargé
    //             $this->addFlash('info', 'Fichier CSV chargé : ' . $csvFile->getClientOriginalName());

    //             $csvData = file_get_contents($csvFile->getPathname());
    //             $rows = array_map(fn($row) => str_getcsv($row, ';'), explode("\n", $csvData));
    //             $headers = array_shift($rows);

    //             return $this->render('bird_species/index.html.twig', [
    //                 'bird_species' => $birdSpeciesRepository->findAll(),
    //                 'form' => $form->createView(),
    //                 'headers' => $headers,
    //                 'rows' => $rows,
    //                 'csvData' => $csvData, // Passez les données CSV
    //                 'importedCount' => $importedCount,
    //                 'invalidCount' => $invalidCount,
    //             ]);
    //         }
    //     }

    //     if ($request->isMethod('POST') && $request->request->get('action') === 'import') {
    //         $this->addFlash('info', 'Tentative d\'importation des données CSV.');

    //         $csvData = $request->request->get('csvData'); // Récupérez csvData de la requête
    //         list($importedCount, $invalidCount) = $this->handleImport($csvData, $entityManager, $birdSpeciesRepository); // Passez csvData comme paramètre
    //     }

    //     return $this->render('bird_species/index.html.twig', [
    //         'bird_species' => $birdSpeciesRepository->findAll(),
    //         'form' => $form->createView(),
    //         'importedCount' => $importedCount,
    //         'invalidCount' => $invalidCount,
    //     ]);
    // }

    // private function handleImport(string $csvData, EntityManagerInterface $entityManager, BirdSpeciesRepository $birdSpeciesRepository): array
    // {
    //     $rows = array_map(fn($row) => str_getcsv($row, ';'), explode("\n", $csvData));
    //     $headers = array_shift($rows);

    //     $importedCount = 0;
    //     $invalidCount = 0;

    //     foreach ($rows as $row) {
    //         if (empty(array_filter($row))) continue; // Ignore empty rows
    //         if (count($row) !== count($headers)) {
    //             $invalidCount++;
    //             continue; // Invalid row length
    //         }

    //         $data = array_combine($headers, $row);
    //         if ($data === false) {
    //             $invalidCount++;
    //             continue; // Failed to combine headers with data
    //         }

    //         try {
    //             $this->processRow($data, $entityManager);
    //             $importedCount++;
    //         } catch (\Exception $e) {
    //             error_log("Failed to process row: " . implode(";", $row) . " - Error: " . $e->getMessage());
    //             $invalidCount++; // Incrementer le compteur des invalides en cas d'erreur
    //         }
    //     }

    //     $entityManager->flush();
    //     $this->addFlash('success', "$importedCount espèces d'oiseaux ont été importées avec succès. $invalidCount lignes n'ont pas pu être importées.");

    //     return [$importedCount, $invalidCount]; // Retourne les compteurs
    // }

    // private function processRow(array $data, EntityManagerInterface $entityManager): void
    // {
    //     // Fetch or create the BirdFamily entity
    //     $familyName = $data['Family name'] ?? null;
    //     $family = $data['Family'] ?? null;
    //     $ordre = $data['Ordre'] ?? null;

    //     if ($familyName && $family) {
    //         $familyRepository = $entityManager->getRepository(BirdFamily::class);
    //         $birdFamily = $familyRepository->findOneBy(['familyName' => $familyName, 'family' => $family]);

    //         if (!$birdFamily) {
    //             $birdFamily = new BirdFamily();
    //             $birdFamily->setOrdre($ordre);
    //             $birdFamily->setFamilyName($familyName);
    //             $birdFamily->setFamily($family);
    //             $birdFamily->setSubFamily($data['Subfamily'] ?? null);
    //             $birdFamily->setTribe($data['Tribe'] ?? null);
    //             $birdFamily->setCreatedAt(new \DateTimeImmutable());

    //             $entityManager->persist($birdFamily);
    //         }
    //     }

    //     // Fetch or create the Coverage entity
    //     $coverageName = $data['Coverage'] ?? null;

    //     if ($coverageName) {
    //         $coverageRepository = $entityManager->getRepository(Coverage::class);
    //         $coverage = $coverageRepository->findOneBy(['label' => $coverageName]);

    //         if (!$coverage) {
    //             $coverage = new Coverage();
    //             $coverage->setLabel($coverageName);
    //             $coverage->setCreatedAt(new \DateTimeImmutable());

    //             $entityManager->persist($coverage);
    //         }
    //     }

    //     // Fetch or create the BirdLifeTaxTreat entity
    //     $birdLifeTaxTreatName = $data['BirdLife taxonomic treatment'] ?? null;
    //     if ($birdLifeTaxTreatName) {
    //         $birdLifeTaxTreatRepository = $entityManager->getRepository(BirdLifeTaxTreat::class);
    //         $birdLifeTaxTreat = $birdLifeTaxTreatRepository->findOneBy(['label' => $birdLifeTaxTreatName]);

    //         if (!$birdLifeTaxTreat) {
    //             $birdLifeTaxTreat = new BirdLifeTaxTreat();
    //             $birdLifeTaxTreat->setLabel($birdLifeTaxTreatName);
    //             $birdLifeTaxTreat->setCreatedAt(new \DateTimeImmutable());

    //             $entityManager->persist($birdLifeTaxTreat);
    //         }
    //     }

    //     // Fetch or create the IucnRedListCategory entity
    //     $iucnRedListCategoryName = $data['2022 IUCN Red List category'] ?? null;

    //     if ($iucnRedListCategoryName) {
    //         $iucnRedListCategoryRepository = $entityManager->getRepository(IucnRedListCategory::class);
    //         $iucnRedListCategory = $iucnRedListCategoryRepository->findOneBy(['label' => $iucnRedListCategoryName]);

    //         if (!$iucnRedListCategory) {
    //             $iucnRedListCategory = new IucnRedListCategory();
    //             $iucnRedListCategory->setLabel($iucnRedListCategoryName);
    //             $iucnRedListCategory->setCreatedAt(new \DateTimeImmutable());

    //             $entityManager->persist($iucnRedListCategory);
    //         }
    //     }

    //     if (empty($data['Scientific name'])) {
    //         throw new \Exception('Le nom scientifique est manquant.');
    //     }
    //     // Check if the BirdSpecies already exists
    //     $scientificName = $data['Scientific name'] ?? null;
    //     if ($scientificName) {
    //         $birdSpeciesRepository = $entityManager->getRepository(BirdSpecies::class);
    //         $birdSpecies = $birdSpeciesRepository->findOneBy(['scientificName' => $scientificName]);

    //         if (!$birdSpecies) {
    //             // Create the BirdSpecies entity if it does not exist
    //             $birdSpecies = new BirdSpecies();
    //             $birdSpecies->setScientificName($scientificName);
    //             $birdSpecies->setFrenchName($data['French name'] ?? null);
    //             $birdSpecies->setWispeciescode($data['Wispeciescode'] ?? null);
    //             $birdSpecies->setAuthority($data['Authority'] ?? null);
    //             $birdSpecies->setCreatedAt(new \DateTimeImmutable());
    //             $birdSpecies->setCommonName($data['Common name'] ?? null);
    //             $birdSpecies->setCommonNameAlt($data['Alternative common names'] ?? null);
    //             $birdSpecies->setSynonyms($data['Synonyms'] ?? null);
    //             $birdSpecies->setTaxonomicSources($data['Taxonomic source'] ?? null);
    //             $birdSpecies->setSisRecId($data['SISRecID'] ?? null);
    //             $birdSpecies->setSpcRecId($data['SpcRecID'] ?? null);
    //             $birdSpecies->setSubsppId($data['SubsppID'] ?? null);

    //             $birdSpecies->setBirdFamily($birdFamily ?? null);
    //             $birdSpecies->setCoverage($coverage ?? null);
    //             $birdSpecies->setBirdLifeTaxTreat($birdLifeTaxTreat ?? null);
    //             $birdSpecies->setIucnRedListCategory($iucnRedListCategory ?? null);

    //             $entityManager->persist($birdSpecies);
    //         }
    //     }
    // }
    

    #[Route('/new', name: 'app_bird_species_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_CREAT', message: 'Vous n\'avez pas l\'accès.')]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $birdSpecy = new BirdSpecies();
        $form = $this->createForm(BirdSpeciesType::class, $birdSpecy);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $imageFile */
            $imageFile = $form->get('image')['imageFile']->getData(); // Get the uploaded image

            // Handle image upload only if a new image is provided
            if ($imageFile) {
                // If there's already an image, we need to update it
                if ($birdSpecy->getImage()) {
                    $image = $birdSpecy->getImage();
                    $image->setCreatedAt(new \DateTimeImmutable());
                    $image->setImageFile($imageFile); // Update with the new file
                } else {
                    // If there's no image yet, create a new Image entity
                    $image = new Image;
                    $image->setImageFile($imageFile);
                    $image->setCreatedAt(new \DateTimeImmutable());
                    $entityManager->persist($image);
                    $birdSpecy->setImage($image); // Set the new image to the birdSpecy
                }
            }
            $birdSpecy->setCreatedAt(new \DateTimeImmutable());
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

    // #[Route('/get-bird-image/{id}', name: 'get_bird_image', methods: ['GET'])]
    // public function getBirdImage(BirdSpecies $birdSpecies): JsonResponse
    // {
    //     // Utiliser la méthode `getImageUrl` ou une méthode similaire pour obtenir l'URL de l'image
    //     $imageUrl = $birdSpecies->getImageUrl();

    //     return new JsonResponse(['imageUrl' => $imageUrl]);
    // }


    #[Route('/{id}', name: 'app_bird_species_show', methods: ['GET'])]
    public function show(BirdSpecies $birdSpecy): Response
    {
        return $this->render('bird_species/show.html.twig', [
            'bird_specy' => $birdSpecy,
        ]);
    }

    #[IsGranted('ROLE_EDIT', message: 'Vous n\'avez pas l\'accès.')]
    #[Route('/{id}/edit', name: 'app_bird_species_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, BirdSpecies $birdSpecy, EntityManagerInterface $entityManager): Response
    {
        
        $form = $this->createForm(BirdSpeciesType::class, $birdSpecy);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                /** @var UploadedFile $imageFile */
                $imageFile = $form->get('image')['imageFile']->getData(); // Récupérer l'image téléchargée
    
                // Gestion du téléchargement de l'image
                if ($imageFile) {
                    // Si l'espèce a déjà une image, on la met à jour
                    if ($birdSpecy->getImage()) {
                        $image = $birdSpecy->getImage(); // Récupère l'image existante
                        $image->setImageFile($imageFile); // Remplace le fichier d'image
                        if (!$image->getCreatedAt()) {
                            $image->setCreatedAt(new \DateTimeImmutable());
                        }
                        $image->setUpdatedAt(new \DateTimeImmutable()); // Met à jour la date de modification
                    } else {
                        // Si l'espèce n'a pas encore d'image, on crée une nouvelle entité Image
                        $image = new Image();
                        $image->setImageFile($imageFile);
                        $image->setCreatedAt(new \DateTimeImmutable());
                        $entityManager->persist($image);
                        $birdSpecy->setImage($image); // Associe l'image à l'espèce
                    }
                }
                $birdSpecy->setUpdatedAt(new \DateTimeImmutable());
                $entityManager->flush();
                $this->addFlash('success', "L'espèse a bien été modifié");
    
                return $this->redirectToRoute('app_bird_species_index', [], Response::HTTP_SEE_OTHER);
            
            } else {
                $this->addFlash('error','Une erreur s\'est produite lors de modification de l\'espèse.');
            }
        }

        return $this->render('bird_species/edit.html.twig', [
            'bird_specy' => $birdSpecy,
            'form' => $form,
        ]);
    }

    #[IsGranted('ROLE_DELETE', message: 'Vous n\'avez pas l\'accès.')]
    #[Route('/{id}', name: 'app_bird_species_delete', methods: ['POST'])]
    public function delete(Request $request, BirdSpecies $birdSpecy, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$birdSpecy->getId(), $request->getPayload()->get('_token'))) {
            $entityManager->remove($birdSpecy);
            $entityManager->flush();
            $this->addFlash('success', "L'espèse a bien été supprimée");
        } else {
            $this->addFlash('error','Une erreur s\'est produite lors de la suppression d\'espèse');
        }

        return $this->redirectToRoute('app_bird_species_index', [], Response::HTTP_SEE_OTHER);
    }
}
