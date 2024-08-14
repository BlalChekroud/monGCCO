<?php

namespace App\Controller;

use App\Entity\EnvironmentalConditions;
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
#[IsGranted('ROLE_COLLECTOR', message: 'Vous n\'avez pas l\'accès.')]
class CountingCampaignController extends AbstractController
{
    #[Route('/', name: 'app_counting_campaign_index', methods: ['GET'])]
    public function index(CountingCampaignRepository $countingCampaignRepository): Response
    {
        $user = $this->getUser();
        
        // Vérifier si l'utilisateur a le rôle ADMIN
        if ($this->isGranted('ROLE_ADMIN')) {
            $countingCampaigns = $countingCampaignRepository->findAll();
        } else {
            $countingCampaigns = $countingCampaignRepository->findByUser($user);
        }
        
        return $this->render('counting_campaign/index.html.twig', [
            'counting_campaigns' => $countingCampaigns,
        ]);
    }

    #[IsGranted('ROLE_TEAMLEADER', message: 'Vous n\'avez pas l\'accès.')]
    #[Route('/new', name: 'app_counting_campaign_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, CampaignStatusRepository $statusRepository): Response
    {
        $user = $this->getUser();
        $countingCampaign = new CountingCampaign();
        $form = $this->createForm(CountingCampaignType::class, $countingCampaign);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                if ($countingCampaign->getStartDate() > $countingCampaign->getEndDate()) {
                    $this->addFlash('danger', 'Date de début ne peut pas être supérieure à la date de fin.');
                    return $this->redirectToRoute('app_counting_campaign_new');
                }
                if ($countingCampaign->getAgentsGroups()->isEmpty()) {
                    $this->addFlash('error', 'Vous devez sélectionner au moins un groupe pour créer une campagne de comptage.');
                    return $this->redirectToRoute('app_counting_campaign_new');
                }
                // Vérifier si la collection de sites est définie
                if ($countingCampaign->getSiteCollection()->isEmpty()) {
                    $this->addFlash('error', 'Vous devez sélectionner au moins un site pour créer une campagne de comptage.');
                    return $this->redirectToRoute('app_counting_campaign_new');
                }
                $countingCampaign->setCreatedAt(DateTimeImmutable::createFromMutable(new DateTime()));
                $countingCampaign->setCreatedBy($user);
                // Générer et définir le nom de la campagne
                $countingCampaign->generateCampaignName();
                // Mettre à jour le statut de la campagne
                $this->updateCampaignStatus($countingCampaign, $statusRepository);
                $entityManager->persist($countingCampaign);
                $entityManager->flush();
                
                $countingCampaign->generateCampaignName();
                $entityManager->flush();
                
                $this->addFlash('success', "Campagne de comptage a bien été crée");
    
                return $this->redirectToRoute('app_counting_campaign_index', [], Response::HTTP_SEE_OTHER);
                
            } else {
                $this->addFlash('error',"Une erreur s'est produite lors de la création de la campagne de comptage.");
            }

        }

        return $this->render('counting_campaign/new.html.twig', [
            'counting_campaign' => $countingCampaign,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_counting_campaign_show', methods: ['GET'])]
    public function show(CountingCampaign $countingCampaign, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();

        // Récupérer les sites associés à la campagne
        $sites = $countingCampaign->getSiteCollection();

        // Tableau pour stocker les conditions environnementales existantes pour chaque site
        $existingConditions = [];

        // Parcourir chaque site et vérifier s'il y a des conditions environnementales existantes
        foreach ($sites as $site) {
            $conditions = $entityManager->getRepository(EnvironmentalConditions::class)->findOneBy([
                'user' => $user,
                'siteCollection' => $site,
                'countingCampaign' => $countingCampaign,
            ]);

            $existingConditions[$site->getId()] = $conditions;
        }
// dd($existingConditions);
        return $this->render('counting_campaign/show.html.twig', [
            'counting_campaign' => $countingCampaign,
            'existingConditions' => $existingConditions,
        ]);
    }


    #[Route('/{id}/edit', name: 'app_counting_campaign_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, CountingCampaign $countingCampaign, EntityManagerInterface $entityManager, CampaignStatusRepository $statusRepository): Response
    {
        $form = $this->createForm(CountingCampaignType::class, $countingCampaign);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                // $status = $countingCampaign->getCampaignStatus()->getLabel();

                // if($status == 'Validé') {
                //     $this->addFlash('info', 'Le modification n\'est pas autorisé.');
                //     return $this->redirectToRoute('app_counting_campaign_show', ['id' => $countingCampaign->getId()], Response::HTTP_SEE_OTHER);

                // } elseif ($status == 'Annulé') {
                //     $this->addFlash('info', 'Le modification n\'est pas autorisé.');
                //     return $this->redirectToRoute('app_counting_campaign_index', [], Response::HTTP_SEE_OTHER);
                // }

                if ($countingCampaign->getStartDate() > $countingCampaign->getEndDate()) {
                    $this->addFlash('danger', 'Date de début ne peut pas être supérieure à la date de fin.');
                    return $this->redirectToRoute('app_counting_campaign_edit', ['id' => $countingCampaign->getId()]);
                }
                // Vérifier si la collection de sites est définie
                if ($countingCampaign->getSiteCollection()->isEmpty()) {
                    $this->addFlash('error', 'Vous devez sélectionner au moins un site pour créer une campagne de comptage.');
                    return $this->redirectToRoute('app_counting_campaign_edit', ['id' => $countingCampaign->getId()]);
                }
                $countingCampaign->setUpdatedAt(DateTimeImmutable::createFromMutable(new DateTime()));
                // Générer et définir le nom de la campagne
                $countingCampaign->generateCampaignName();
    
                $entityManager->flush();
                $this->addFlash('success', "Campagne de comptage a bien été modifié");
    
                return $this->redirectToRoute('app_counting_campaign_index', [], Response::HTTP_SEE_OTHER);

            } else {
                $this->addFlash('error','Une erreur s\'est produite lors de la modification de la campagne de comptage.');
            }
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
            $this->addFlash('success', "Campagne de comptage a bien été supprimée");
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
