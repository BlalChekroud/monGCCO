<?php

namespace App\Controller;

use App\Form\ExportType;
use App\Service\ExportService;
use App\Entity\Country;
use App\Entity\Region;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use App\Form\ImportCsvType;
use App\Entity\City;
use App\Form\CityType;
use App\Repository\CityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/user/city')]
// #[IsGranted('ROLE_COLLECTOR', message: 'Vous n\'avez pas l\'accès.')]
class CityController extends AbstractController
{
    public function __construct(private readonly TranslatorInterface $translator) {}

    #[Route('/', name: 'app_city_index', methods: ['GET', 'POST'])]
    public function index(ExportService $exportService, CityRepository $cityRepository, Request $request, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ImportCsvType::class);
        $form->handleRequest($request);

        $formExport = $this->createForm(ExportType::class);
        $formExport->handleRequest($request);

        if ($formExport->isSubmitted() && $formExport->isValid()) {
            if (!$this->isGranted('ROLE_EXPORT')) {
                $this->addFlash('warning', $this->translator->trans('export_permission'));
                return $this->redirectToRoute('app_city_index');
            }
            $columnNames = ['Ville', 'Latitude', 'Longitude', 'Région', 'Créé le', 'Dernière mise à jour le'];
            $cities = $cityRepository->findAll();

            $data = [];
            foreach ($cities as $city) {
                $createdAt = $city->getCreatedAt();
                $updatedAt = $city->getUpdatedAt();

                $data[] = [
                    $city->getName(),
                    $city->getLatitude(),
                    $city->getLongitude(),
                    $city->getRegion()->getName(),
                    $createdAt ? $createdAt->format('d-m-Y H:i:s') : null,
                    $updatedAt ? $updatedAt->format('d-m-Y H:i:s') : null,
                ];
            }
            $format = $formExport->get('format')->getData();
            $fileName = sprintf("Exporter les données des villes %s", date('d-m-Y_His'));
    
            return $exportService->export($columnNames, $data, $format, $fileName);
        }
    
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $csvFile */
            $csvFile = $form->get('csvFile')->getData();
            
            if ($csvFile && $this->IsGranted('ROLE_IMPORT')) {
                $csvData = file_get_contents($csvFile->getPathname());

                // Convertir l'encodage si nécessaire
                if (!mb_check_encoding($csvData, 'UTF-8')) {
                    $csvData = mb_convert_encoding($csvData, 'UTF-8', 'ISO-8859-1'); // Changez 'ISO-8859-1' si besoin
                }

                // Vérifiez si la conversion a réussi
                if (!mb_check_encoding($csvData, 'UTF-8')) {
                    $this->addFlash('error', $this->translator->trans('error.invalid_csv'));
                    return $this->redirectToRoute('app_city_index');
                }

                $rows = array_map(function($row) {
                    return str_getcsv($row, ';'); // Assurez-vous que le séparateur correspond au fichier CSV
                }, explode("\n", $csvData));
                
                $headers = array_shift($rows); // Enlever la première ligne qui contient les en-têtes
    
                return $this->render('city/index.html.twig', [
                    'cities' => $cityRepository->findAll(),
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
            $processedCities = []; // Tableau pour suivre les régions déjà traitées
            $invalidCount = 0; // Compteur de lignes non importées
            $invalidRows = []; // Tableau pour stocker les numéros des lignes invalides
            // $processedRegions = [];
    
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
    
                $cityName = $data['city'];
                $latitude = $data['lat'];
                $longitude = $data['lng'];
                $regionName = $data['region'] ?? null;
                $regionCode = $data['regionCode'] ?? null;
                $country = $data['country'] ?? null;
    
                if (empty($cityName) || empty($latitude) || empty($longitude) || empty($regionName) || empty($regionCode)) {
                    $invalidRows[] = $lineNumber + 2;
                    $invalidCount++;
                    continue;
                }
    
                // Vérifier si la ville a déjà été traitée dans ce fichier
                if (isset($processedCities[$regionName])) {
                    $invalidRows[] = $lineNumber + 2;
                    $invalidCount++;
                    continue; // Si oui, ignorer cette entrée
                }
    
                // Vérifier si la ville existe déjà dans la base de données
                $existingCity = $cityRepository->findOneBy(['name' => $cityName, 'latitude' => $latitude, 'longitude' => $longitude]);
                if ($existingCity) {
                    $processedCities[$cityName] = true;
                    $invalidRows[] = $lineNumber + 2;
                    $invalidCount++;
                    continue; // Si oui, ignorer cette entrée
                }
    
                // Récupérer ou créer la région associée
                $regionRepository = $entityManager->getRepository(Region::class);
                $existingRegion = $regionRepository->findOneBy(['name' => $regionName, 'regionCode' => $regionCode]);
    
                if (!$existingRegion) {
                    $region = new Region();
                    $region->setName($regionName);
                    $region->setRegionCode($regionCode);
                    $region->setCreatedAt(new \DateTimeImmutable());
                    // Récupérer le pays associé
                    $countryRepository = $entityManager->getRepository(Country::class);
                    $existingCountry = $countryRepository->findOneBy(['name' => $country]);
        
                    if ($existingCountry) {
                        $region->setCountry($existingCountry);
                        $entityManager->persist($region);
                        $existingRegion = $region; // Réassigner pour utiliser l'objet persisté
                    } else {
                        $this->addFlash('info', $this->translator->trans('city.msg.no_country', ['%regionName%' => $regionName]));
                    }
                }
    
                // Créez et persistez une nouvelle ville
                $city = new City();
                $city->setName($cityName);
                $city->setLongitude($longitude);
                $city->setLatitude($latitude);
                $city->setCreatedAt(new \DateTimeImmutable());
    
                // Associer la ville au région
                $city->setRegion($existingRegion);
    
                $entityManager->persist($city);
                $importedCount++; // Incrémentez le compteur
    
                // Marquer cette ville comme traitée
                $processedCities[$cityName] = true;
            }
    
            // try {
            //     $entityManager->flush();
            //     $this->addFlash('success', "$importedCount villes ont été importés avec succès.");
            // } catch (\Exception $e) {
            //     $this->addFlash('error', 'Erreur lors de l\'importation : ' . $e->getMessage());
            // }
            
            // if ($invalidCount > 0) {
            //     $this->addFlash('error', "$invalidCount lignes n'ont pas pu être importées. Numéros des lignes : " . implode(', ', $invalidRows));
            // }
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
            return $this->redirectToRoute('app_city_index');
        }
    
        return $this->render('city/index.html.twig', [
            'cities' => $cityRepository->findAll(),
            'form' => $form->createView(),
            'formExport' => $formExport->createView(),
        ]);
    }

