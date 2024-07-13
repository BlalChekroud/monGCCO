<?php

namespace App\Controller;

use App\Repository\CampaignStatusRepository;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Monolog\DateTimeImmutable;
use DateTime;
use App\Entity\CountingCampaign;
use App\Form\CountingCampaignType;
use App\Repository\CountingCampaignRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/counting/campaign')]
#[IsGranted('ROLE_COLLECTOR')]
class CountingCampaignController extends AbstractController
{
    #[Route('/', name: 'app_counting_campaign_index', methods: ['GET'])]
    public function index(CountingCampaignRepository $countingCampaignRepository): Response
    {
        return $this->render('counting_campaign/index.html.twig', [
            'counting_campaigns' => $countingCampaignRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_counting_campaign_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, CampaignStatusRepository $statusRepository): Response
    {
        $countingCampaign = new CountingCampaign();
        $form = $this->createForm(CountingCampaignType::class, $countingCampaign);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $countingCampaign->setCreatedAt(DateTimeImmutable::createFromMutable(new DateTime()));
            // Générer et définir le nom de la campagne
            $countingCampaign->generateCampaignName();
            // Mettre à jour le statut de la campagne
            $this->updateCampaignStatus($countingCampaign, $statusRepository);
            $entityManager->persist($countingCampaign);
            $entityManager->flush();
            
            $this->addFlash('success', "Campagne de comptage a bien été crée");

            return $this->redirectToRoute('app_counting_campaign_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('counting_campaign/new.html.twig', [
            'counting_campaign' => $countingCampaign,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_counting_campaign_show', methods: ['GET'])]
    public function show(CountingCampaign $countingCampaign): Response
    {
        $sites = $countingCampaign->getSiteCollection()->toArray();
    
        // Tri par siteName
        usort($sites, function($a, $b) {
            return strcmp($a->getSiteName(), $b->getSiteName());
        });

        return $this->render('counting_campaign/show.html.twig', [
            'counting_campaign' => $countingCampaign,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_counting_campaign_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, CountingCampaign $countingCampaign, EntityManagerInterface $entityManager, CampaignStatusRepository $statusRepository): Response
    {
        $form = $this->createForm(CountingCampaignType::class, $countingCampaign);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $countingCampaign->setUpdatedAt(DateTimeImmutable::createFromMutable(new DateTime()));
            // Générer et définir le nom de la campagne
            $countingCampaign->generateCampaignName();

            $entityManager->flush();
            $this->addFlash('success', "Campagne de comptage a bien été modifié");

            return $this->redirectToRoute('app_counting_campaign_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('counting_campaign/edit.html.twig', [
            'counting_campaign' => $countingCampaign,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_counting_campaign_delete', methods: ['POST'])]
    public function delete(Request $request, CountingCampaign $countingCampaign, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$countingCampaign->getId(), $request->getPayload()->get('_token'))) {
            $entityManager->remove($countingCampaign);
            $entityManager->flush();
            $this->addFlash('success', "campagne de comptage a bien été supprimée");
        }

        return $this->redirectToRoute('app_counting_campaign_index', [], Response::HTTP_SEE_OTHER);
    }

    private function updateCampaignStatus(CountingCampaign $countingCampaign, CampaignStatusRepository $statusRepository)
    {
        $now = new \DateTimeImmutable();
        
        $startDate = $countingCampaign->getStartDate();
        $endDate = $countingCampaign->getEndDate();
    
        if ($startDate <= $endDate) {
            if ($startDate > $now) {
                $status = $statusRepository->findOneBy(['label' => 'En attente']);
            } elseif ($startDate <= $now && $endDate >= $now) {
                $status = $statusRepository->findOneBy(['label' => 'En cours']);
            } elseif ($endDate < $now) {
                $status = $statusRepository->findOneBy(['label' => 'Terminé']);
            } else {
                throw new \Exception('Statut non trouvé dans la base de données.');
            }
    
            if ($status) {
                $countingCampaign->setCampaignStatus($status);
            } else {
                throw new \Exception('Statut non trouvé dans la base de données.');
            }
        } else {
            throw new \Exception('Vérifiez les dates de début et de fin.');
        }
    }
    

}
