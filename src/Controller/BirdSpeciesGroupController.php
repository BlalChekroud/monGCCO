<?php

namespace App\Controller;

use App\Entity\BirdSpeciesGroup;
use App\Form\BirdSpeciesGroupType;
use App\Repository\BirdSpeciesGroupRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/bird/species/group')]
class BirdSpeciesGroupController extends AbstractController
{
    #[Route('/', name: 'app_bird_species_group_index', methods: ['GET'])]
    public function index(BirdSpeciesGroupRepository $birdSpeciesGroupRepository): Response
    {
        return $this->render('bird_species_group/index.html.twig', [
            'bird_species_groups' => $birdSpeciesGroupRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_bird_species_group_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $birdSpeciesGroup = new BirdSpeciesGroup();
        $form = $this->createForm(BirdSpeciesGroupType::class, $birdSpeciesGroup);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($birdSpeciesGroup);
            $entityManager->flush();

            return $this->redirectToRoute('app_bird_species_group_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('bird_species_group/new.html.twig', [
            'bird_species_group' => $birdSpeciesGroup,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_bird_species_group_show', methods: ['GET'])]
    public function show(BirdSpeciesGroup $birdSpeciesGroup): Response
    {
        return $this->render('bird_species_group/show.html.twig', [
            'bird_species_group' => $birdSpeciesGroup,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_bird_species_group_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, BirdSpeciesGroup $birdSpeciesGroup, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(BirdSpeciesGroupType::class, $birdSpeciesGroup);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_bird_species_group_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('bird_species_group/edit.html.twig', [
            'bird_species_group' => $birdSpeciesGroup,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_bird_species_group_delete', methods: ['POST'])]
    public function delete(Request $request, BirdSpeciesGroup $birdSpeciesGroup, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$birdSpeciesGroup->getId(), $request->getPayload()->get('_token'))) {
            $entityManager->remove($birdSpeciesGroup);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_bird_species_group_index', [], Response::HTTP_SEE_OTHER);
    }
}
