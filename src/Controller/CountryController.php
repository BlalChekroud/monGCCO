<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use App\Form\ImportCsvType;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Entity\Country;
use App\Form\CountryType;
use App\Repository\CountryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/user/country')]
// #[IsGranted('ROLE_COLLECTOR', message: 'Vous n\'avez pas l\'accès.')]
class CountryController extends AbstractController
{
    #[Route('/', name: 'app_country_index', methods: ['GET', 'POST'])]
    public function index(CountryRepository $countryRepository, Request $request, EntityManagerInterface $entityManager): Response
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

                // Convertir l'encodage si nécessaire
                if (!mb_check_encoding($csvData, 'UTF-8')) {
                    $csvData = mb_convert_encoding($csvData, 'UTF-8', 'ISO-8859-1'); // Changez 'ISO-8859-1' si besoin
                }

                // Vérifiez si la conversion a réussi
                if (!mb_check_encoding( $csvData, 'UTF-8')) {
                    $this->addFlash('error', 'Le fichier CSV contient des caractères non valides. Veuillez vérifier l\'encodage du fichier.');
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
    
            try {
                $entityManager->flush();
                $this->addFlash('success', "$importedCount pays ont été importés avec succès.");
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de l\'importation : ' . $e->getMessage());
            }
            
            if ($invalidCount > 0) {
                $this->addFlash('error', "$invalidCount lignes n'ont pas pu être importées. Numéros des lignes : " . implode(', ', $invalidRows));
            }

            return $this->redirectToRoute('app_country_index');
        }
    
        return $this->render('country/index.html.twig', [
            'countries' => $countryRepository->findAll(),
            'form' => $form->createView(),
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
                // Ajout de vérifications supplémentaires avant de persister l'entité
                if (empty($country->getName()) || empty($country->getIso2())) {
                    $this->addFlash('error', 'Le nom et le code ISO2 doivent être remplis.');
                    return $this->redirectToRoute('app_country_new');
                }
                $country->setCreatedAt(new \DateTimeImmutable());
                $entityManager->persist($country);
                $entityManager->flush();
                $this->addFlash('success', "Le pays a bien été crée");
                return $this->redirectToRoute('app_country_index', [], Response::HTTP_SEE_OTHER);
            } else {
                $this->addFlash('error',"Une erreur s'est produite lors de la création du pays.");
            }
        }

        return $this->render('country/new.html.twig', [
            'country' => $country,
            'form' => $form,
        ]);
    }

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
                $country->setUpdatedAt(new \DateTimeImmutable());
                $entityManager->flush();
                $this->addFlash('success', "Le pays a bien été modifié");
                return $this->redirectToRoute('app_country_index', [], Response::HTTP_SEE_OTHER);
            } else {
                $this->addFlash('error',"Une erreur s'est produite lors de la modification du pays.");
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
        if ($this->isCsrfTokenValid('delete'.$country->getId(), $request->getPayload()->get('_token'))) {
            $entityManager->remove($country);
            $entityManager->flush();
            $this->addFlash('success', "Le pays a bien été supprimé");
        } else {
            $this->addFlash('error', "Une erreur est survenue");
        }

        return $this->redirectToRoute('app_country_index', [], Response::HTTP_SEE_OTHER);
    }
}
