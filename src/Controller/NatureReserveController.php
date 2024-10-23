<?php

namespace App\Controller;

use App\Entity\NatureReserve;
use App\Form\NatureReserveType;
use App\Repository\NatureReserveRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/nature/reserve')]
class NatureReserveController extends AbstractController
{
    #[Route('/', name: 'app_nature_reserve_index', methods: ['GET'])]
    public function index(NatureReserveRepository $natureReserveRepository): Response
    {
        $user = $this->getUser();
        // Vérifier si l'utilisateur a le rôle ADMIN
        if ($this->isGranted('ROLE_ADMIN') || $this->isGranted('ROLE_VIEW')) {
            $natureReserves = $natureReserveRepository->findAll();
        } else {
            $natureReserves = $natureReserveRepository->findByUser($user);
        }
        
        return $this->render('nature_reserve/index.html.twig', [
            'nature_reserves' => $natureReserves,
        ]);
    }

    #[Route('/new', name: 'app_nature_reserve_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        $natureReserve = new NatureReserve();
        $form = $this->createForm(NatureReserveType::class, $natureReserve);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                try {
                    // if ($natureReserve->getSiteAgentsGroups()->isEmpty()) {
                    //     $this->addFlash('error', 'Vous devez sélectionner au moins un site et un groupe.');
                    //     return $this->redirectToRoute('app_nature_reserve_new');
                    // }
                    // foreach ($natureReserve->getSiteAgentsGroups() as $siteAgentsGroup) {
                    //     $siteAgentsGroup->setNatureReserve($natureReserve);
                    //     $siteAgentsGroup->setCreatedAt(new \DateTimeImmutable());
                    //     $entityManager->persist($siteAgentsGroup);
                    // }
    
                    
                    $natureReserve->setCreatedAt(new \DateTimeImmutable());
                    $natureReserve->setCreatedBy($user);
                    $entityManager->persist($natureReserve);
                    $entityManager->flush();
                    $this->addFlash('success', 'La réserve naturelle a été créée avec succès.');
        
                    return $this->redirectToRoute('app_nature_reserve_index', [], Response::HTTP_SEE_OTHER);
                    
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Une erreur s\'est produite lors de la création de la réserve naturelle : ' . $e->getMessage());
                    return $this->redirectToRoute('app_nature_reserve_new', [], Response::HTTP_SEE_OTHER);
                }
                
            } else {
                $this->addFlash('error',"Veuillez corriger les erreurs dans le formulaire.");
            }
        }

        return $this->render('nature_reserve/new.html.twig', [
            'nature_reserve' => $natureReserve,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_nature_reserve_show', methods: ['GET'])]
    public function show(NatureReserve $natureReserve): Response
    {
        return $this->render('nature_reserve/show.html.twig', [
            'nature_reserve' => $natureReserve,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_nature_reserve_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, NatureReserve $natureReserve, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(NatureReserveType::class, $natureReserve);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_nature_reserve_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('nature_reserve/edit.html.twig', [
            'nature_reserve' => $natureReserve,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_nature_reserve_delete', methods: ['POST'])]
    public function delete(Request $request, NatureReserve $natureReserve, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$natureReserve->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($natureReserve);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_nature_reserve_index', [], Response::HTTP_SEE_OTHER);
    }
}
