<?php

namespace App\Controller;

use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

use App\Form\ImportCsvType;
use App\Service\FileUploader;

use Monolog\DateTimeImmutable;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use App\Form\BirdFamilyType;
use App\Entity\BirdFamily;
use Symfony\Component\HttpFoundation\Request;
use App\Repository\BirdFamilyRepository;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/bird/family')]
class BirdFamilyController extends AbstractController
{
    #[Route('/', name: 'app_bird_family_index', methods: ['GET', 'POST'])]
    public function index(Request $request, BirdFamilyRepository $birdFamilyRepository, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ImportCsvType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $csvFile */
            $csvFile = $form->get('csvFile')->getData();

            if ($csvFile && $this->IsGranted('ROLE_IMPORT')) {
                try {
                    $this->processCsv($csvFile, $entityManager);
                    $this->addFlash('success', 'Les données ont été importées avec succès.');
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Une erreur s\'est produite lors de l\'importation du fichier CSV : ' . $e->getMessage());
                }

                return $this->redirectToRoute('app_bird_family_index');
            }
        }

        return $this->render('bird_family/index.html.twig', [
            'bird_families' => $birdFamilyRepository->findAll(),
            'form' => $form->createView(),
        ]);
    }


    #[Route('/new', name: 'app_bird_family_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_CREAT', message: 'Vous n\'avez pas l\'accès.')]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $birdFamily = new BirdFamily();
        $form = $this->createForm(BirdFamilyType::class, $birdFamily);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $birdFamily->setCreatedAt(DateTimeImmutable::createFromMutable(new DateTime()));
            $entityManager->persist($birdFamily);
            $entityManager->flush();
            $this->addFlash('success', "Famille d'oiseaux a bien été crée");

            return $this->redirectToRoute('app_bird_family_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('bird_family/new.html.twig', [
            'bird_family' => $birdFamily,
            'form' => $form,
        ]);
    }

    #[Route('/import', name: 'app_bird_family_import', methods: ['POST'])]
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
                    return $this->redirectToRoute('app_bird_family_import');
                }

                if ($csvFile) {
                    try {
                        $this->processCsv($csvFile, $entityManager);
                        $this->addFlash('success', 'Les données ont été importées avec succès.');
                        return $this->redirectToRoute('app_bird_family_index');
                    } catch (\Exception $e) {
                        $this->addFlash('error', 'Une erreur s\'est produite lors de l\'importation du fichier CSV : ' . $e->getMessage());
                    }
                }
            } else {
                $this->addFlash('error', 'Le fichier CSV contient des erreurs de validation.');
            }
        }

        return $this->render('bird_family/import.html.twig', [
            'form' => $form->createView(),
        ]);
    }
    // public function import(Request $request, EntityManagerInterface $entityManager): Response
    // {
    //     $form = $this->createForm(ImportCsvType::class);
    //     $form->handleRequest($request);

    //     if ($form->isSubmitted() && $form->isValid()) {
    //         /** @var UploadedFile $csvFile */
    //         $csvFile = $form->get('csvFile')->getData();

    //         // Validate file extension
    //         $extension = $csvFile->getClientOriginalExtension();
    //         if (!in_array($extension, ['csv', 'txt'])) {
    //             $this->addFlash('error', 'Veuillez charger un fichier CSV ou TXT valide.');
    //             return $this->redirectToRoute('app_bird_family_import');
    //         }

    //         if ($csvFile) {
    //             try {
    //                 $this->processCsv($csvFile, $entityManager);
    //                 $this->addFlash('success', 'Les données ont été importées avec succès.');

    //                 return $this->redirectToRoute('app_bird_family_index');
    //             } catch (\Exception $e) {
    //                 $this->addFlash('error', 'Une erreur s\'est produite lors de l\'importation du fichier CSV : ' . $e->getMessage());
    //             }
    //         }
    //     }

    //     return $this->render('bird_family/import.html.twig', [
    //         'form' => $form->createView(),
    //     ]);
    // }

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
    
            // $birdFamily = new BirdFamily();
            // $birdFamily->setOrdre($data['Ordre'] ?? null);
            // $birdFamily->setFamilyName($data['Family name'] ?? null);
            // $birdFamily->setFamily($data['Family'] ?? null);
            // $birdFamily->setSubFamily($data['Subfamily'] ?? null);
            // $birdFamily->setTribe($data['Tribe'] ?? null);
            // $birdFamily->setCreatedAt(DateTimeImmutable::createFromMutable(new DateTime()));
    
            // $entityManager->persist($birdFamily);

            // Fetch or create the Birdfamily entity
            $familyName = $data['Family name'] ?? null;
            $subFamily = $data['subfamily'] ?? null;
            $family = $data['Family'] ?? null;
            $ordre = $data['Ordre'] ?? null;
            $tribe = $data['Tribe'] ?? null;
    
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
        }
    
        $entityManager->flush();
    }
    
    #[Route('/preview', name: 'app_bird_family_preview', methods: ['POST'])]
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

                return $this->render('bird_family/preview.html.twig', [
                    'headers' => $headers,
                    'rows' => $rows,
                    'csvData' => $csvData, // Pass the raw CSV data to the template
                ]);
            }
        }

        return $this->redirectToRoute('app_bird_family_index');
    }

    #[Route('/{id}', name: 'app_bird_family_show', methods: ['GET'])]
    public function show(BirdFamily $birdFamily): Response
    {
        return $this->render('bird_family/show.html.twig', [
            'bird_family' => $birdFamily,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_bird_family_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_EDIT', message: 'Vous n\'avez pas l\'accès.')]
    public function edit(Request $request, BirdFamily $birdFamily, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(BirdFamilyType::class, $birdFamily);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $birdFamily->setUpdatedAt(DateTimeImmutable::createFromMutable(new DateTime()));
            $entityManager->flush();
            $this->addFlash('success', 'La famille a bien été modifié');

            return $this->redirectToRoute('app_bird_family_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('bird_family/edit.html.twig', [
            'bird_family' => $birdFamily,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_bird_family_delete', methods: ['POST'])]
    #[IsGranted('ROLE_DELETE', message: 'Vous n\'avez pas l\'accès.')]
    public function delete(Request $request, BirdFamily $birdFamily, EntityManagerInterface $entityManager): Response
    {
        $csrfToken = $request->request->get('_token'); // Utilisation de `request->request` pour obtenir le payload
    
        if ($this->isCsrfTokenValid('delete' . $birdFamily->getId(), $csrfToken)) {
            $entityManager->remove($birdFamily);
            $entityManager->flush();
            $this->addFlash('success', "Famille d'espèce a bien été supprimée");
        }
    
        return $this->redirectToRoute('app_bird_family_index', [], Response::HTTP_SEE_OTHER);
    }
    
}
