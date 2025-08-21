<?php

namespace App\Controller;

use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Entity\CampaignStatus;
use App\Form\CampaignStatusType;
use App\Repository\CampaignStatusRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/super-admin/campaign/status')]
// #[IsGranted('ROLE_COLLECTOR', message: 'Vous n\'avez pas l\'accès.')]
class CampaignStatusController extends AbstractController
{
    public function __construct(private readonly TranslatorInterface $translator) {}

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
    
        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                try {
                    $campaignStatus->setCreatedAt(new \DateTimeImmutable());
                    $entityManager->persist($campaignStatus);
                    $entityManager->flush();

                    $this->addFlash('success', $this->translator->trans('campaignStatus.msg.created_success'));

                    return $this->redirectToRoute('app_campaign_status_index', [], Response::HTTP_SEE_OTHER);
                } catch (\Exception $e) {
                    $this->addFlash('error', $e->getMessage());
                    return $this->redirectToRoute('app_campaign_status_new', [], Response::HTTP_SEE_OTHER);
                }
            } else {
                $this->addFlash('error', $this->translator->trans('campaignStatus.msg.created_error'));
            }
        }
    
        return $this->render('campaign_status/new.html.twig', [
            'campaign_status' => $campaignStatus,
            'form' => $form,
        ]);
    }
    

    #[Route('/{id}/edit', name: 'app_campaign_status_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, CampaignStatus $campaignStatus, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(CampaignStatusType::class, $campaignStatus);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                try {
                    $campaignStatus->setUpdatedAt(new \DateTimeImmutable());
                    $entityManager->flush();
                    $this->addFlash('success', $this->translator->trans('campaignStatus.msg.updated_success'));
            
                    return $this->redirectToRoute('app_campaign_status_index', [], Response::HTTP_SEE_OTHER);        
                } catch (\Exception $e) {
                    $this->addFlash('error', $e->getMessage());
                    return $this->redirectToRoute('app_campaign_status_edit', ['id' => $campaignStatus->getId()], Response::HTTP_SEE_OTHER);
                }
            } else {
                $this->addFlash('error', $this->translator->trans('campaignStatus.msg.updated_error'));
            }
        }

        return $this->render('campaign_status/edit.html.twig', [
            'campaign_status' => $campaignStatus,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_campaign_status_delete', methods: ['POST'])]
    public function delete(Request $request, CampaignStatus $campaignStatus, EntityManagerInterface $entityManager): Response
    {
        try {
            if ($this->isCsrfTokenValid('delete'.$campaignStatus->getId(), $request->getPayload()->get('_token'))) {
                // Tentative de suppression de l'état de campagne
                $entityManager->remove($campaignStatus);
                $entityManager->flush();
                $this->addFlash('success', $this->translator->trans('campaignStatus.msg.deleted_success'));           
            } else {
                $this->addFlash('error', $this->translator->trans('campaignStatus.msg.deleted_error'));
            }
        } catch (\Exception $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('app_campaign_status_index', [], Response::HTTP_SEE_OTHER);
    }
}
