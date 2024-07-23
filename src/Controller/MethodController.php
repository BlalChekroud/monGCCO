<?php

namespace App\Controller;

use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Entity\Method;
use App\Form\MethodType;
use App\Repository\MethodRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/method')]
#[IsGranted('ROLE_COLLECTOR', message: 'Vous n\'avez pas l\'accès.')]
class MethodController extends AbstractController
{
    #[Route('/', name: 'app_method_index', methods: ['GET'])]
    public function index(MethodRepository $methodRepository): Response
    {
        return $this->render('method/index.html.twig', [
            'methods' => $methodRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_method_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $method = new Method();
        $form = $this->createForm(MethodType::class, $method);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($method);
            $entityManager->flush();

            return $this->redirectToRoute('app_method_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('method/new.html.twig', [
            'method' => $method,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_method_show', methods: ['GET'])]
    public function show(Method $method): Response
    {
        return $this->render('method/show.html.twig', [
            'method' => $method,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_method_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Method $method, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(MethodType::class, $method);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_method_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('method/edit.html.twig', [
            'method' => $method,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_method_delete', methods: ['POST'])]
    public function delete(Request $request, Method $method, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$method->getId(), $request->getPayload()->get('_token'))) {
            $entityManager->remove($method);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_method_index', [], Response::HTTP_SEE_OTHER);
    }
}
