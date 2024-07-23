<?php

namespace App\Controller;

use App\Repository\EnvironmentalConditionsRepository;
use Symfony\Component\Security\Http\Attribute\IsGranted;

use Monolog\DateTimeImmutable;
use DateTime;
use App\Entity\CollectedData;
use App\Form\CollectedDataType;
use App\Repository\CollectedDataRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/collected/data')]
#[IsGranted('ROLE_COLLECTOR', message: 'Vous n\'avez pas l\'accès.')]
class CollectedDataController extends AbstractController
{
    #[Route('/', name: 'app_collected_data_index', methods: ['GET'])]
    public function index(CollectedDataRepository $collectedDataRepository): Response
    {
        // $this->denyAccessUnlessGranted('ROLE_USER', null,'Access Denied.');
        return $this->render('collected_data/index.html.twig', [
            'collected_datas' => $collectedDataRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_collected_data_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, EnvironmentalConditionsRepository $environmentalConditionsRepository): Response
    {
        // Vérifier si les conditions environnementales existent pour l'utilisateur actuel
        $user = $this->getUser();
        $environmentalConditions = $environmentalConditionsRepository->findOneBy(['user' => $user]);

        if (!$environmentalConditions) {
            // Rediriger vers la page de création des conditions environnementales si elles n'existent pas
            $this->addFlash('warning', 'Veuillez d\'abord créer les conditions environnementales.');
            return $this->redirectToRoute('app_environmental_conditions_new');
        }
        $collectedDatum = new CollectedData();
        $form = $this->createForm(CollectedDataType::class, $collectedDatum);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $collectedDatum->setCreatedAt(DateTimeImmutable::createFromMutable(new DateTime()));
                $collectedDatum->setEnvironmentalConditions($environmentalConditions);
                $entityManager->persist($collectedDatum);
                $entityManager->flush();
                $this->addFlash('success', "Les données collectées ont été créées avec succès.");
    
                return $this->redirectToRoute('app_collected_data_index', [], Response::HTTP_SEE_OTHER);
            } else {
                // Log the errors for debugging
                foreach ($form->getErrors(true) as $error) {
                    error_log($error->getMessage());
                }
                $this->addFlash('error','Une erreur s\'est produite lors de la création de la collection de données.');
            }
        }

        return $this->render('collected_data/new.html.twig', [
            'collected_datum' => $collectedDatum,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_collected_data_show', methods: ['GET'])]
    public function show(CollectedData $collectedDatum): Response
    {
        return $this->render('collected_data/show.html.twig', [
            'collected_datum' => $collectedDatum,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_collected_data_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, CollectedData $collectedDatum, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(CollectedDataType::class, $collectedDatum);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $collectedDatum->setUpdatedAt(DateTimeImmutable::createFromMutable(new DateTime()));
                $entityManager->flush();
                $this->addFlash('success', "Les données ont été mises à jour avec succès.");
    
                return $this->redirectToRoute('app_collected_data_index', [], Response::HTTP_SEE_OTHER);
            } else {
                $this->addFlash('error','Une erreur s\'est produite lors de la modification de la collection de données.');
            }
        }

        return $this->render('collected_data/edit.html.twig', [
            'collected_datum' => $collectedDatum,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_collected_data_delete', methods: ['POST'])]
    public function delete(Request $request, CollectedData $collectedDatum, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$collectedDatum->getId(), $request->getPayload()->get('_token'))) {
            $entityManager->remove($collectedDatum);
            $entityManager->flush();
            $this->addFlash('success', "Les données collectées ont été supprimées avec succès.");
        } else {
            $this->addFlash('error','Une erreur s\'est produite lors de la suppression de la collection de données.');
        }

        return $this->redirectToRoute('app_collected_data_index', [], Response::HTTP_SEE_OTHER);
    }
}
