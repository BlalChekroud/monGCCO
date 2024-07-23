<?php

namespace App\Controller;

use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Entity\Tidal;
use App\Form\TidalType;
use App\Repository\TidalRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/tidal')]
#[IsGranted('ROLE_COLLECTOR', message: 'Vous n\'avez pas l\'accès.')]
class TidalController extends AbstractController
{
    #[Route('/', name: 'app_tidal_index', methods: ['GET'])]
    public function index(TidalRepository $tidalRepository): Response
    {
        return $this->render('tidal/index.html.twig', [
            'tidals' => $tidalRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_tidal_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $tidal = new Tidal();
        $form = $this->createForm(TidalType::class, $tidal);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($tidal);
            $entityManager->flush();

            return $this->redirectToRoute('app_tidal_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('tidal/new.html.twig', [
            'tidal' => $tidal,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_tidal_show', methods: ['GET'])]
    public function show(Tidal $tidal): Response
    {
        return $this->render('tidal/show.html.twig', [
            'tidal' => $tidal,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_tidal_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Tidal $tidal, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(TidalType::class, $tidal);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_tidal_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('tidal/edit.html.twig', [
            'tidal' => $tidal,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_tidal_delete', methods: ['POST'])]
    public function delete(Request $request, Tidal $tidal, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$tidal->getId(), $request->getPayload()->get('_token'))) {
            $entityManager->remove($tidal);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_tidal_index', [], Response::HTTP_SEE_OTHER);
    }
}
