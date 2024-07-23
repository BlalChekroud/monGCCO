<?php

namespace App\Controller;

use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Entity\Disturbed;
use App\Form\DisturbedType;
use App\Repository\DisturbedRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/disturbed')]
#[IsGranted('ROLE_COLLECTOR', message: 'Vous n\'avez pas l\'accès.')]
class DisturbedController extends AbstractController
{
    #[Route('/', name: 'app_disturbed_index', methods: ['GET'])]
    public function index(DisturbedRepository $disturbedRepository): Response
    {
        return $this->render('disturbed/index.html.twig', [
            'disturbeds' => $disturbedRepository->findAll(),
        ]);
    }

    // #[Route('/new', name: 'app_disturbed_new', methods: ['GET', 'POST'])]
    // public function new(Request $request, EntityManagerInterface $entityManager): Response
    // {
    //     $disturbed = new Disturbed();
    //     $form = $this->createForm(DisturbedType::class, $disturbed);
    //     $form->handleRequest($request);

    //     if ($form->isSubmitted()) {
    //         if ($form->isValid()) {
    //             $disturbed->setCreatedAt(\DateTimeImmutable::createFromMutable(new DateTime()));
    //             $entityManager->persist($disturbed);
    //             $entityManager->flush();

    //             // Handle Turbo Stream response
    //             if ($request->headers->get('Turbo-Frame')) {
    //                 return $this->render('disturbed/_form.html.twig', [
    //                     'disturbed' => $disturbed,
    //                     'form' => $form->createView(),
    //                 ]);
    //             }
    //             $this->addFlash('success', "Perturbé a bien été crée");
    
    //             return $this->redirectToRoute('app_disturbed_index', [], Response::HTTP_SEE_OTHER);
    //         } else {
    //             // Log the errors for debugging
    //             foreach ($form->getErrors(true) as $error) {
    //                 error_log($error->getMessage());
    //             }
    //             $this->addFlash('error','Une erreur s\'est produite lors de la création de Perturbé.');
    //         }
    //     }

    //     return $this->render('disturbed/new.html.twig', [
    //         'disturbed' => $disturbed,
    //         'form' => $form,
    //     ]);
    // }
    #[Route('/new', name: 'app_disturbed_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $disturbed = new Disturbed();
        $form = $this->createForm(DisturbedType::class, $disturbed);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $disturbed->setCreatedAt(new \DateTimeImmutable());
            $entityManager->persist($disturbed);
            $entityManager->flush();

            if ($request->headers->get('Turbo-Frame')) {
                return $this->render('disturbed/_form.html.twig', [
                    'disturbed' => $disturbed,
                    'form' => $form->createView(),
                ]);
            }

            // If not a Turbo Frame request, redirect to list
            return $this->redirectToRoute('app_disturbed_index');
        }

        return $this->render('disturbed/new.html.twig', [
            'disturbed' => $disturbed,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_disturbed_show', methods: ['GET'])]
    public function show(Disturbed $disturbed): Response
    {
        return $this->render('disturbed/show.html.twig', [
            'disturbed' => $disturbed,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_disturbed_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Disturbed $disturbed, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(DisturbedType::class, $disturbed);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $disturbed->setUpdatedAt(\DateTimeImmutable::createFromMutable(new DateTime()));
                $entityManager->flush();
                $this->addFlash('success', "Perturbé a bien été modifié");

                return $this->redirectToRoute('app_disturbed_index', [], Response::HTTP_SEE_OTHER);
            
            } else {
                $this->addFlash('error','Une erreur s\'est produite lors de la modification de Perturbé.');
            }
        }

        return $this->render('disturbed/edit.html.twig', [
            'disturbed' => $disturbed,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_disturbed_delete', methods: ['POST'])]
    public function delete(Request $request, Disturbed $disturbed, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$disturbed->getId(), $request->getPayload()->get('_token'))) {
            $entityManager->remove($disturbed);
            $entityManager->flush();
            $this->addFlash('success', "Perturbé a bien été supprimé");
        } else {
            $this->addFlash('error','Une erreur s\'est produite lors de la suppression de Perturbé.');
        }

        return $this->redirectToRoute('app_disturbed_index', [], Response::HTTP_SEE_OTHER);
    }
}
