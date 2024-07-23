<?php

namespace App\Controller;
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

#[Route('/iucn/red/list/category')]
#[IsGranted('ROLE_COLLECTOR', message: 'Vous n\'avez pas l\'accès.')]
class IucnRedListCategoryController extends AbstractController
{
    #[Route('/', name: 'app_iucn_red_list_category_index', methods: ['GET'])]
    public function index(IucnRedListCategoryRepository $iucnRedListCategoryRepository): Response
    {
        return $this->render('iucn_red_list_category/index.html.twig', [
            'iucn_red_list_categories' => $iucnRedListCategoryRepository->findAll(),
        ]);
    }

    #[Route('/new/ajax', name: 'app_iucn_red_list_category_new_ajax', methods: ['POST'])]
    public function newAjax(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $iucnRedListCategory = new IucnRedListCategory();
        $form = $this->createForm(IucnRedListCategoryType::class, $iucnRedListCategory);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($iucnRedListCategory);
            $entityManager->flush();

            return new JsonResponse(['success' => true, 'iucnRedListCategory' => ['id' => $iucnRedListCategory->getId(), 'label' => $iucnRedListCategory->getLabel()]]);
        }

        return new JsonResponse(['success' => false, 'errors' => (string) $form->getErrors(true, false)]);
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

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($iucnRedListCategory);
            $entityManager->flush();

            return $this->redirectToRoute('app_iucn_red_list_category_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('iucn_red_list_category/new.html.twig', [
            'iucn_red_list_category' => $iucnRedListCategory,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_iucn_red_list_category_show', methods: ['GET'])]
    public function show(IucnRedListCategory $iucnRedListCategory): Response
    {
        return $this->render('iucn_red_list_category/show.html.twig', [
            'iucn_red_list_category' => $iucnRedListCategory,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_iucn_red_list_category_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, IucnRedListCategory $iucnRedListCategory, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(IucnRedListCategoryType::class, $iucnRedListCategory);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_iucn_red_list_category_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('iucn_red_list_category/edit.html.twig', [
            'iucn_red_list_category' => $iucnRedListCategory,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_iucn_red_list_category_delete', methods: ['POST'])]
    public function delete(Request $request, IucnRedListCategory $iucnRedListCategory, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$iucnRedListCategory->getId(), $request->getPayload()->get('_token'))) {
            $entityManager->remove($iucnRedListCategory);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_iucn_red_list_category_index', [], Response::HTTP_SEE_OTHER);
    }
}
