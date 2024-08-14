<?php

namespace App\Controller;

use App\Entity\BirdSpeciesCount;
use App\Form\BirdSpeciesCountType;
use App\Repository\BirdSpeciesCountRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/bird/species/count')]
class BirdSpeciesCountController extends AbstractController
{
    #[Route('/', name: 'app_bird_species_count_index', methods: ['GET'])]
    public function index(BirdSpeciesCountRepository $birdSpeciesCountRepository): Response
    {
        return $this->render('bird_species_count/index.html.twig', [
            'bird_species_counts' => $birdSpeciesCountRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_bird_species_count_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $birdSpeciesCount = new BirdSpeciesCount();
        $formCount = $this->createForm(BirdSpeciesCountType::class, $birdSpeciesCount);
        $formCount->handleRequest($request);

        if ($formCount->isSubmitted() && $formCount->isValid()) {
            $entityManager->persist($birdSpeciesCount);
            $entityManager->flush();

            return $this->redirectToRoute('app_bird_species_count_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('bird_species_count/new.html.twig', [
            'bird_species_count' => $birdSpeciesCount,
            'formCount' => $formCount,
        ]);
    }

    #[Route('/{id}', name: 'app_bird_species_count_show', methods: ['GET'])]
    public function show(BirdSpeciesCount $birdSpeciesCount): Response
    {
        return $this->render('bird_species_count/show.html.twig', [
            'bird_species_count' => $birdSpeciesCount,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_bird_species_count_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, BirdSpeciesCount $birdSpeciesCount, EntityManagerInterface $entityManager): Response
    {
        $formCount = $this->createForm(BirdSpeciesCountType::class, $birdSpeciesCount);
        $formCount->handleRequest($request);

        if ($formCount->isSubmitted() && $formCount->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_bird_species_count_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('bird_species_count/edit.html.twig', [
            'bird_species_count' => $birdSpeciesCount,
            'formCount' => $formCount,
        ]);
    }

    #[Route('/{id}', name: 'app_bird_species_count_delete', methods: ['POST'])]
    public function delete(Request $request, BirdSpeciesCount $birdSpeciesCount, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$birdSpeciesCount->getId(), $request->getPayload()->get('_token'))) {
            $entityManager->remove($birdSpeciesCount);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_bird_species_count_index', [], Response::HTTP_SEE_OTHER);
    }
}
