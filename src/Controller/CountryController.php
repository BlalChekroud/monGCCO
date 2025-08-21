<?php

namespace App\Controller;

use App\Form\ExportType;
use App\Service\ExportService;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use App\Form\ImportCsvType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Entity\Country;
use App\Form\CountryType;
use App\Repository\CountryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/user/country')]
// #[IsGranted('ROLE_COLLECTOR', message: 'Vous n\'avez pas l\'accès.')]
class CountryController extends AbstractController
{
    private $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    #[Route('/', name: 'app_country_index', methods: ['GET', 'POST'])]
    public function index(ExportService $exportService, CountryRepository $countryRepository, Request $request, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ImportCsvType::class);
        $form->handleRequest($request);

        $formExport = $this->createForm(ExportType::class);
        $formExport->handleRequest($request);

        if ($formExport->isSubmitted() && $formExport->isValid()) {
            if (!$this->isGranted('ROLE_EXPORT')) {
                $this->addFlash('warning', $this->translator->trans('export_permission'));
                return $this->redirectToRoute('app_country_index');
            }
            $columnNames = ['Pays', 'Iso2', 'Ajouter le'];
            $countries = $countryRepository->findAll();

            $data = [];
            foreach ($countries as $country) {
                $data[] = [
                    $country->getName(),
                    $country->getIso2(),
                    $country->getCreatedAt()->format('d-m-Y H:i:s'),
                ];
            }
            $format = $formExport->get('format')->getData();
            $fileName = sprintf("Exporter les données des pays %s", date('d-m-Y_His'));
    
            return $exportService->export($columnNames, $data, $format, $fileName);
        }
    
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $csvFile */
            $csvFile = $form->get('csvFile')->getData();
            
