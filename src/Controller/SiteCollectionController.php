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
                $rows = array_map(function($row) {
                    return str_getcsv($row, ';'); // Assurez-vous que le séparateur correspond au fichier CSV
                }, explode("\n", $csvData));
                
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
    
            foreach ($rows as $row) {
                // Ignorer les lignes vides
                if (empty(array_filter($row))) {
                    continue;
                }
    
                // Vérifiez si la ligne a le même nombre de colonnes que les en-têtes
                if (count($row) !== count($headers)) {
                    $this->addFlash('error', 'Ligne incorrecte : ' . implode(', ', $row));
                    continue; // Ignorez cette ligne
                }
        
                $data = array_combine($headers, $row);
        
                if ($data === false) {
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
                // $parentSite = $data['parentSite'] ?? null;
                $cityName = $data['city'] ?? null;
                $lat = $data['lat'] ?? null;
                $lng = $data['lng'] ?? null;
                $regionName = $data['region'] ?? null;
    
                if (empty($siteName) || empty($siteCode) || empty($latDepart) || empty($longDepart) || empty($latFin) || empty($longFin)  || empty($cityName)) {
                    continue;
                }
    
                // Vérifier si le site a déjà été traitée dans ce fichier
                if (isset($processedSites[$siteName])) {
                    continue; // Si oui, ignorer cette entrée
                }
    
                // Vérifier si le site existe déjà dans la base de données
                $existingSite = $siteCollectionRepository->findOneBy(['siteName' => $siteName]);
                if ($existingSite) {
                    // $this->addFlash('info', 'Le site ' . $siteName . ' existe déjà dans la base de données.');
                    $processedSites[$siteName] = true;
                    continue; // Si oui, ignorer cette entrée
                }
    
                // Récupérer ou créer la ville associée
                $cityRepository = $entityManager->getRepository(City::class);
                $existingCity = $cityRepository->findOneBy(['name' => $cityName]);

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
                    $city->setRegion($regionName);

                    $entityManager->persist($city);
                    $existingCity = $city; // Réassigner pour utiliser l'objet persisté
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
                // $site->setParentSite($parentSite);

                $site->setCreatedAt(new \DateTimeImmutable());
    
                // Associer le site au ville
                $site->setCity($existingCity);
    
                $entityManager->persist($site);
                $importedCount++; // Incrémentez le compteur
    
                // Marquer cette ville comme traitée
                $processedSites[$siteName] = true;
            }
    
            $entityManager->flush();
    
            $this->addFlash('success', $importedCount . ' sites de collecte ont été importées avec succès.');
    
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
