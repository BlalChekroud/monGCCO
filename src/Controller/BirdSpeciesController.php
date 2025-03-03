<?php

namespace App\Controller;

use App\Exporter\ExcelOpenSpoutExporter;
use App\Form\ExportType;
use App\Service\ExportService;
use Symfony\Contracts\Translation\TranslatorInterface;
use App\Entity\Image;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use App\Form\ImportCsvType;
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

#[Route('/user/bird/species')]
class BirdSpeciesController extends AbstractController
{
    private $birdSpeciesRepository;

    public function __construct(private readonly ExcelOpenSpoutExporter $exporter, BirdSpeciesRepository $birdSpeciesRepository)
    {
        $this->birdSpeciesRepository = $birdSpeciesRepository;
    }
    
    #[Route('/', name: 'app_bird_species_index', methods: ['GET', 'POST'])]
    public function index(ExportService $exportService, Request $request, BirdSpeciesRepository $birdSpeciesRepository, EntityManagerInterface $entityManager, TranslatorInterface $translator): Response
    {
        // if ($this->getUser() !== $this->isGranted('IS_AUTHENTICATED_FULLY')) {
        //     $this->addFlash('warning', $translator->trans('please_log_in_to_access_the_page'));
        //     return $this->redirectToRoute('app_login');
        // }
        
        // Formulaire d'importation
        $form = $this->createForm(ImportCsvType::class);
        $form->handleRequest($request);
        // Formulaire d'exportation
        $formExport = $this->createForm(ExportType::class);
        $formExport->handleRequest($request);

        if ($formExport->isSubmitted() && $formExport->isValid()) {
            $columnNames = ['Scientific Name', 'French Name', 'English Name', 'WI Specy Code', 'Family Name', 'Family', 'Created at'];
            $birdSpecies = $birdSpeciesRepository->findAll();
    
            $data = [];
            foreach ($birdSpecies as $birdSpecy) {
                $data[] = [
                    $birdSpecy->getScientificName(),
                    $birdSpecy->getFrenchName(),
                    $birdSpecy->getEnglishName(),
                    $birdSpecy->getWispeciescode(),
                    $birdSpecy->getBirdFamily()->getFamilyName(),
                    $birdSpecy->getBirdFamily()->getFamily(),
                    $birdSpecy->getCreatedAt()->format('d-m-Y H:i:s'),
                ];
            }
    
            $format = $formExport->get('format')->getData();
            $fileName = sprintf("bird_species_export_%s", date('d-m-Y_His'));
    
            return $exportService->export($columnNames, $data, $format, $fileName);
        }

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $csvFile */
            $csvFile = $form->get('csvFile')->getData();

            if ($csvFile) {
                if (!$this->isGranted('ROLE_IMPORT')) {
                    throw $this->createNotFoundException($translator->trans('import_permission'));
                }

                $csvData = file_get_contents($csvFile->getPathname());
                // Convertir l'encodage si nécessaire
                if (!mb_check_encoding($csvData, 'UTF-8')) {
                    $csvData = mb_convert_encoding($csvData, 'UTF-8', 'ISO-8859-1'); // Changez 'ISO-8859-1' si besoin
                }

                // Vérifiez si la conversion a réussi
                if (!mb_check_encoding( $csvData, 'UTF-8')) {
                    $this->addFlash('error', $translator->trans('birdSpecies.error.invalid_encoding'));
                    return $this->redirectToRoute('app_country_index');
                }
                
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
                    'formExport' => $formExport->createView(),
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
            $invalidRows = []; // Tableau pour stocker les numéros des lignes invalides

            foreach ($rows as $lineNumber => $row) {
                // Ignorez les lignes vides, mais ne comptez pas la dernière ligne vide
                if (empty(array_filter($row))) {
                    continue; // Ignorez cette ligne sans l'incrémenter à invalidCount
                }

                // Vérifiez si la ligne a le même nombre de colonnes que les en-têtes
                if (count($row) !== count($headers)) {
                    $invalidRows[] = $lineNumber + 2; // Ajouter 2 pour compenser les décalages d'index et la ligne d'en-tête
                    $invalidCount++; // Compter comme ligne non valide
                    continue; // Ignorez cette ligne
                }

                $data = array_combine($headers, $row);

                if ($data === false) {
                    $invalidRows[] = $lineNumber + 2;
                    $invalidCount++;
                    continue; // Ignorez les lignes où array_combine échoue
                }

                // Fetch or create the BirdSpecies entity
                $scientificName = $data['Scientific name'] ?? null;
                $familyName = $data['Family name'] ?? null;

                if (empty($scientificName)) {
                    $invalidRows[] = $lineNumber + 2;
                    $invalidCount++;
                    continue; // Ignorez si les champs nécessaires sont vides
                }

                // Vérifier si l'espèce a déjà été traitée dans ce fichier
                if (isset($processedSpecies[$scientificName])) {
                    $invalidRows[] = $lineNumber + 2;
                    $invalidCount++;
                    $processedSpecies[$scientificName] = true; // Marquer le site comme traité
                    continue; // Si oui, ignorer cette entrée
                }

                // Vérifier si l'espèce existe déjà dans la base de données
                $existingSpecies = $birdSpeciesRepository->findOneBy(['scientificName' => $scientificName]);
                if ($existingSpecies) {
                    $processedSpecies[$scientificName] = true;
                    $invalidRows[] = $lineNumber + 2;
                    $invalidCount++; // Compter comme ligne non valide
                    continue; // Si oui, ignorer cette entrée
                }

                // Récupérer ou créer le pays associé
                $birdFamilyRepository = $entityManager->getRepository(BirdFamily::class);
                $existingFamily = $birdFamilyRepository->findOneBy(['familyName' => $familyName]);

                if (!$existingFamily) {
                    $birdFamily = new BirdFamily();
                    $birdFamily->setOrdre($data['Ordre']);
                    $birdFamily->setFamilyName($familyName);
                    $birdFamily->setFamily( $data['Family']);
                    $birdFamily->setSubFamily($data['Subfamily'] ?? null);
                    $birdFamily->setTribe($data['Tribe'] ?? null);
                    $birdFamily->setCreatedAt(new \DateTimeImmutable());
        
                    $entityManager->persist($birdFamily);
                    $existingFamily = $birdFamily; // Réassigner pour utiliser l'objet persisté
                }

                // Créez et persistez une nouvelle espèce d'oiseaux
                $birdSpecy = new BirdSpecies();
                $birdSpecy->setScientificName($scientificName);
                $birdSpecy->setFrenchName($data['French name'] ?? '');
                $birdSpecy->setEnglishName($data['English name'] ?? '');
                $birdSpecy->setWispeciescode($data['Wispeciescode'] ?? '');
                $birdSpecy->setAuthority($data['Authority'] ?? '');
                $birdSpecy->setCreatedAt(new \DateTimeImmutable());
                $birdSpecy->setCommonName($data['Common name'] ?? '');
                $birdSpecy->setCommonNameAlt($data['Alternative common names'] ?? '');
                $birdSpecy->setSynonyms($data['Synonyms'] ?? '');
                $birdSpecy->setTaxonomicSources($data['Taxonomic source'] ?? '');
                $birdSpecy->setSisRecId($data['SISRecID'] ?? 0);
                if ($data['SpcRecID'] == null || !$data['SpcRecID']){ $birdSpecy->setSpcRecId(0);} else { $birdSpecy->setSpcRecId($data['SpcRecID']); }
                // $birdSpecy->setSpcRecId($data['SpcRecID'] ?? 0);
                $birdSpecy->setSubsppId($data['SubsppID'] ?? '');

                $birdSpecy->setBirdFamily($existingFamily ?? null);
                // $birdSpecy->setCoverage($coverage ?? null);
                $birdSpecy->setBirdLifeTaxTreat($birdLifeTaxTreat ?? null);
                $birdSpecy->setIucnRedListCategory($iucnRedListCategory ?? null);
        

                $entityManager->persist($birdSpecy);
                $importedCount++; // Incrémentez le compteur

                // Marquer cette espèce comme traitée
                $processedSpecies[$scientificName] = true;
            }

            // Affichez le nombre de lignes importées et non importées    
            try {
                $entityManager->flush();
                $this->addFlash('success', $translator->trans('birdSpecies.msg.success_import', ['%importedCount%' => $importedCount, '%invalidCount%' => $invalidCount]));
            } catch (\Exception $e) {
                $this->addFlash('error', $translator->trans('birdSpecies.error.import', ['%message%' => $e->getMessage()]));
            }
            
            if ($invalidCount > 0) {
                $this->addFlash('error', $translator->trans('birdSpecies.error.invalid_lines', [
                    '%invalidCount%' => $invalidCount,
                    '%invalidRows%' => implode(', ', $invalidRows) // Conversion du tableau en chaîne
                ]));
            }
            return $this->redirectToRoute('app_bird_species_index');
        }

        return $this->render('bird_species/index.html.twig', [
            'bird_species' => $birdSpeciesRepository->findAll(),
            'form' => $form->createView(),
            'formExport' => $formExport->createView(),
        ]);
    }

