<?php

namespace App\Controller;

use Symfony\Component\Security\Http\Attribute\IsGranted;
use Monolog\DateTimeImmutable;
use DateTime;
use App\Entity\CampaignStatus;
use App\Form\CampaignStatusType;
use App\Repository\CampaignStatusRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/campaign/status')]
#[IsGranted('ROLE_COLLECTOR', message: 'Vous n\'avez pas l\'accès.')]
class CampaignStatusController extends AbstractController
{
    #[Route('/', name: 'app_campaign_status_index', methods: ['GET'])]
    public function index(CampaignStatusRepository $campaignStatusRepository): Response
    {
        return $this->render('campaign_status/index.html.twig', [
            'campaign_statuses' => $campaignStatusRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_campaign_status_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $campaignStatus = new CampaignStatus();
        $form = $this->createForm(CampaignStatusType::class, $campaignStatus);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $campaignStatus->setCreatedAt(DateTimeImmutable::createFromMutable(new DateTime()));
            $entityManager->persist($campaignStatus);
            $entityManager->flush();

            $this->addFlash('success', "Etat de campagne a bien été crée");

            return $this->redirectToRoute('app_campaign_status_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('campaign_status/new.html.twig', [
            'campaign_status' => $campaignStatus,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_campaign_status_show', methods: ['GET'])]
    public function show(CampaignStatus $campaignStatus): Response
    {
        return $this->render('campaign_status/show.html.twig', [
            'campaign_status' => $campaignStatus,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_campaign_status_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, CampaignStatus $campaignStatus, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(CampaignStatusType::class, $campaignStatus);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $campaignStatus->setUpdatedAt(DateTimeImmutable::createFromMutable(new DateTime()));
            $entityManager->flush();
            $this->addFlash('success', "Etat de campagne de comptage a bien été modifié");

            return $this->redirectToRoute('app_campaign_status_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('campaign_status/edit.html.twig', [
            'campaign_status' => $campaignStatus,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_campaign_status_delete', methods: ['POST'])]
    public function delete(Request $request, CampaignStatus $campaignStatus, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$campaignStatus->getId(), $request->getPayload()->get('_token'))) {
            $entityManager->remove($campaignStatus);
            $entityManager->flush();
            $this->addFlash('success', "Etat de campagne a bien été supprimée");
        }

        return $this->redirectToRoute('app_campaign_status_index', [], Response::HTTP_SEE_OTHER);
    }
}
