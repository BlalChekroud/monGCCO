<?php

namespace App\Controller;

use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Entity\Water;
use App\Form\WaterType;
use App\Repository\WaterRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/water')]
#[IsGranted('ROLE_COLLECTOR', message: 'Vous n\'avez pas l\'accès.')]
class WaterController extends AbstractController
{
    #[Route('/', name: 'app_water_index', methods: ['GET'])]
    public function index(WaterRepository $waterRepository): Response
    {
        return $this->render('water/index.html.twig', [
            'waters' => $waterRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_water_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $water = new Water();
        $form = $this->createForm(WaterType::class, $water);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($water);
            $entityManager->flush();

            return $this->redirectToRoute('app_water_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('water/new.html.twig', [
            'water' => $water,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_water_show', methods: ['GET'])]
    public function show(Water $water): Response
    {
        return $this->render('water/show.html.twig', [
            'water' => $water,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_water_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Water $water, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(WaterType::class, $water);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_water_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('water/edit.html.twig', [
            'water' => $water,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_water_delete', methods: ['POST'])]
    public function delete(Request $request, Water $water, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$water->getId(), $request->getPayload()->get('_token'))) {
            $entityManager->remove($water);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_water_index', [], Response::HTTP_SEE_OTHER);
    }
}
