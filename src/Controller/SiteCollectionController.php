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
use App\Entity\SiteCollection;
use App\Form\SiteCollectionType;
use App\Repository\SiteCollectionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/site/collection')]
#[IsGranted('ROLE_COLLECTOR')]
class SiteCollectionController extends AbstractController
{
    #[Route('/', name: 'app_site_collection_index', methods: ['GET'])]
    public function index(Request $request, SiteCollectionRepository $siteCollectionRepository, EntityManagerInterface $entityManager): Response
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

                return $this->redirectToRoute('app_site_collection_index');
            }
        }

        return $this->render('site_collection/index.html.twig', [
            'site_collections' => $siteCollectionRepository->findAll(),
            'form' => $form->createView(),
        ]);
    }

    #[Route('/import', name: 'app_site_collection_import', methods: ['POST'])]
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
                    return $this->redirectToRoute('app_site_collection_import');
                }

                if ($csvFile) {
                    try {
                        $this->processCsv($csvFile, $entityManager);
                        $this->addFlash('success', 'Les données ont été importées avec succès.');
                        return $this->redirectToRoute('app_site_collection_index');
                    } catch (\Exception $e) {
                        $this->addFlash('error', 'Une erreur s\'est produite lors de l\'importation du fichier CSV : ' . $e->getMessage());
                    }
                }
            } else {
                $this->addFlash('error', 'Le fichier CSV contient des erreurs de validation.');
            }
        }

        return $this->render('site_collection/import.html.twig', [
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
    
            $siteCollection = new SiteCollection();
            $siteCollection->setSiteName($data['Nom du site'] ?? null);
            $siteCollection->setSiteCode($data['Code du site'] ?? null);
            $siteCollection->setNationalSiteCode($data['Code national'] ?? null);
            $siteCollection->setInternationalSiteCode($data['Code international'] ?? null);
            $siteCollection->setLatDepart($data['Latitude de depart'] ?? null);
            $siteCollection->setLongDepart($data['Longitude de depart'] ?? null);
            $siteCollection->setLatFin($data['Latitude de fin'] ?? null);
            $siteCollection->setLongFin($data['Longitude de fin'] ?? null);
            $siteCollection->setRegion($data['Region'] ?? null);
            $siteCollection->setParentSiteName($data['Nom du site parent'] ?? null);

            $siteCollection->setCreatedAt(DateTimeImmutable::createFromMutable(new DateTime()));
    
            $entityManager->persist($siteCollection);
        }
    
        $entityManager->flush();
    }

    #[Route('/preview', name: 'app_site_collection_preview', methods: ['POST'])]
    public function preview(Request $request): Response
    {
        $form = $this->createForm(ImportCsvType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                /** @var UploadedFile $csvFile */
                $csvFile = $form->get('csvFile')->getData();
                if ($csvFile) {
                    $csvData = file_get_contents($csvFile->getPathname());
                    $rows = array_map(function($row) {
                        return str_getcsv($row, ';');
                    }, explode("\n", $csvData));

                    $headers = array_shift($rows);

                    return $this->render('site_collection/preview.html.twig', [
                        'headers' => $headers,
                        'rows' => $rows,
                        'csvData' => $csvData, // Pass the raw CSV data to the template
                    ]);
                } else {
                    // Add a flash message or log an error if the file is not provided
                    $this->addFlash('error', 'No CSV file was provided.');
                }
            } else {
                // Add a flash message or log an error if the form is invalid
                $this->addFlash('error', 'The form is invalid.');
            }
        } else {
            // Add a flash message or log an error if the form is not submitted
            $this->addFlash('error', 'The form was not submitted.');
        }

        return $this->redirectToRoute('app_site_collection_index');
    }
    
    // #[Route('/preview', name: 'app_site_collection_preview', methods: ['POST'])]
    // public function preview(Request $request): Response
    // {
    //     $form = $this->createForm(ImportCsvType::class);
    //     $form->handleRequest($request);

    //     if ($form->isSubmitted() && $form->isValid()) {
    //         /** @var UploadedFile $csvFile */
    //         $csvFile = $form->get('csvFile')->getData();
    //         if ($csvFile) {
    //             $csvData = file_get_contents($csvFile->getPathname());
    //             $rows = array_map(function($row) {
    //                 return str_getcsv($row, ';');
    //             }, explode("\n", $csvData));
                
    //             $headers = array_shift($rows);

    //             return $this->render('site_collection/preview.html.twig', [
    //                 'headers' => $headers,
    //                 'rows' => $rows,
    //                 'csvData' => $csvData, // Pass the raw CSV data to the template
    //             ]);
    //         }
    //     }

    //     return $this->redirectToRoute('app_site_collection_index');
    // }

    #[Route('/new', name: 'app_site_collection_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $siteCollection = new SiteCollection();
        $form = $this->createForm(SiteCollectionType::class, $siteCollection);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $siteCollection->setCreatedAt(DateTimeImmutable::createFromMutable(new DateTime()));
            $entityManager->persist($siteCollection);
            $entityManager->flush();
            $this->addFlash('success', "Site de collection a bien été crée");

            return $this->redirectToRoute('app_site_collection_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('site_collection/new.html.twig', [
            'site_collection' => $siteCollection,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_site_collection_show', methods: ['GET'])]
    public function show(SiteCollection $siteCollection): Response
    {
        return $this->render('site_collection/show.html.twig', [
            'site_collection' => $siteCollection,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_site_collection_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, SiteCollection $siteCollection, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(SiteCollectionType::class, $siteCollection);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $siteCollection->setUpdatedAt(DateTimeImmutable::createFromMutable(new DateTime()));
            $entityManager->flush();
            $this->addFlash('success', "Site de collection a bien été modifié");

            return $this->redirectToRoute('app_site_collection_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('site_collection/edit.html.twig', [
            'site_collection' => $siteCollection,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_site_collection_delete', methods: ['POST'])]
    public function delete(Request $request, SiteCollection $siteCollection, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$siteCollection->getId(), $request->getPayload()->get('_token'))) {
            $entityManager->remove($siteCollection);
            $entityManager->flush();
            $this->addFlash('success', "Site de collection a bien été supprimée");
        }

        return $this->redirectToRoute('app_site_collection_index', [], Response::HTTP_SEE_OTHER);
    }
}
