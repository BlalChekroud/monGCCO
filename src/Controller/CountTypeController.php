<?php

namespace App\Controller;

use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Entity\CountType;
use App\Form\CountTypeType;
use App\Repository\CountTypeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/count/type')]
#[IsGranted('ROLE_COLLECTOR', message: 'Vous n\'avez pas l\'accès.')]
class CountTypeController extends AbstractController
{
    #[Route('/', name: 'app_count_type_index', methods: ['GET'])]
    public function index(CountTypeRepository $countTypeRepository): Response
    {
        return $this->render('count_type/index.html.twig', [
            'count_types' => $countTypeRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_count_type_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $countType = new CountType();
        $form = $this->createForm(CountTypeType::class, $countType);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $countType->setCreatedAt(new \DateTimeImmutable());
                $entityManager->persist($countType);
                $entityManager->flush();
                $this->addFlash('success', 'Type de conptage a bien été crée.');
    
                return $this->redirectToRoute('app_count_type_index', [], Response::HTTP_SEE_OTHER);
            } else {
                $this->addFlash('error', 'Une erreur s\'est produite lors de la création de la type de comptage.');
            }
        }

        return $this->render('count_type/new.html.twig', [
            'count_type' => $countType,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_count_type_show', methods: ['GET'])]
    public function show(CountType $countType): Response
    {
        return $this->render('count_type/show.html.twig', [
            'count_type' => $countType,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_count_type_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, CountType $countType, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(CountTypeType::class, $countType);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $countType->setUpdatedAt(new \DateTimeImmutable());
                $entityManager->flush();
                $this->addFlash('success', 'Le type de comptage a bien été modifié');
    
                return $this->redirectToRoute('app_count_type_index', [], Response::HTTP_SEE_OTHER);
            } else {
                $this->addFlash('error', 'Une erreur s\'est produite lors de la modification de latype de comptage.');
            }
        }

        return $this->render('count_type/edit.html.twig', [
            'count_type' => $countType,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_count_type_delete', methods: ['POST'])]
    public function delete(Request $request, CountType $countType, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$countType->getId(), $request->getPayload()->get('_token'))) {
            $entityManager->remove($countType);
            $entityManager->flush();
            $this->addFlash('success', "La type de comptage a bien été supprimée");
        } else {
            $this->addFlash('error','Une erreur s\'est produite lors de la suppression de type de comptage.');
        }

        return $this->redirectToRoute('app_count_type_index', [], Response::HTTP_SEE_OTHER);
    }
}
