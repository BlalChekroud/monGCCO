<?php

namespace App\Controller;

use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Entity\Ice;
use App\Form\IceType;
use App\Repository\IceRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/ice')]
#[IsGranted('ROLE_COLLECTOR', message: 'Vous n\'avez pas l\'accès.')]
class IceController extends AbstractController
{
    #[Route('/', name: 'app_ice_index', methods: ['GET'])]
    public function index(IceRepository $iceRepository): Response
    {
        return $this->render('ice/index.html.twig', [
            'ices' => $iceRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_ice_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $ice = new Ice();
        $form = $this->createForm(IceType::class, $ice);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $ice->setCreatedAt(\DateTimeImmutable::createFromMutable(new DateTime()));
                $entityManager->persist($ice);
                $entityManager->flush();
                $this->addFlash('success', "Couverture de glace a bien été crée");
    
                return $this->redirectToRoute('app_ice_index', [], Response::HTTP_SEE_OTHER);
            } else {
                // Log the errors for debugging
                foreach ($form->getErrors(true) as $error) {
                    error_log($error->getMessage());
                }
                $this->addFlash('error','Une erreur s\'est produite lors de la création de la couverture de glace.');
            }
        }

        return $this->render('ice/new.html.twig', [
            'ice' => $ice,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_ice_show', methods: ['GET'])]
    public function show(Ice $ice): Response
    {
        return $this->render('ice/show.html.twig', [
            'ice' => $ice,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_ice_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Ice $ice, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(IceType::class, $ice);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $ice->setUpdatedAt(\DateTimeImmutable::createFromMutable(new DateTime()));
                $entityManager->flush();
                $this->addFlash('success', "Couverture de glace a bien été modifié");

                return $this->redirectToRoute('app_ice_index', [], Response::HTTP_SEE_OTHER);

            } else {
                $this->addFlash('error','Une erreur s\'est produite lors de la modification de la couverture de glace.');
            }
        }

        return $this->render('ice/edit.html.twig', [
            'ice' => $ice,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_ice_delete', methods: ['POST'])]
    public function delete(Request $request, Ice $ice, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$ice->getId(), $request->getPayload()->get('_token'))) {
            $entityManager->remove($ice);
            $entityManager->flush();
            $this->addFlash('success', "Couverture de glace a bien été supprimée");
        } else {
            $this->addFlash('error','Une erreur s\'est produite lors de la suppression de la couverture de glace.');
        }

        return $this->redirectToRoute('app_ice_index', [], Response::HTTP_SEE_OTHER);
    }
}