    // # VALIDE #
    // #[Route('/export', name: 'app_bird_species_export')]
    // public function export(Request $request, BirdSpeciesRepository $birdSpeciesRepository): Response
    // {
    //     $form = $this->createForm(ExportType::class)
    //                 ->handleRequest($request);

    //     if ($form->isSubmitted() && $form->isValid()) {
    //         $columnNames = ['Scientific Name', 'French Name', 'Created at'];
    //         $birdSpecies = $birdSpeciesRepository->findAll();
            
    //         // Prepare an array to store the data rows
    //         $data = [];
    //         foreach ($birdSpecies as $birdSpecy) {
    //             // Extract data from the BirdSpecies entity and map it to an array
    //             $row = [
    //                 $birdSpecy->getScientificName(),
    //                 $birdSpecy->getFrenchName(),
    //                 $birdSpecy->getCreatedAt()->format('Y-m-d H:i:s'),
    //             ];
                
    //             // Add the row to the array of rows
    //             $data[] = $row;
    //         }

    //         /** @var ExportFormat $format */
    //         $format = $form->get('format')->getData();

    //         $response = new StreamedResponse(fn () => $this->exporter->export($columnNames, $data, $format));
            
    //         $response->headers->set('Content-Type', $format->contentType());
            
    //         return $response;
    //     }