    #[Route('/new', name: 'app_city_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $city = new City();
        $form = $this->createForm(CityType::class, $city);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                try {
                    $city->setCreatedAt(new \DateTimeImmutable());
                    $entityManager->persist($city);
                    $entityManager->flush();
                    $this->addFlash('success', $this->translator->trans('city.msg.created_success'));

                    return $this->redirectToRoute('app_city_index', [], Response::HTTP_SEE_OTHER);
                } catch (\Exception $e) {
                    $this->addFlash('error', $e->getMessage());
                    return $this->redirectToRoute('app_city_new', [], Response::HTTP_SEE_OTHER);
                }
            } else {
                $this->addFlash('error', $this->translator->trans('city.msg.created_error'));
            }
        }

        return $this->render('city/new.html.twig', [
            'city' => $city,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_city_show', methods: ['GET'])]
    public function show(City $city): Response
    {
        return $this->render('city/show.html.twig', [
            'city' => $city,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_city_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, City $city, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(CityType::class, $city);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                try {
                    $city->setUpdatedAt(new \DateTimeImmutable());
                    $entityManager->flush();
                    $this->addFlash('success', $this->translator->trans('city.msg.updated_success'));
        
                    return $this->redirectToRoute('app_city_show', ['id' => $city->getId()], Response::HTTP_SEE_OTHER);
                } catch (\Exception $e) {
                    $this->addFlash('error', $e->getMessage());
                    return $this->redirectToRoute('app_city_index', [], Response::HTTP_SEE_OTHER);
                }
            } else {
                $this->addFlash('error', $this->translator->trans('city.msg.updated_error'));
            }
        }
        return $this->render('city/edit.html.twig', [
            'city' => $city,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_city_delete', methods: ['POST'])]
    public function delete(Request $request, City $city, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$city->getId(), $request->getPayload()->get('_token'))) {
            try {
                $entityManager->remove($city);
                $entityManager->flush();
                $this->addFlash('success', $this->translator->trans('city.msg.deleted_success'));
            } catch (\Exception $e) {
                $this->addFlash('error', $e->getMessage());
                return $this->redirectToRoute('app_city_index', [], Response::HTTP_SEE_OTHER);
            }
        } else {
            $this->addFlash('error',$this->translator->trans('city.msg.deleted_error'));
        } 

        return $this->redirectToRoute('app_city_index', [], Response::HTTP_SEE_OTHER);
    }
}
