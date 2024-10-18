<?php

namespace App\Controller;

use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use App\Form\ImportCsvType;
use Doctrine\ORM\EntityManagerInterface;
use App\Form\BirdFamilyType;
use App\Entity\BirdFamily;
use Symfony\Component\HttpFoundation\Request;
use App\Repository\BirdFamilyRepository;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/user/bird/family')]
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
                if (!mb_check_encoding($csvData, 'UTF-8')) {
                    $this->addFlash('error', 'Le fichier CSV contient des caractères non valides. Veuillez vérifier l\'encodage du fichier.');
                    return $this->redirectToRoute('app_bird_family_index');
                }

                $rows = array_filter(array_map(function($row) {
                    return str_getcsv($row, ';');
                }, explode("\n", $csvData)), function($row) {
                    return !empty(array_filter($row)); // Supprime les lignes vides
                }); 
    
                $headers = array_shift($rows); // Enlever la première ligne qui contient les en-têtes
    
                return $this->render('bird_family/index.html.twig', [
                    'bird_families' => $birdFamilyRepository->findAll(),
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
    
            $importedCount = 0; // Compteur de familles d'oiseaux importées
            $invalidCount = 0; // Compteur de lignes non importées
            $processedFamilies = []; // Tableau pour suivre les familles déjà traitées
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
    
                // Fetch or create the Birdfamily entity
                $familyName = $data['Family name'] ?? null;
                $subFamily = $data['Subfamily'] ?? null;
                $family = $data['Family'] ?? null;
                $ordre = $data['Ordre'] ?? null;
                $tribe = $data['Tribe'] ?? null;
    
                if (empty($familyName) || empty($family) || empty($ordre)) {
                    $invalidRows[] = $lineNumber + 2;
                    $invalidCount++;
                    continue; // Ignorez si les champs nécessaires sont vides
                }
    
                // Vérifier si la famille a déjà été traitée dans ce fichier
                if (isset($processedFamilies[$familyName])) {
                    $invalidRows[] = $lineNumber + 2;
                    $invalidCount++;
                    $processedFamilies[$familyName] = true; // Marquer le site comme traité
                    continue; // Si oui, ignorer cette entrée
                }
    
                $existingFamily = $birdFamilyRepository->findOneBy(['familyName' => $familyName, 'family' => $family, 'ordre' => $ordre, 'subFamily' => $subFamily, 'tribe' => $tribe]);
                if ($existingFamily) {
                    $processedFamilies[$familyName] = true;
                    $invalidRows[] = $lineNumber + 2;
                    $invalidCount++; // Compter comme ligne non valide
                    continue; // Si oui, ignorer cette entrée
                }
                
                
                // Créez et persistez une nouvelle famille d'oiseaux
                $birdFamily = new BirdFamily();
                $birdFamily->setOrdre($ordre);
                $birdFamily->setFamilyName($familyName);
                $birdFamily->setFamily( $family);
                $birdFamily->setSubFamily($data['Subfamily'] ?? null);
                $birdFamily->setTribe($data['Tribe'] ?? null);
                $birdFamily->setCreatedAt(new \DateTimeImmutable());
    
                $entityManager->persist($birdFamily);
                $importedCount++; // Incrémentez le compteur
    
                // Marquer cette famille comme traitée
                $processedFamilies[$familyName] = true;
                
            }

            // Affichez le nombre de lignes importées et non importées    
            try {
                $entityManager->flush();
                $this->addFlash('success', "$importedCount familles d'oiseaux ont été importées avec succès. $invalidCount lignes n'ont pas pu être importées.");
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de l\'importation : ' . $e->getMessage());
            }
            
            if ($invalidCount > 0) {
                $this->addFlash('error', "$invalidCount lignes n'ont pas pu être importées. Numéros des lignes : " . implode(', ', $invalidRows));
            }

            return $this->redirectToRoute('app_bird_family_index');
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
            $birdFamily->setCreatedAt(new \DateTimeImmutable());
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
            $birdFamily->setUpdatedAt(new \DateTimeImmutable());
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
