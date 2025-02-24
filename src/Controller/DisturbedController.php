<?php

namespace App\Controller;

use App\Form\ExportType;
use App\Form\ImportCsvType;
use App\Service\ExportService;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Entity\Disturbed;
use App\Form\DisturbedType;
use App\Repository\DisturbedRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/user/disturbed')]
// #[IsGranted('ROLE_COLLECTOR', message: 'Vous n\'avez pas l\'accès.')]
class DisturbedController extends AbstractController
{
    public function __construct(private readonly TranslatorInterface $translator) {}

    #[Route('/', name: 'app_disturbed_index', methods: ['GET', 'POST'])]
    public function index(ExportService $exportService, Request $request, EntityManagerInterface $entityManager, DisturbedRepository $disturbedRepository): Response
    {
        $form = $this->createForm(ImportCsvType::class);
        $form->handleRequest($request);
    
        $formExport = $this->createForm(ExportType::class);
        $formExport->handleRequest($request);

        if ($formExport->isSubmitted() && $formExport->isValid()) {
            if (!$this->isGranted('ROLE_EXPORT')) {
                $this->addFlash('warning', $this->translator->trans('export_permission'));
                return $this->redirectToRoute('app_disturbed_index');
            }
            $columnNames = ['Libellé', 'Créé le', 'Dernière mise à jour le'];
            $disturbeds = $disturbedRepository->findAll();

            $data = [];
            foreach ($disturbeds as $disturbed) {
                $createdAt = $disturbed->getCreatedAt();
                $updatedAt = $disturbed->getUpdatedAt();

                $data[] = [
                    $disturbed->getLabel(),
                    $createdAt ? $createdAt->format('d-m-Y H:i:s') : null,
                    $updatedAt ? $updatedAt->format('d-m-Y H:i:s') : null,
                ];
            }
            $format = $formExport->get('format')->getData();
            $fileName = sprintf("Export des données de perturbation %s", date('d-m-Y_His'));
    
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
                    return $this->redirectToRoute('app_disturbed_index');
                }

                $rows = array_filter(array_map(function($row) {
                    return str_getcsv($row, ';');
                }, explode("\n", $csvData)), function($row) {
                    return !empty(array_filter($row)); // Supprime les lignes vides
                }); 
    
                $headers = array_shift($rows); // Enlever la première ligne qui contient les en-têtes
    
                return $this->render('disturbed/index.html.twig', [
                    'disturbeds' => $disturbedRepository->findAll(),
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
            $processedDisturbeds = [];
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
    
                // Fetch or create the Disturbed entity
                $label = $data['DISTURBED'] ?? null;
    
                if (empty($label)) {
                    $invalidRows[] = $lineNumber + 2;
                    $invalidCount++;
                    continue; // Ignorez si les champs nécessaires sont vides
                }
    
                // Vérifier si la famille a déjà été traitée dans ce fichier
                if (isset($processedDisturbeds[$label])) {
                    $invalidRows[] = $lineNumber + 2;
                    $invalidCount++;
                    $processedDisturbeds[$label] = true; // Marquer le site comme traité
                    continue; // Si oui, ignorer cette entrée
                }
    
                $existingFamily = $disturbedRepository->findOneBy(['label' => $label]);
                if ($existingFamily) {
                    $processedDisturbeds[$label] = true;
                    $invalidRows[] = $lineNumber + 2;
                    $invalidCount++; // Compter comme ligne non valide
                    continue; // Si oui, ignorer cette entrée
                }
                
                
                // Créez et persistez une nouvelle famille d'oiseaux
                $disturbed = new Disturbed();
                $disturbed->setlabel($label);
                $disturbed->setCreatedAt(new \DateTimeImmutable());
    
                $entityManager->persist($disturbed);
                $importedCount++; // Incrémentez le compteur
    
                // Marquer cette famille comme traitée
                $processedDisturbeds[$label] = true;
                
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

            return $this->redirectToRoute('app_disturbed_index');
        }
        
        return $this->render('disturbed/index.html.twig', [
            'disturbeds' => $disturbedRepository->findAll(),
            'form' => $form->createView(),
            'formExport' => $formExport->createView(),
        ]);
    }

    #[Route('/new', name: 'app_disturbed_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $disturbed = new Disturbed();
        $form = $this->createForm(DisturbedType::class, $disturbed);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                try {
                    $disturbed->setCreatedAt(new \DateTimeImmutable());
                    $entityManager->persist($disturbed);
                    $entityManager->flush();
                    $this->addFlash('success', $this->translator->trans('conditionDisturbed.msg.created_success'));

                    return $this->redirectToRoute('app_disturbed_index', [], Response::HTTP_SEE_OTHER);
                } catch (\Exception $e) {
                    $this->addFlash('error', $e->getMessage());
                    return $this->redirectToRoute('app_disturbed_new', [], Response::HTTP_SEE_OTHER);
                }
            } else {
                $this->addFlash('error', $this->translator->trans('conditionDisturbed.msg.created_error'));
            }
        }

        return $this->render('disturbed/new.html.twig', [
            'disturbed' => $disturbed,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/edit', name: 'app_disturbed_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Disturbed $disturbed, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(DisturbedType::class, $disturbed);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                try {
                    $disturbed->setUpdatedAt(new \DateTimeImmutable());
                    $entityManager->flush();
                    $this->addFlash('success', $this->translator->trans('conditionDisturbed.msg.updated_success'));
        
                    return $this->redirectToRoute('app_disturbed_index', [], Response::HTTP_SEE_OTHER);
                } catch (\Exception $e) {
                    $this->addFlash('error', $e->getMessage());
                    return $this->redirectToRoute('app_disturbed_edit', ['id' => $disturbed->getId()], Response::HTTP_SEE_OTHER);}
            } else {
                $this->addFlash('error', $this->translator->trans('conditionDisturbed.msg.updated_error'));
            }
        }

        return $this->render('disturbed/edit.html.twig', [
            'disturbed' => $disturbed,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_disturbed_delete', methods: ['POST'])]
    public function delete(Request $request, Disturbed $disturbed, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$disturbed->getId(), $request->getPayload()->get('_token'))) {
            try {
                $entityManager->remove($disturbed);
                $entityManager->flush();
                $this->addFlash('success', $this->translator->trans('conditionDisturbed.msg.deleted_success'));
            } catch (\Exception $e) {
                $this->addFlash('error', $e->getMessage());
                return $this->redirectToRoute('app_disturbed_index', [], Response::HTTP_SEE_OTHER);
            }
        } else {
            $this->addFlash('error',$this->translator->trans('conditionDisturbed.msg.deleted_error'));
        }

        return $this->redirectToRoute('app_disturbed_index', [], Response::HTTP_SEE_OTHER);
    }
}
