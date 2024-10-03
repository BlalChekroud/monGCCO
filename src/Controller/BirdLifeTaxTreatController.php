<?php

namespace App\Controller;

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

#[Route('/bird/life/tax/treat')]
class BirdLifeTaxTreatController extends AbstractController
{
    #[Route('/', name: 'app_bird_life_tax_treat_index', methods: ['GET'])]
    public function index(BirdLifeTaxTreatRepository $birdLifeTaxTreatRepository): Response
    {
        return $this->render('bird_life_tax_treat/index.html.twig', [
            'bird_life_tax_treats' => $birdLifeTaxTreatRepository->findAll(),
        ]);
    }

    #[Route('/new/ajax', name: 'app_bird_life_tax_treat_new_ajax', methods: ['POST'])]
    #[IsGranted('ROLE_CREAT', message: 'Vous n\'avez pas l\'accès.')]
    public function newAjax(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $birdLifeTaxTreat = new BirdLifeTaxTreat();
        $form = $this->createForm(BirdLifeTaxTreatType::class, $birdLifeTaxTreat);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($birdLifeTaxTreat);
            $entityManager->flush();

            return new JsonResponse(['success' => true, 'birdLifeTaxTreat' => ['id' => $birdLifeTaxTreat->getId(), 'label' => $birdLifeTaxTreat->getLabel()]]);
        }

        return new JsonResponse(['success' => false, 'errors' => (string) $form->getErrors(true, false)]);
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

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($birdLifeTaxTreat);
            $entityManager->flush();

            return $this->redirectToRoute('app_bird_life_tax_treat_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('bird_life_tax_treat/new.html.twig', [
            'bird_life_tax_treat' => $birdLifeTaxTreat,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_bird_life_tax_treat_show', methods: ['GET'])]
    public function show(BirdLifeTaxTreat $birdLifeTaxTreat): Response
    {
        return $this->render('bird_life_tax_treat/show.html.twig', [
            'bird_life_tax_treat' => $birdLifeTaxTreat,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_bird_life_tax_treat_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_EDIT', message: 'Vous n\'avez pas l\'accès.')]
    public function edit(Request $request, BirdLifeTaxTreat $birdLifeTaxTreat, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(BirdLifeTaxTreatType::class, $birdLifeTaxTreat);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_bird_life_tax_treat_index', [], Response::HTTP_SEE_OTHER);
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
        if ($this->isCsrfTokenValid('delete'.$birdLifeTaxTreat->getId(), $request->getPayload()->get('_token'))) {
            $entityManager->remove($birdLifeTaxTreat);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_bird_life_tax_treat_index', [], Response::HTTP_SEE_OTHER);
    }
}
