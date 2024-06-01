<?php

namespace App\Controller;

use Monolog\DateTimeImmutable;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use App\Form\BirdFamilyType;
use App\Entity\BirdFamily;
use Symfony\Component\HttpFoundation\Request;
use App\Repository\BirdFamilyRepository;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/bird/family')]
class BirdFamilyController extends AbstractController
{
    #[Route('/', name: 'app_bird_family_index', methods: ['GET'])]
    public function index(BirdFamilyRepository $birdFamilyRepository): Response
    {
        return $this->render('bird_family/index.html.twig', [
            'bird_families' => $birdFamilyRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_bird_family_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $birdFamily = new BirdFamily();
        $form = $this->createForm(BirdFamilyType::class, $birdFamily);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $birdFamily->setCreatedAt(DateTimeImmutable::createFromMutable(new DateTime()));
            $entityManager->persist($birdFamily);
            $entityManager->flush();
            $this->addFlash('success', "Famille d'oiseaux a bien été crée");

            return $this->redirectToRoute('app_bird_family_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('bird_family/new.html.twig', [
            'bird_family' => $birdFamily,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_bird_family_show', methods: ['GET'])]
    public function show(BirdFamily $birdFamily): Response
    {
        return $this->render('bird_family/show.html.twig', [
            'bird_family' => $birdFamily,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_bird_family_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, BirdFamily $birdFamily, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(BirdFamilyType::class, $birdFamily);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $birdFamily->setUpdatedAt(DateTimeImmutable::createFromMutable(new DateTime()));
            $entityManager->flush();
            $this->addFlash('success', 'La famille a bien été modifié');

            return $this->redirectToRoute('app_bird_family_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('bird_family/edit.html.twig', [
            'bird_family' => $birdFamily,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_bird_family_delete', methods: ['POST'])]
    public function delete(Request $request, BirdFamily $birdFamily, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$birdFamily->getId(), $request->getPayload()->get('_token'))) {
            $entityManager->remove($birdFamily);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_bird_family_index', [], Response::HTTP_SEE_OTHER);
    }
}