    //     return $this->render('bird_species/export.html.twig', [
    //         'formExport' => $form->createView(),
    //     ]);
    // }
    

    #[Route('/new', name: 'app_bird_species_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_CREAT', message: 'Vous n\'avez pas l\'accès.')]
    public function new(Request $request, EntityManagerInterface $entityManager, TranslatorInterface $translator): Response
    {
        $birdSpecy = new BirdSpecies();
        $form = $this->createForm(BirdSpeciesType::class, $birdSpecy);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
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
                $this->addFlash('success', $translator->trans('birdSpecies.msg.success_create'));
    
                return $this->redirectToRoute('app_bird_species_index', [], Response::HTTP_SEE_OTHER);

            } else {
                $this->addFlash('error', $translator->trans('birdSpecies.error.creation_failed'));
            }
        }

        // $coverageForm = $this->createForm(CoverageType::class);
        $birdLifeTaxTreatForm = $this->createForm(BirdLifeTaxTreatType::class);
        $iucnRedListCategoryForm = $this->createForm(IucnRedListCategoryType::class);

        return $this->render('bird_species/new.html.twig', [
            'bird_specy' => $birdSpecy,
            'form' => $form,
            // 'coverageForm' => $coverageForm->createView(),
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
    // #[Route('/get-bird-image/{id}', name: 'get_bird_image', methods: ['GET'])]
    // public function getBirdImage(BirdSpecies $birdSpecies): JsonResponse
    // {
    //     $imageUrl = $birdSpecies->getImage() ? $birdSpecies->getImage()->getImageUrl() : null;
    
    //     return new JsonResponse(['imageUrl' => $imageUrl]);
    // }

    #[Route('/{id}/image', name: 'bird_species_image', methods: ['GET'])]
    public function getBirdImage($id): JsonResponse
    {
        $birdSpecy = $this->birdSpeciesRepository->find($id);

        if (!$birdSpecy || !$birdSpecy->getImage()) {
            return new JsonResponse(['image' => null], Response::HTTP_NOT_FOUND);
        }

        $imageUrl = $this->getParameter('vich_uploader.upload_directory') . '/' . $birdSpecy->getImage()->getImageFilename();
        
        return new JsonResponse(['image' => $imageUrl]);
    }


    #[Route('/{id}', name: 'app_bird_species_show', methods: ['GET', 'POST'])]
    public function show(ExportService $exportService, Request $request, BirdSpecies $birdSpecy): Response
    {
        // Formulaire d'exportation
        $formExport = $this->createForm(ExportType::class);
        $formExport->handleRequest($request);

        if ($formExport->isSubmitted() && $formExport->isValid()) {
            $columnNames = ['Nom scientifique', 'Nom en Français', 'Nom en Englais', "Code WI d'espèce", 'Nom de Famille', 'Famille', 'Sous famille', 'Tribu', 'Ordre', 
            'Autorité', 'Nom commun', 'Noms communs alternatifs', 'Synonymes', 'Source(s) Taxonomique', 'SisRec ID', 'SpcRec ID', 'Subspp ID',
            'Traitement taxonomique de BirdLife', "Catégorie de la liste rouge de l'IUCN 2022", 'Couverture','créé le'];

            // Les données doivent être encapsulées dans un tableau multidimensionnel
            $data = [
                [
                    $birdSpecy->getScientificName(),
                    $birdSpecy->getFrenchName(),
                    $birdSpecy->getEnglishName(),
                    $birdSpecy->getWispeciescode(),
                    $birdSpecy->getBirdFamily()->getFamilyName(),
                    $birdSpecy->getBirdFamily()->getFamily(),
                    $birdSpecy->getBirdFamily()->getSubFamily(),
                    $birdSpecy->getBirdFamily()->getTribe(),
                    $birdSpecy->getBirdFamily()->getOrdre(),
                    $birdSpecy->getAuthority(),
                    $birdSpecy->getCommonName(),
                    $birdSpecy->getCommonNameAlt(),
                    $birdSpecy->getSynonyms(),
                    $birdSpecy->getTaxonomicSources(),
                    $birdSpecy->getSisRecId(),
                    $birdSpecy->getSpcRecId(),
                    $birdSpecy->getSubsppId(),
                    $birdSpecy->getBirdLifeTaxTreat(),
                    $birdSpecy->getIucnRedListCategory(),
                    $birdSpecy->getCoverage(),
                    $birdSpecy->getCreatedAt()->format('d-m-Y H:i:s'),
                ]
            ];
            $format = $formExport->get('format')->getData();
            $fileName = sprintf("bird_specy_export_%s", date('d-m-Y_His'));
    
            return $exportService->export($columnNames, $data, $format, $fileName);
        }
        return $this->render('bird_species/show.html.twig', [
            'bird_specy' => $birdSpecy,
            'formExport' => $formExport->createView(),
        ]);
    }

    #[IsGranted('ROLE_EDIT', message: 'Vous n\'avez pas l\'accès.')]
    #[Route('/{id}/edit', name: 'app_bird_species_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, BirdSpecies $birdSpecy, EntityManagerInterface $entityManager, TranslatorInterface $translator): Response
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
                $this->addFlash('success', $translator->trans('birdSpecies.msg.success_update'));
    
                return $this->redirectToRoute('app_bird_species_index', [], Response::HTTP_SEE_OTHER);
            
            } else {
                $this->addFlash('error', $translator->trans('birdSpecies.error.modification_failed'));
            }
        }

        return $this->render('bird_species/edit.html.twig', [
            'bird_specy' => $birdSpecy,
            'form' => $form,
        ]);
    }

    #[IsGranted('ROLE_DELETE', message: 'Vous n\'avez pas l\'accès.')]
    #[Route('/{id}', name: 'app_bird_species_delete', methods: ['POST'])]
    public function delete(Request $request, BirdSpecies $birdSpecy, EntityManagerInterface $entityManager, TranslatorInterface $translator): Response
    {
        if ($this->isCsrfTokenValid('delete'.$birdSpecy->getId(), $request->getPayload()->get('_token'))) {
            $entityManager->remove($birdSpecy);
            $entityManager->flush();
            $this->addFlash('success', $translator->trans('birdSpecies.msg.success_delete'));
        } else {
            $this->addFlash('error', $translator->trans('birdSpecies.error.deletion_failed'));
        }

        return $this->redirectToRoute('app_bird_species_index', [], Response::HTTP_SEE_OTHER);
    }
}
