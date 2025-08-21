<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use App\Form\ExportType;
use App\Form\ImportCsvType;
use App\Service\ExportService;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Entity\BirdLifeTaxTreat;
use App\Form\BirdLifeTaxTreatType;
use App\Repository\BirdLifeTaxTreatRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/user/bird/life/tax/treat')]
class BirdLifeTaxTreatController extends AbstractController
{
    public function __construct(private readonly TranslatorInterface $translator) {}

    #[Route('/', name: 'app_bird_life_tax_treat_index', methods: ['GET', 'POST'])]
    public function index(ExportService $exportService, Request $request, EntityManagerInterface $entityManager, BirdLifeTaxTreatRepository $birdLifeTaxTreatRepository): Response
    {
        $form = $this->createForm(ImportCsvType::class);
        $form->handleRequest($request);
    
        $formExport = $this->createForm(ExportType::class);
        $formExport->handleRequest($request);

        if ($formExport->isSubmitted() && $formExport->isValid()) {
            if (!$this->isGranted('ROLE_EXPORT')) {
                $this->addFlash('warning', $this->translator->trans('export_permission'));
                return $this->redirectToRoute('app_bird_life_tax_treat_index');
            }
            $columnNames = ['Libellé', 'Créé le', 'Dernière mise à jour le'];
            $birdLifeTaxTreats = $birdLifeTaxTreatRepository->findAll();

            $data = [];
            foreach ($birdLifeTaxTreats as $birdLifeTaxTreat) {
                $createdAt = $birdLifeTaxTreat->getCreatedAt();
                $updatedAt = $birdLifeTaxTreat->getUpdatedAt();

                $data[] = [
                    $birdLifeTaxTreat->getLabel(),
                    $createdAt ? $createdAt->format('d-m-Y H:i:s') : null,
                    $updatedAt ? $updatedAt->format('d-m-Y H:i:s') : null,
                ];
            }
            $format = $formExport->get('format')->getData();
            $fileName = sprintf("Export des données de marée %s", date('d-m-Y_His'));
    
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
                if (!mb_check_encoding($csvData, 'UTF-8')) {
                    $this->addFlash('error', $this->translator->trans('error.invalid_csv'));
                    return $this->redirectToRoute('app_bird_life_tax_treat_index');
                }

                $rows = array_filter(array_map(function($row) {
                    return str_getcsv($row, ';');
                }, explode("\n", $csvData)), function($row) {
                    return !empty(array_filter($row)); // Supprime les lignes vides
                }); 
    
                $headers = array_shift($rows); // Enlever la première ligne qui contient les en-têtes
    
                return $this->render('bird_life_tax_treat/index.html.twig', [
                    'bird_life_tax_treats' => $birdLifeTaxTreatRepository->findAll(),
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
    
            $importedCount = 0; // Compteur de familles d'oiseaux importées
            $invalidCount = 0; // Compteur de lignes non importées
            $processedbirdLifeTaxTreats = [];
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
    
                // Fetch or create the birdLifeTaxTreat entity
                $label = $data['TAX TREAT'] ?? null;
    
                if (empty($label)) {
                    $invalidRows[] = $lineNumber + 2;
                    $invalidCount++;
                    continue; // Ignorez si les champs nécessaires sont vides
                }
    
                // Vérifier si la famille a déjà été traitée dans ce fichier
                if (isset($processedbirdLifeTaxTreats[$label])) {
                    $invalidRows[] = $lineNumber + 2;
                    $invalidCount++;
                    $processedbirdLifeTaxTreats[$label] = true; // Marquer le site comme traité
                    continue; // Si oui, ignorer cette entrée
                }
    
                $existingFamily = $birdLifeTaxTreatRepository->findOneBy(['label' => $label]);
                if ($existingFamily) {
                    $processedbirdLifeTaxTreats[$label] = true;
                    $invalidRows[] = $lineNumber + 2;
                    $invalidCount++; // Compter comme ligne non valide
                    continue; // Si oui, ignorer cette entrée
                }
                
                
                // Créez et persistez une nouvelle famille d'oiseaux
                $birdLifeTaxTreat = new BirdLifeTaxTreat();
                $birdLifeTaxTreat->setlabel($label);
                $birdLifeTaxTreat->setCreatedAt(new \DateTimeImmutable());
    
                $entityManager->persist($birdLifeTaxTreat);
                $importedCount++; // Incrémentez le compteur
    
                // Marquer cette famille comme traitée
                $processedbirdLifeTaxTreats[$label] = true;
                
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

            return $this->redirectToRoute('app_bird_life_tax_treat_index');
        }
        
        return $this->render('bird_life_tax_treat/index.html.twig', [
            'bird_life_tax_treats' => $birdLifeTaxTreatRepository->findAll(),
            'form' => $form->createView(),
            'formExport' => $formExport->createView(),
        ]);
    }

    #[Route('/new/ajax', name: 'app_bird_life_tax_treat_new_ajax', methods: ['POST'])]
    #[IsGranted('ROLE_CREAT', message: 'Vous n\'avez pas l\'accès.')]
    public function newAjax(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $birdLifeTaxTreat = new BirdLifeTaxTreat();
        $birdLifeTaxTreatForm = $this->createForm(BirdLifeTaxTreatType::class, $birdLifeTaxTreat);
        $birdLifeTaxTreatForm->handleRequest($request);

        if ($birdLifeTaxTreatForm->isSubmitted() && $birdLifeTaxTreatForm->isValid()) {
            $birdLifeTaxTreat->setCreatedAt(new \DateTimeImmutable());
            $entityManager->persist($birdLifeTaxTreat);
            $entityManager->flush();

            return new JsonResponse(['success' => true, 'birdLifeTaxTreat' => ['id' => $birdLifeTaxTreat->getId(), 'label' => $birdLifeTaxTreat->getLabel()]]);
        }

        return new JsonResponse(['success' => false, 'errors' => (string) $birdLifeTaxTreatForm->getErrors(true, false)]);
    }

    
    #[Route('/list', name: 'app_bird_life_tax_treat_list', methods: ['GET'])]
    public function list(EntityManagerInterface $entityManager): JsonResponse
    {
        $birdLifeTaxTreats = $entityManager->getRepository(BirdLifeTaxTreat::class)->findAll();
        $data = [];

        foreach ($birdLifeTaxTreats as $birdLifeTaxTreat) {
            $data[] = ['id' => $birdLifeTaxTreat->getId(), 'label' => $birdLifeTaxTreat->getLabel()];
        }

        return new JsonResponse(['taxTreatments' => $data]);
    }

    #[Route('/new', name: 'app_bird_life_tax_treat_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_CREAT', message: 'Vous n\'avez pas l\'accès.')]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $birdLifeTaxTreat = new BirdLifeTaxTreat();
        $form = $this->createForm(BirdLifeTaxTreatType::class, $birdLifeTaxTreat);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                try {
                    $birdLifeTaxTreat->setCreatedAt(new \DateTimeImmutable());
                    $entityManager->persist($birdLifeTaxTreat);
                    $entityManager->flush();
                    $this->addFlash('success', $this->translator->trans('birdLifeTaxTreat.msg.created_success'));

                    return $this->redirectToRoute('app_bird_life_tax_treat_index', [], Response::HTTP_SEE_OTHER);
                } catch (\Exception $e) {
                    $this->addFlash('error', $e->getMessage());
                    return $this->redirectToRoute('app_bird_life_tax_treat_new', [], Response::HTTP_SEE_OTHER);
                }
            } else {
                $this->addFlash('error', $this->translator->trans('birdLifeTaxTreat.msg.created_error'));
            }
        }

