<?php

namespace App\Controller;

use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Entity\Quality;
use App\Form\QualityType;
use App\Repository\QualityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/quality')]
#[IsGranted('ROLE_COLLECTOR', message: 'Vous n\'avez pas l\'accès.')]
class QualityController extends AbstractController
{
    #[Route('/', name: 'app_quality_index', methods: ['GET'])]
    public function index(QualityRepository $qualityRepository): Response
    {
        return $this->render('quality/index.html.twig', [
            'qualities' => $qualityRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_quality_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $quality = new Quality();
        $form = $this->createForm(QualityType::class, $quality);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($quality);
            $entityManager->flush();

            return $this->redirectToRoute('app_quality_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('quality/new.html.twig', [
            'quality' => $quality,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_quality_show', methods: ['GET'])]
    public function show(Quality $quality): Response
    {
        return $this->render('quality/show.html.twig', [
            'quality' => $quality,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_quality_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Quality $quality, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(QualityType::class, $quality);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_quality_index', [], Response::HTTP_SEE_OTHER);
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
            $entityManager->remove($quality);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_quality_index', [], Response::HTTP_SEE_OTHER);
    }
}
