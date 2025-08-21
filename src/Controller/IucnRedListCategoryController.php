<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use App\Form\ExportType;
use App\Form\ImportCsvType;
use App\Service\ExportService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Entity\IucnRedListCategory;
use App\Form\IucnRedListCategoryType;
use App\Repository\IucnRedListCategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/user/iucn/red/list/category')]
// #[IsGranted('ROLE_COLLECTOR', message: 'Vous n\'avez pas l\'accès.')]
class IucnRedListCategoryController extends AbstractController
{
    public function __construct(private readonly TranslatorInterface $translator) {}

    #[Route('/', name: 'app_iucn_red_list_category_index', methods: ['GET', 'POST'])]
    public function index(ExportService $exportService, Request $request, EntityManagerInterface $entityManager, IucnRedListCategoryRepository $iucnRedListCategoryRepository): Response
    {
        $form = $this->createForm(ImportCsvType::class);
        $form->handleRequest($request);
    
        $formExport = $this->createForm(ExportType::class);
        $formExport->handleRequest($request);

        if ($formExport->isSubmitted() && $formExport->isValid()) {
            if (!$this->isGranted('ROLE_EXPORT')) {
                $this->addFlash('warning', $this->translator->trans('export_permission'));
                return $this->redirectToRoute('app_iucn_red_list_category_index');
            }
            $columnNames = ['Libellé', 'Créé le', 'Dernière mise à jour le'];
            $iucnRedListCategories = $iucnRedListCategoryRepository->findAll();

            $data = [];
            foreach ($iucnRedListCategories as $iucnRedListCategory) {
                $createdAt = $iucnRedListCategory->getCreatedAt();
                $updatedAt = $iucnRedListCategory->getUpdatedAt();

                $data[] = [
                    $iucnRedListCategory->getLabel(),
                    $createdAt ? $createdAt->format('d-m-Y H:i:s') : null,
                    $updatedAt ? $updatedAt->format('d-m-Y H:i:s') : null,
                ];
            }
            $format = $formExport->get('format')->getData();
            $fileName = sprintf("Export des catégorie de la liste rouge de l'UICN %s", date('d-m-Y_His'));
    
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
                    return $this->redirectToRoute('app_iucn_red_list_category_index');
                }

                $rows = array_filter(array_map(function($row) {
                    return str_getcsv($row, ';');
                }, explode("\n", $csvData)), function($row) {
                    return !empty(array_filter($row)); // Supprime les lignes vides
                }); 
    
                $headers = array_shift($rows); // Enlever la première ligne qui contient les en-têtes
    
                return $this->render('iucn_red_list_category/index.html.twig', [
                    'iucn_red_list_categories' => $iucnRedListCategoryRepository->findAll(),
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
            $processedIucnRedListCategories = [];
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
    
                // Fetch or create the iucn_red_list_category entity
                $label = $data['IUCN'] ?? null;
    
                if (empty($label)) {
                    $invalidRows[] = $lineNumber + 2;
                    $invalidCount++;
                    continue; // Ignorez si les champs nécessaires sont vides
                }
    
                // Vérifier si la famille a déjà été traitée dans ce fichier
                if (isset($processedIucnRedListCategories[$label])) {
                    $invalidRows[] = $lineNumber + 2;
                    $invalidCount++;
                    $processedIucnRedListCategories[$label] = true; // Marquer le site comme traité
                    continue; // Si oui, ignorer cette entrée
                }
    
                $existingFamily = $iucnRedListCategoryRepository->findOneBy(['label' => $label]);
                if ($existingFamily) {
                    $processedIucnRedListCategories[$label] = true;
                    $invalidRows[] = $lineNumber + 2;
                    $invalidCount++; // Compter comme ligne non valide
                    continue; // Si oui, ignorer cette entrée
                }
                
                
                // Créez et persistez une nouvelle famille d'oiseaux
                $iucnRedListCategory = new IucnRedListCategory();
                $iucnRedListCategory->setlabel($label);
                $iucnRedListCategory->setCreatedAt(new \DateTimeImmutable());
    
                $entityManager->persist($iucnRedListCategory);
                $importedCount++; // Incrémentez le compteur
    
                // Marquer cette famille comme traitée
                $processedIucnRedListCategories[$label] = true;
                
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

            return $this->redirectToRoute('app_iucn_red_list_category_index');
        }
        
        return $this->render('iucn_red_list_category/index.html.twig', [
            'iucn_red_list_categories' => $iucnRedListCategoryRepository->findAll(),
            'form' => $form->createView(),
            'formExport' => $formExport->createView(),
        ]);
    }

    #[Route('/new/ajax', name: 'app_iucn_red_list_category_new_ajax', methods: ['POST'])]
    public function newAjax(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $iucnRedListCategory = new IucnRedListCategory();
        $iucnRedListCategoryForm = $this->createForm(IucnRedListCategoryType::class, $iucnRedListCategory);
        $iucnRedListCategoryForm->handleRequest($request);
    
        if ($iucnRedListCategoryForm->isSubmitted() && $iucnRedListCategoryForm->isValid()) {
            $iucnRedListCategory->setCreatedAt(new \DateTimeImmutable());
            $entityManager->persist($iucnRedListCategory);
            $entityManager->flush();
    
            return new JsonResponse([
                'success' => true,
                'iucnRedListCategory' => [
                    'id' => $iucnRedListCategory->getId(),
                    'label' => $iucnRedListCategory->getLabel()
                ]
            ]);
        }
    
        return new JsonResponse([
            'success' => false,
            'errors' => (string) $iucnRedListCategoryForm->getErrors(true, false)
        ]);
    }
    
    
    #[Route('/list', name: 'app_iucn_red_list_category_list', methods: ['GET'])]
    public function list(EntityManagerInterface $entityManager): JsonResponse
    {
        $iucnRedListCategorys = $entityManager->getRepository(IucnRedListCategory::class)->findAll();
        $data = [];

        foreach ($iucnRedListCategorys as $iucnRedListCategory) {
            $data[] = ['id' => $iucnRedListCategory->getId(), 'label' => $iucnRedListCategory->getLabel()];
        }

        return new JsonResponse(['iucnRedListCategorys' => $data]);
    }

    #[Route('/new', name: 'app_iucn_red_list_category_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $iucnRedListCategory = new IucnRedListCategory();
        $form = $this->createForm(IucnRedListCategoryType::class, $iucnRedListCategory);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                try {
                    $iucnRedListCategory->setCreatedAt(new \DateTimeImmutable());
                    $entityManager->persist($iucnRedListCategory);
                    $entityManager->flush();
                    $this->addFlash('success', $this->translator->trans('iucn.msg.created_success'));

                    return $this->redirectToRoute('app_iucn_red_list_category_index', [], Response::HTTP_SEE_OTHER);
                } catch (\Exception $e) {
                    $this->addFlash('error', $e->getMessage());
                    return $this->redirectToRoute('app_iucn_red_list_category_new', [], Response::HTTP_SEE_OTHER);
                }
            } else {
                $this->addFlash('error', $this->translator->trans('iucn.msg.created_error'));
            }
        }

        return $this->render('iucn_red_list_category/new.html.twig', [
            'iucn_red_list_category' => $iucnRedListCategory,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_iucn_red_list_category_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, IucnRedListCategory $iucnRedListCategory, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(IucnRedListCategoryType::class, $iucnRedListCategory);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                try {
                    $iucnRedListCategory->setUpdatedAt(new \DateTimeImmutable());
                    $entityManager->flush();
                    $this->addFlash('success', $this->translator->trans('iucn.msg.updated_success'));
        
                    return $this->redirectToRoute('app_iucn_red_list_category_index', [], Response::HTTP_SEE_OTHER);
                } catch (\Exception $e) {
                    $this->addFlash('error', $e->getMessage());
                    return $this->redirectToRoute('app_iucn_red_list_category_edit', ['id' => $iucnRedListCategory->getId()], Response::HTTP_SEE_OTHER);}
            } else {
                $this->addFlash('error', $this->translator->trans('iucn.msg.updated_error'));
            }
        }

        return $this->render('iucn_red_list_category/edit.html.twig', [
            'iucn_red_list_category' => $iucnRedListCategory,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_iucn_red_list_category_delete', methods: ['POST'])]
    public function delete(Request $request, IucnRedListCategory $iucnRedListCategory, EntityManagerInterface $entityManager): Response
    {
        
        try {
            if ($this->isCsrfTokenValid('delete'.$iucnRedListCategory->getId(), $request->getPayload()->get('_token'))) {
                $entityManager->remove($iucnRedListCategory);
                $entityManager->flush();
            }
        } catch (\Exception $e) {
            $this->addFlash('error', $e->getMessage());
        }
        return $this->redirectToRoute('app_iucn_red_list_category_index', [], Response::HTTP_SEE_OTHER);
    }
}