            if ($csvFile) {
                if (!$this->isGranted('ROLE_IMPORT')) {
                    throw $this->createNotFoundException($this->translator->trans('import_permission'));
                }

                $csvData = file_get_contents($csvFile->getPathname());

                // Convertir l'encodage si nécessaire
                if (!mb_check_encoding($csvData, 'UTF-8')) {
                    $csvData = mb_convert_encoding($csvData, 'UTF-8', 'ISO-8859-1'); // Changez 'ISO-8859-1' si besoin
                }

                // Vérifiez si la conversion a réussi
                if (!mb_check_encoding( $csvData, 'UTF-8')) {
                    $this->addFlash('error', $this->translator->trans('error.invalid_csv'));
                    return $this->redirectToRoute('app_country_index');
                }

                $rows = array_map(function($row) {
                    return str_getcsv($row, ';'); // Assurez-vous que le séparateur correspond au fichier CSV
                }, explode("\n", $csvData));
                
                $headers = array_shift($rows); // Enlever la première ligne qui contient les en-têtes
    
                return $this->render('country/index.html.twig', [
                    'countries' => $countryRepository->findAll(),
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
    
            $importedCount = 0; // Compteur de pays importés
            $invalidCount = 0; // Compteur de lignes non importées
            $processedCountries = []; // Tableau pour suivre les pays déjà traités
            $invalidRows = []; // Tableau pour stocker les numéros des lignes invalides
    
            foreach ($rows as $lineNumber => $row) {
                // Ignorer les lignes vides
                if (empty(array_filter($row))) {
                    continue;
                }
    
                // Vérifiez si la ligne a le même nombre de colonnes que les en-têtes
                if (count($row) !== count($headers)) {
                    $invalidRows[] = $lineNumber + 2; // Ajouter 2 pour compenser les décalages d'index et la ligne d'en-tête
                    $invalidCount++; // Compter comme ligne non valide
                    // $this->addFlash('error', 'Ligne incorrecte : ' . implode(', ', $row));
                    continue; // Ignorez cette ligne
                }
        
                $data = array_combine($headers, $row);
        
                if ($data === false) {
                    $invalidRows[] = $lineNumber + 2;
                    $invalidCount++;
                    continue; // Ignorez les lignes où array_combine échoue
                }
    
                $countryName = $data['country'];
                $iso2 = $data['iso2'];
    
                if (empty($countryName) || empty($iso2)) {
                    $invalidRows[] = $lineNumber + 2;
                    $invalidCount++;
                    continue;
                }

                // Vérifier si le pays a déjà été traité dans ce fichier
                if (isset($processedCountries[$countryName])) {
                    $invalidRows[] = $lineNumber + 2;
                    $invalidCount++;
                    continue; // Si oui, ignorer cette entrée
                }
    
                // Vérifier si le pays existe déjà dans la base de données
                $existingCountry = $countryRepository->findOneBy(['name' => $countryName]);
                if ($existingCountry) {
                    $processedCountries[$countryName] = true;
                    $invalidRows[] = $lineNumber + 2;
                    $invalidCount++;
                    continue; // Si oui, ignorer cette entrée
                }
    
                // Créez et persistez un nouveau pays
                $country = new Country();
                $country->setName($countryName);
                $country->setIso2($data['iso2']);
                $country->setCreatedAt(new \DateTimeImmutable());
    
                $entityManager->persist($country);
                $importedCount++; // Incrémentez le compteur
    
                // Marquer ce pays comme traité
                $processedCountries[$countryName] = true;
            }
    
            // Affichez le nombre de lignes importées et non importées    
            try {
                $entityManager->flush();
                $this->addFlash('success', $this->translator->trans('success_import', [
                    '%importedCount%' => $importedCount,
                    '%invalidCount%' => $invalidCount
                ]));
            } catch (\Exception $e) {
                $this->addFlash('error', $this->translator->trans('error.import', [
                    '%message%' => $e->getMessage()
                ]));
            }
            
            if ($invalidCount > 0) {
                $this->addFlash('error', $this->translator->trans('error.invalid_rows', [
                    '%count%' => $invalidCount,
                    '%invalidRows%' => implode(', ', $invalidRows),
                ]));
            }

            return $this->redirectToRoute('app_country_index');
        }
    
        return $this->render('country/index.html.twig', [
            'countries' => $countryRepository->findAll(),
            'form' => $form->createView(),
            'formExport' => $formExport->createView(),
        ]);
    }
    
    #[Route('/new', name: 'app_country_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $country = new Country();
        $form = $this->createForm(CountryType::class, $country);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                try {
                    // Ajout de vérifications supplémentaires avant de persister l'entité
                    if (empty($country->getName()) || empty($country->getIso2())) {
                        $this->addFlash('error', $this->translator->trans('country.msg.name_and_iso2_required'));
                        return $this->redirectToRoute('app_country_new');
                    }
                    $country->setCreatedAt(new \DateTimeImmutable());
                    $entityManager->persist($country);
                    $entityManager->flush();
                    $this->addFlash('success', $this->translator->trans('country.msg.created_success'));
                    return $this->redirectToRoute('app_country_index', [], Response::HTTP_SEE_OTHER);
                } catch (\Exception $e) {
                    $this->addFlash('error', $e->getMessage());
                    return $this->redirectToRoute('app_country_new', [], Response::HTTP_SEE_OTHER);
                }
            } else {
                $this->addFlash('error', $this->translator->trans('country.msg.created_error'));
            }
        }

        return $this->render('country/new.html.twig', [
            'country' => $country,
            'form' => $form,
        ]);
    }

    
    // Méthode utilitaire pour vérifier la connectivité
    // private function isConnected(): bool
    // {
    //     return @fsockopen("www.google.com", 80); // Test simple pour détecter une connexion
    // }

    // #[Route('/api/sync', name: 'app_country_sync', methods: ['POST'])]
    // public function sync(Request $request, EntityManagerInterface $entityManager): Response
    // {
    //     $data = json_decode($request->getContent(), true);
    
    //     foreach ($data as $countryData) {
    //         $country = new Country();
    //         $country->setName($countryData['name']);
    //         $country->setIso2($countryData['iso2']);
    //         $country->setCreatedAt(new \DateTimeImmutable($countryData['createdAt']));
    //         $entityManager->persist($country);
    //     }
    
    //     $entityManager->flush();
    //     return $this->json(['message' => 'Synchronisation réussie !'], Response::HTTP_OK);
    // }
    
    #[Route('/{id}', name: 'app_country_show', methods: ['GET'])]
    public function show(Country $country): Response
    {
        return $this->render('country/show.html.twig', [
            'country' => $country,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_country_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Country $country, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(CountryType::class, $country);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                // Ajout de vérifications supplémentaires avant de persister l'entité
                if (empty($country->getName()) || empty($country->getIso2())) {
                    $this->addFlash('error', $this->translator->trans('country.msg.name_and_iso2_required'));
                    return $this->redirectToRoute('app_country_new');
                }

                try {
                    $country->setUpdatedAt(new \DateTimeImmutable());
                    $entityManager->flush();
                    $this->addFlash('success', $this->translator->trans('country.msg.updated_success'));
                    return $this->redirectToRoute('app_country_index', [], Response::HTTP_SEE_OTHER);
                } catch (\Exception $e) {
                    $this->addFlash('error', $e->getMessage());
                    return $this->redirectToRoute('app_country_edit', ['id' => $country->getId()], Response::HTTP_SEE_OTHER);}
            } else {
                $this->addFlash('error', $this->translator->trans('country.msg.updated_error'));
            }
        }

        return $this->render('country/edit.html.twig', [
            'country' => $country,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_country_delete', methods: ['POST'])]
    public function delete(Request $request, Country $country, EntityManagerInterface $entityManager): Response
    {
        try {
            if ($this->isCsrfTokenValid('delete'.$country->getId(), $request->getPayload()->get('_token'))) {
                $entityManager->remove($country);
                $entityManager->flush();
                $this->addFlash('success', $this->translator->trans('country.msg.deleted_success'));
            } else {
                $this->addFlash('error',$this->translator->trans('country.msg.deleted_error'));
            }
        } catch (\Exception $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('app_country_index', [], Response::HTTP_SEE_OTHER);
    }
}