        return $this->render('bird_life_tax_treat/new.html.twig', [
            'bird_life_tax_treat' => $birdLifeTaxTreat,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_bird_life_tax_treat_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_EDIT', message: 'Vous n\'avez pas l\'accès.')]
    public function edit(Request $request, BirdLifeTaxTreat $birdLifeTaxTreat, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(BirdLifeTaxTreatType::class, $birdLifeTaxTreat);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                try {
                    $birdLifeTaxTreat->setUpdatedAt(new \DateTimeImmutable());
                    $entityManager->flush();
                    $this->addFlash('success', $this->translator->trans('birdLifeTaxTreat.msg.updated_success'));
        
                    return $this->redirectToRoute('app_bird_life_tax_treat_index', [], Response::HTTP_SEE_OTHER);
                } catch (\Exception $e) {
                    $this->addFlash('error', $e->getMessage());
                    return $this->redirectToRoute('app_bird_life_tax_treat_edit', ['id' => $birdLifeTaxTreat->getId()], Response::HTTP_SEE_OTHER);}
            } else {
                $this->addFlash('error', $this->translator->trans('birdLifeTaxTreat.msg.updated_error'));
            }
        }

        return $this->render('bird_life_tax_treat/edit.html.twig', [
            'bird_life_tax_treat' => $birdLifeTaxTreat,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_bird_life_tax_treat_delete', methods: ['POST'])]
    #[IsGranted('ROLE_DELETE', message: 'Vous n\'avez pas l\'accès.')]
    public function delete(Request $request, BirdLifeTaxTreat $birdLifeTaxTreat, EntityManagerInterface $entityManager): Response
    {
        try {
            if ($this->isCsrfTokenValid('delete'.$birdLifeTaxTreat->getId(), $request->getPayload()->get('_token'))) {
                $entityManager->remove($birdLifeTaxTreat);
                $entityManager->flush();
                $this->addFlash('success', $this->translator->trans('birdLifeTaxTreat.msg.deleted_success'));
            } else {
                $this->addFlash('error',$this->translator->trans('birdLifeTaxTreat.msg.deleted_error'));
                return $this->redirectToRoute('app_bird_life_tax_treat_index', [], Response::HTTP_SEE_OTHER);
            }
        } catch (\Exception $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('app_bird_life_tax_treat_index', [], Response::HTTP_SEE_OTHER);
    }
}
