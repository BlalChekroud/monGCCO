<?php

namespace App\Controller;

use App\Form\ExportType;
use App\Form\ImportCsvType;
use App\Service\ExportService;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Entity\Quality;
use App\Form\QualityType;
use App\Repository\QualityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/user/quality')]
// #[IsGranted('ROLE_COLLECTOR', message: 'Vous n\'avez pas l\'accès.')]
class QualityController extends AbstractController
{
    public function __construct(private readonly TranslatorInterface $translator) {}

    #[Route('/', name: 'app_quality_index', methods: ['GET', 'POST'])]
    public function index(ExportService $exportService, Request $request, EntityManagerInterface $entityManager, QualityRepository $qualityRepository): Response
    {
        $form = $this->createForm(ImportCsvType::class);
        $form->handleRequest($request);
    
        $formExport = $this->createForm(ExportType::class);
        $formExport->handleRequest($request);

        if ($formExport->isSubmitted() && $formExport->isValid()) {
            if (!$this->isGranted('ROLE_EXPORT')) {
                $this->addFlash('warning', $this->translator->trans('export_permission'));
                return $this->redirectToRoute('app_quality_index');
            }
            $columnNames = ['Libellé', 'Créé le', 'Dernière mise à jour le'];
            $qualities = $qualityRepository->findAll();

            $data = [];
            foreach ($qualities as $quality) {
                $createdAt = $quality->getCreatedAt();
                $updatedAt = $quality->getUpdatedAt();

                $data[] = [
                    $quality->getLabel(),
                    $createdAt ? $createdAt->format('d-m-Y H:i:s') : null,
                    $updatedAt ? $updatedAt->format('d-m-Y H:i:s') : null,
                ];
            }
            $format = $formExport->get('format')->getData();
            $fileName = sprintf("Export des qualités de collecte %s", date('d-m-Y_His'));
    
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
                    return $this->redirectToRoute('app_quality_index');
                }

                $rows = array_filter(array_map(function($row) {
                    return str_getcsv($row, ';');
                }, explode("\n", $csvData)), function($row) {
                    return !empty(array_filter($row)); // Supprime les lignes vides
                }); 
    
                $headers = array_shift($rows); // Enlever la première ligne qui contient les en-têtes
    
                return $this->render('quality/index.html.twig', [
                    'qualities' => $qualityRepository->findAll(),
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
            $processedQualities = [];
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
    
                // Fetch or create the quality entity
                $label = $data['QUALITY'] ?? null;
    
                if (empty($label)) {
                    $invalidRows[] = $lineNumber + 2;
                    $invalidCount++;
                    continue; // Ignorez si les champs nécessaires sont vides
                }
    
                // Vérifier si la famille a déjà été traitée dans ce fichier
                if (isset($processedQualities[$label])) {
                    $invalidRows[] = $lineNumber + 2;
                    $invalidCount++;
                    $processedQualities[$label] = true; // Marquer le site comme traité
                    continue; // Si oui, ignorer cette entrée
                }
    
                $existingFamily = $qualityRepository->findOneBy(['label' => $label]);
                if ($existingFamily) {
                    $processedQualities[$label] = true;
                    $invalidRows[] = $lineNumber + 2;
                    $invalidCount++; // Compter comme ligne non valide
                    continue; // Si oui, ignorer cette entrée
                }
                
                
                // Créez et persistez une nouvelle famille d'oiseaux
                $quality = new Quality();
                $quality->setlabel($label);
                $quality->setCreatedAt(new \DateTimeImmutable());
    
                $entityManager->persist($quality);
                $importedCount++; // Incrémentez le compteur
    
                // Marquer cette famille comme traitée
                $processedQualities[$label] = true;
                
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

            return $this->redirectToRoute('app_quality_index');
        }
        
        return $this->render('quality/index.html.twig', [
            'qualities' => $qualityRepository->findAll(),
            'form' => $form->createView(),
            'formExport' => $formExport->createView(),
        ]);
    }

    #[Route('/new', name: 'app_quality_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $quality = new Quality();
        $form = $this->createForm(QualityType::class, $quality);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                try {
                    $quality->setCreatedAt(new \DateTimeImmutable());
                    $entityManager->persist($quality);
                    $entityManager->flush();
                    $this->addFlash('success', $this->translator->trans('collectQuality.msg.created_success'));

                    return $this->redirectToRoute('app_quality_index', [], Response::HTTP_SEE_OTHER);
                } catch (\Exception $e) {
                    $this->addFlash('error', $e->getMessage());
                    return $this->redirectToRoute('app_quality_new', [], Response::HTTP_SEE_OTHER);
                }
            } else {
                $this->addFlash('error', $this->translator->trans('collectQuality.msg.created_error'));
            }
        }

        return $this->render('quality/new.html.twig', [
            'quality' => $quality,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/edit', name: 'app_quality_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Quality $quality, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(QualityType::class, $quality);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                try {
                    $quality->setUpdatedAt(new \DateTimeImmutable());
                    $entityManager->flush();
                    $this->addFlash('success', $this->translator->trans('collectQuality.msg.updated_success'));
        
                    return $this->redirectToRoute('app_quality_index', [], Response::HTTP_SEE_OTHER);
                } catch (\Exception $e) {
                    $this->addFlash('error', $e->getMessage());
                    return $this->redirectToRoute('app_quality_edit', ['id' => $quality->getId()], Response::HTTP_SEE_OTHER);}
            } else {
                $this->addFlash('error', $this->translator->trans('collectQuality.msg.updated_error'));
            }
        }

        return $this->render('quality/edit.html.twig', [
            'quality' => $quality,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_quality_delete', methods: ['POST'])]
    public function delete(Request $request, Quality $quality, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$quality->getId(), $request->getPayload()->get('_token'))) {
            try {
                $entityManager->remove($quality);
                $entityManager->flush();
                $this->addFlash('success', $this->translator->trans('collectQuality.msg.deleted_success'));
            } catch (\Exception $e) {
                $this->addFlash('error', $e->getMessage());
                return $this->redirectToRoute('app_quality_index', [], Response::HTTP_SEE_OTHER);
            }
        } else {
            $this->addFlash('error',$this->translator->trans('collectQuality.msg.deleted_error'));
        } 

        return $this->redirectToRoute('app_quality_index', [], Response::HTTP_SEE_OTHER);
    }
}
