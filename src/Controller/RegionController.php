<?php

namespace App\Controller;

use App\Entity\Country;
use App\Form\ExportType;
use App\Service\ExportService;
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
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/user/region')]
class RegionController extends AbstractController
{
    public function __construct(private readonly TranslatorInterface $translator) {}

    #[Route('/', name: 'app_region_index', methods: ['GET', 'POST'])]
    public function index(ExportService $exportService, RegionRepository $regionRepository, Request $request, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ImportCsvType::class);
        $form->handleRequest($request);

        $formExport = $this->createForm(ExportType::class);
        $formExport->handleRequest($request);

        if ($formExport->isSubmitted() && $formExport->isValid()) {
            if (!$this->isGranted('ROLE_EXPORT')) {
                $this->addFlash('warning', $this->translator->trans('export_permission'));
                return $this->redirectToRoute('app_region_index');
            }
            $columnNames = ['Région', 'Code région', 'Pays', 'Ajouter le'];
            $regions = $regionRepository->findAll();

            $data = [];
            foreach ($regions as $region) {
                $data[] = [
                    $region->getName(),
                    $region->getRegionCode(),
                    $region->getCountry()->getName(),
                    $region->getCreatedAt()->format('d-m-Y H:i:s'),
                ];
            }
            $format = $formExport->get('format')->getData();
            $fileName = sprintf("Exporter les données des régions %s", date('d-m-Y_His'));
    
            return $exportService->export($columnNames, $data, $format, $fileName);
        }
    
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
                    $this->addFlash('error', $this->translator->trans('error.invalid_csv'));
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
    
            $importedCount = 0; // Compteur de régions importées
            $processedRegions = []; // Tableau pour suivre les régions déjà traitées
            $invalidCount = 0; // Compteur de lignes non importées
            $invalidRows = []; // Tableau pour stocker les numéros des lignes invalides
            $processedRegions = [];
    
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
                    if (!isset($processedRegions[$countryName])) {
                        $country = new Country();
                        $country->setName($countryName);
                        $country->setIso2($iso2);
                        $country->setCreatedAt(new \DateTimeImmutable());

                        $entityManager->persist($country);
                        $processedRegions[$countryName] = $country; // Marquer comme traité
                        $existingCountry = $country; // Réassigner pour utiliser l'objet persisté
                    } else {
                        $existingCountry = $processedRegions[$countryName]; // Utiliser le pays déjà traité
                    }
                } else {
                    $processedRegions[$countryName] = $existingCountry; // Marquer comme traité
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
            
            return $this->redirectToRoute('app_region_index');
        }
    
        return $this->render('region/index.html.twig', [
            'regions' => $regionRepository->findAll(),
            'form' => $form->createView(),
            'formExport' => $formExport->createView(),
        ]);
    }
    
    #[IsGranted('ROLE_CREAT', message: 'Vous n\'avez pas l\'accès.')]
    #[Route('/new', name: 'app_region_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $region = new Region();
        $form = $this->createForm(RegionType::class, $region);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                try {
                    $region->setCreatedAt(new \DateTimeImmutable());
                    $entityManager->persist($region);
                    $entityManager->flush();
                    $this->addFlash('success', $this->translator->trans('region.msg.created_success'));
                    return $this->redirectToRoute('app_region_index', [], Response::HTTP_SEE_OTHER);
                } catch (\Exception $e) {
                    $this->addFlash('error', $e->getMessage());
                    return $this->redirectToRoute('app_region_new', [], Response::HTTP_SEE_OTHER);
                }
            } else {
                $this->addFlash('error', $this->translator->trans('region.msg.created_error'));
            }
        }

        return $this->render('region/new.html.twig', [
            'region' => $region,
            'form' => $form,
        ]);
    }

    #[IsGranted('ROLE_VIEW', message: 'Vous n\'avez pas l\'accès.')]
    #[Route('/{id}', name: 'app_region_show', methods: ['GET'])]
    public function show(Region $region): Response
    {
        return $this->render('region/show.html.twig', [
            'region' => $region,
        ]);
    }

    #[IsGranted('ROLE_EDIT', message: 'Vous n\'avez pas l\'accès.')]
    #[Route('/{id}/edit', name: 'app_region_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Region $region, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(RegionType::class, $region);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                try {
                    $region->setUpdatedAt(new \DateTimeImmutable());
                    $entityManager->flush();
                    $this->addFlash('success', $this->translator->trans('region.msg.updated_success'));
                    return $this->redirectToRoute('app_region_show', ['id' => $region->getId()], Response::HTTP_SEE_OTHER);
                } catch (\Exception $e) {
                    $this->addFlash('error', $e->getMessage());
                    return $this->redirectToRoute('app_region_edit', ['id' => $region->getId()], Response::HTTP_SEE_OTHER);
                }
            } else {
                $this->addFlash('error', $this->translator->trans('region.msg.updated_error'));
            }
        }

        return $this->render('region/edit.html.twig', [
            'region' => $region,
            'form' => $form,
        ]);
    }

    #[IsGranted('ROLE_DELETE', message: 'Vous n\'avez pas l\'accès.')]
    #[Route('/{id}', name: 'app_region_delete', methods: ['POST'])]
    public function delete(Request $request, Region $region, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$region->getId(), $request->getPayload()->getString('_token'))) {
            try {
                $entityManager->remove($region);
                $entityManager->flush();
                $this->addFlash('success', $this->translator->trans('region.msg.deleted_success'));
            } catch (\Exception $e) {
                $this->addFlash('error', $e->getMessage());
                return $this->redirectToRoute('app_region_index', [], Response::HTTP_SEE_OTHER);
            }
        } else {
            $this->addFlash('error',$this->translator->trans('region.msg.deleted_error'));
        }

        return $this->redirectToRoute('app_region_index', [], Response::HTTP_SEE_OTHER);
    }
}
