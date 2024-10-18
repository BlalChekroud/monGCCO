<?php

namespace App\Controller;

use App\Entity\Region;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use App\Entity\City;

use App\Form\ImportCsvType;
use App\Entity\SiteCollection;
use App\Form\SiteCollectionType;
use App\Repository\SiteCollectionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/site/collection')]
#[IsGranted('ROLE_USER', message: 'Vous n\'avez pas l\'accès.')]
class SiteCollectionController extends AbstractController
{
    #[Route('/', name: 'app_site_collection_index', methods: ['GET', 'POST'])]
    public function index(SiteCollectionRepository $siteCollectionRepository, Request $request, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ImportCsvType::class);
        $form->handleRequest($request);
    
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
                    $this->addFlash('error', 'Le fichier CSV contient des caractères non valides. Veuillez vérifier l\'encodage du fichier.');
                    return $this->redirectToRoute('app_site_collection_index');
                }

                $rows = array_filter(array_map(function($row) {
                    return str_getcsv($row, ';');
                }, explode("\n", $csvData)), function($row) {
                    return !empty(array_filter($row)); // Supprime les lignes vides
                });                
                
                $headers = array_shift($rows); // Enlever la première ligne qui contient les en-têtes
    
                return $this->render('site_collection/index.html.twig', [
                    'site_collections' => $siteCollectionRepository->findAll(),
                    'form' => $form->createView(),
                    'headers' => $headers,
                    'rows' => $rows,
                    'csvData' => $csvData,
                ]);
            } else {
                $this->addFlash('warning' ,"Vous n'avez pas le droit.");
                return $this->redirectToRoute('app_site_collection_index', [], Response::HTTP_SEE_OTHER);
            }
        }
    
        if ($request->isMethod('POST') && $request->request->get('action') === 'import') {
            $csvData = $request->request->get('csvData');
            $rows = array_map(function($row) {
                return str_getcsv($row, ';');
            }, explode("\n", $csvData));
    
            $headers = array_shift($rows);
    
            $importedCount = 0; // Compteur de sites importées
            $processedSites = []; // Tableau pour suivre les sites déjà traitées
            $invalidCount = 0; // Compteur de lignes non importées
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
                    continue; // Ignorez cette ligne
                }
        
                $data = array_combine($headers, $row);
        
                if ($data === false) {
                    $invalidRows[] = $lineNumber + 2;
                    $invalidCount++;
                    continue; // Ignorez les lignes où array_combine échoue
                }
    
                $siteName = $data['siteName'];
                $siteCode = $data['siteCode'];
                $nationalSiteCode = $data['nationalSiteCode'] ?? null;
                $internationalSiteCode = $data['internationalSiteCode'] ?? null;
                $latDepart = $data['latDepart'];
                $longDepart = $data['longDepart'];
                $latFin = $data['latFin'];
                $longFin = $data['longFin'];
                $parentSite = $data['parentSite'] ?? null;
                $cityName = $data['city'] ?? null;
                $lat = $data['cityLat'] ?? null;
                $lng = $data['cityLng'] ?? null;
                $regionName = $data['region'] ?? null;
    
                if (empty($siteName) || empty($siteCode) || empty($latDepart) || empty($longDepart) || empty($latFin) || empty($longFin)  || empty($cityName)) {
                    $invalidRows[] = $lineNumber + 2;
                    $invalidCount++;
                    continue;
                }

                // if (!is_numeric($latDepart) || !is_numeric($longDepart) || !is_numeric($latFin) || !is_numeric($longFin)) {
                //     $this->addFlash('error', "Les coordonnées ne sont pas valides pour le site: $siteName.");
                //     $invalidRows[] = $lineNumber + 2;
                //     $invalidCount++;
                //     continue; // Ignorer cette entrée si les coordonnées ne sont pas valides
                // }                
    
                // Vérifier si le site a déjà été traitée dans ce fichier
                if (isset($processedSites[$siteName])) {
                    $invalidRows[] = $lineNumber + 2;
                    $invalidCount++;
                    $processedSites[$siteName] = true; // Marquer le site comme traité
                    continue; // Si oui, ignorer cette entrée
                }
    
                // Vérifier si le site existe déjà dans la base de données
                $existingSite = $siteCollectionRepository->findOneBy(['siteName' => $siteName, 'siteCode' => $siteCode]);
                if ($existingSite) {
                    $processedSites[$siteName] = true;
                    $invalidRows[] = $lineNumber + 2;
                    $invalidCount++;
                    continue; // Si oui, ignorer cette entrée
                }
    
                // Récupérer ou créer la ville associée
                $cityRepository = $entityManager->getRepository(City::class);
                $existingCity = $cityRepository->findOneBy(['name' => $cityName, 'latitude' => $lat, 'longitude' => $lng]);

                $regionRepository = $entityManager->getRepository(Region::class);
                $existingRegion = $regionRepository->findOneBy(['name' => $regionName]);
    
                // if (!$existingRegion) {
                //     $this->addFlash('info', 'Région n\'existe pas.');
                //     continue;
                // } 
                if (!$existingCity) {
                    $city = new City();
                    $city->setName($cityName);
                    $city->setLatitude($lat);
                    $city->setLongitude($lng);
                    $city->setCreatedAt(new \DateTimeImmutable());
                    $city->setRegion($existingRegion);

                    $entityManager->persist($city);
                    $existingCity = $city; // Réassigner pour utiliser l'objet persisté
                }
    
                $existingSite = $siteCollectionRepository->findOneBy(['siteName' => $siteName, 'siteCode' => $siteCode]);

                if ($existingSite) {
                    $this->addFlash('error', "Le site $siteName avec le code $siteCode existe déjà.");
                    $invalidRows[] = $lineNumber + 2;
                    $invalidCount++;
                    continue; // Ignorer cette entrée si le site existe déjà
                }

                $existingParentSite = $siteCollectionRepository->findOneBy(['siteName' => $parentSite]);

                if (!$existingParentSite && $parentSite) {
                    $this->addFlash('error', "Le site parent '$parentSite' n'existe pas dans la base de données.");
                    // continue; // Passer à la ligne suivante du CSV
                }
                
                // Créez et persistez un nouveau site
                $site = new SiteCollection();
                $site->setSiteName($siteName);
                $site->setSiteCode($siteCode);
                $site->setNationalSiteCode($nationalSiteCode);
                $site->setInternationalSiteCode($internationalSiteCode);
                $site->setLatDepart($latDepart);
                $site->setLongDepart($longDepart);
                $site->setLatFin($latFin);
                $site->setLongFin($longFin);
                $site->setCreatedAt(new \DateTimeImmutable());
                if ($existingParentSite) {
                    $site->setParentSite($existingParentSite);
                } else {
                    $site->setParentSite(null); // Ou gérer cela comme une erreur si nécessaire
                }
                
                // Associer le site au ville
                $site->setCity($existingCity);
    
                $entityManager->persist($site);
                $importedCount++; // Incrémentez le compteur
    
                // Marquer cette ville comme traitée
                $processedSites[$siteName] = true;
            }
    
            try {
                $entityManager->flush();
                $this->addFlash('success', "$importedCount sites ont été importés avec succès.");
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de l\'importation : ' . $e->getMessage());
            }
            
            if ($invalidCount > 0) {
                $this->addFlash('error', "$invalidCount lignes n'ont pas pu être importées. Numéros des lignes : " . implode(', ', $invalidRows));
            }
            return $this->redirectToRoute('app_site_collection_index');
        }
    
        return $this->render('site_collection/index.html.twig', [
            'site_collections' => $siteCollectionRepository->findAll(),
            'form' => $form->createView(),
        ]);
    }

    #[Route('/new', name: 'app_site_collection_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_CREAT', message: 'Vous n\'avez pas l\'accès.')]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $siteCollection = new SiteCollection();
        $form = $this->createForm(SiteCollectionType::class, $siteCollection);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                if ($this->IsGranted('ROLE_CREAT')) {

                    $siteCollection->setCreatedAt(new \DateTimeImmutable());
                    $entityManager->persist($siteCollection);
                    $entityManager->flush();
                    $this->addFlash('success', "Site de collecte a bien été crée");
        
                    return $this->redirectToRoute('app_site_collection_index', [], Response::HTTP_SEE_OTHER);
                } else {
                    $this->addFlash('warning',"Vous n'avez pas le droit.");
                    return $this->redirectToRoute('app_site_collection_new', [], Response::HTTP_SEE_OTHER);
                }
            } else {
                $this->addFlash('error','Une erreur s\'est produite lors de l\'ajout du site.');
            }
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
    #[IsGranted('ROLE_EDIT', message: 'Vous n\'avez pas l\'accès.')]
    public function edit(Request $request, SiteCollection $siteCollection, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(SiteCollectionType::class, $siteCollection);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $siteCollection->setUpdatedAt(new \DateTimeImmutable());
            $entityManager->flush();
            $this->addFlash('success', "Site de collecte a bien été modifié");

            return $this->redirectToRoute('app_site_collection_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('site_collection/edit.html.twig', [
            'site_collection' => $siteCollection,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_site_collection_delete', methods: ['POST'])]
    #[IsGranted('ROLE_DELETE', message: 'Vous n\'avez pas l\'accès.')]
    public function delete(Request $request, SiteCollection $siteCollection, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$siteCollection->getId(), $request->getPayload()->get('_token'))) {
            $entityManager->remove($siteCollection);
            $entityManager->flush();
            $this->addFlash('success', "Site de collecte a bien été supprimée");
        }

        return $this->redirectToRoute('app_site_collection_index', [], Response::HTTP_SEE_OTHER);
    }
}
