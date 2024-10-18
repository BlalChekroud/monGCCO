<?php

namespace App\Controller;

use App\Entity\CountingCampaign;
use App\Entity\SiteCollection;
use App\Repository\BirdSpeciesRepository;
use App\Repository\EnvironmentalConditionsRepository;
use App\Service\CampaignStatusService;
use Symfony\Component\Security\Http\Attribute\IsGranted;

use App\Entity\CollectedData;
use App\Form\CollectedDataType;
use App\Repository\CollectedDataRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/user/collected/data')]
class CollectedDataController extends AbstractController
{
    private $campaignStatusService;

    public function __construct(CampaignStatusService $campaignStatusService)
    {
        $this->campaignStatusService = $campaignStatusService;
    }
    

    #[Route('/', name: 'app_collected_data_index', methods: ['GET'])]
    public function index(CollectedDataRepository $collectedDataRepository): Response
    {
        $user = $this->getUser();

        // Vérifiez si l'utilisateur a l'un des rôles
        if (!$this->isGranted('ROLE_VIEW') && !$this->isGranted('ROLE_COLLECTOR')) {
            throw $this->createAccessDeniedException('Vous n\'avez pas l\'accès.');
        }

        if ($this->isGranted('ROLE_ADMIN')) {
            $collectedDatas = $collectedDataRepository->findAll();
        } else {
            $collectedDatas = $collectedDataRepository->findBy(['createdBy' => $user]);
        }
        return $this->render('collected_data/index.html.twig', [
            'collected_datas' => $collectedDatas,
        ]);
    }

    #[IsGranted('ROLE_COLLECTOR', message: 'Vous n\'avez pas l\'accès.')]
    #[Route('/new', name: 'app_collected_data_new', methods: ['GET', 'POST'])]
    public function new(Request $request, BirdSpeciesRepository $birdSpeciesRepository, EntityManagerInterface $entityManager, EnvironmentalConditionsRepository $environmentalConditionsRepository): Response
    {
        // Récupérer l'utilisateur actuel
        $user = $this->getUser();

        $statuses =$this->campaignStatusService->getCampaignStatuses();
        $statusClosed = $statuses['statusClosed'];
        $statusSuspended = $statuses['statusSuspended'];
        $statusCancelled = $statuses['statusCancelled'];

        // Récupérer la campagne et le site en fonction des paramètres de la requête ou d'un choix de l'utilisateur
        $campaignId = $request->query->get('campaignId');
        $siteId = $request->query->get('siteId');

        $campaign = $entityManager->getRepository(CountingCampaign::class)->find($campaignId);
        $site = $entityManager->getRepository(SiteCollection::class)->find($siteId);

        if ($campaign->getCampaignStatus() === $statusClosed) {
            throw $this->createNotFoundException("Une campagne $statusClosed ne peut pas être modifiée");
        }
        if ($campaign->getCampaignStatus() === $statusSuspended) {
            throw $this->createNotFoundException("Une campagne $statusSuspended ne peut pas être modifiée");
        }
        if ($campaign->getCampaignStatus() === $statusCancelled) {
            throw $this->createNotFoundException("Une campagne $statusClosed ne peut pas être modifiée");
        }
        
        // Vérifier si la campagne et le site existent
        if (!$campaign) {
            $this->addFlash('error', 'La campagne spécifiée est introuvable.');
            return $this->redirectToRoute('app_collected_data_index');
        }
        
        if (!$site) {
            $this->addFlash('error', 'Le site spécifié est introuvable.');
            return $this->redirectToRoute('app_collected_data_index');
        }

            // Vérifier si l'utilisateur est membre d'un groupe d'agents assigné à ce site
        $isMember = false;
        foreach ($site->getSiteAgentsGroups() as $siteAgentsGroup) {
            foreach ($siteAgentsGroup->getAgentsGroup() as $group) {
                if ($group->getGroupMember()->contains($user)) {
                    $isMember = true;
                    break;
                }
            }
            if ($isMember) break;
        }

        // Si l'utilisateur n'est pas membre d'un groupe, interdire l'accès
        if (!$isMember) {
            $this->addFlash('warning', 'Vous ne pouvez pas collecter des données car vous n\'êtes pas membre d\'un groupe assigné à ce site.');
            return $this->redirectToRoute('app_collected_data_index');
        }

        // Vérifier si les conditions environnementales existent pour cet utilisateur, ce site et cette campagne
        $environmentalConditions = $environmentalConditionsRepository->findOneBy(
            [
                'user' => $user,
                'siteCollection' => $site,
                'countingCampaign' => $campaign
            ],
            ['createdAt' => 'DESC'] // Trier par date de création pour obtenir la plus récente
        );

        // Récupérer toutes les espèces d'oiseaux
        $birdSpecies = $birdSpeciesRepository->findAll();
        if (!$environmentalConditions) {
            // Rediriger vers la page de création des conditions environnementales si elles n'existent pas
            $this->addFlash('warning', 'Veuillez d\'abord créer les conditions environnementales pour ce site.');
            return $this->redirectToRoute('app_environmental_conditions_new', [
                'campaignId' => $campaignId,
                'siteId' => $siteId
            ]);
        }

        
        // if ($environmentalConditions && !$environmentalConditions->getCollectedData()) {
        //     $this->addFlash('info', "Vous avez déjà créé des conditions environnementales pour ce site.");
        // }

        // Créer une nouvelle entité CollectedData
        $collectedDatum = new CollectedData();
        $collectedDatum->setCountingCampaign($campaign);
        $collectedDatum->setSiteCollection($site);
        $collectedDatum->setEnvironmentalConditions($environmentalConditions);
        $collectedDatum->setCreatedBy($user);

        // Créer le formulaire pour les données collectées
        $form = $this->createForm(CollectedDataType::class, $collectedDatum);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                 // Vérifiez les propriétés nécessaires
                $hasErrors = false;
                $errorMessage = "";

                // Vérifiez la présence des espèces d'oiseaux
                if ($collectedDatum->getBirdSpeciesCounts()->isEmpty()) {
                    $hasErrors = true;
                    $errorMessage .= "Aucune espèce d'oiseau sélectionnée. ";
                }

                // Vérifiez la présence de méthodes
                if ($collectedDatum->getMethod()->isEmpty()) {
                    $hasErrors = true;
                    $errorMessage .= "Aucune méthode sélectionnée. ";
                }

                // Vérifiez la présence de qualité et de type de comptage si nécessaire
                if ($collectedDatum->getQuality() === null) {
                    $hasErrors = true;
                    $errorMessage .= "La qualité est requise. ";
                }

                if ($collectedDatum->getCountType() === null) {
                    $hasErrors = true;
                    $errorMessage .= "Le type de comptage est requis. ";
                }

                // Vérifiez les données d'environnement
                if (!$campaign) {
                    $hasErrors = true;
                    $errorMessage .= "La campagne de comptage est manquante. ";
                }

                if (!$site) {
                    $hasErrors = true;
                    $errorMessage .= "Le site de collecte est manquant. ";
                }

                // Vérifier si le `SiteCollection` de `CollectedData` correspond à celui de `EnvironmentalConditions`
                if ($collectedDatum->getSiteCollection() !== $environmentalConditions->getSiteCollection()) {
                    $hasErrors = true;
                    $errorMessage .= "Le site de collecte ne correspond pas aux conditions environnementales.";
                }

                // Vérifiez que le total des comptages d'oiseaux est positif
                if ($collectedDatum->getTotalCount() <= 0) {
                    $hasErrors = true;
                    $errorMessage .= "La collecte ne peut pas être nulle.";
                }

                // Si des erreurs sont présentes, affichez un message d'erreur et redirigez
                if ($hasErrors) {
                    $this->addFlash('error', $errorMessage);
                    return $this->redirectToRoute('app_collected_data_new', [], Response::HTTP_SEE_OTHER);
                }

                // Enregistrez les données collectées
                $collectedDatum->setCreatedAt(new \DateTimeImmutable());
                $entityManager->persist($collectedDatum);

                foreach ($collectedDatum->getBirdSpeciesCounts() as $birdSpeciesCount) {
                    $birdSpeciesCount->setCollectedData($collectedDatum);
                    $entityManager->persist($birdSpeciesCount);
                }
                // Sauvegarde des données
                $entityManager->flush();
                $this->addFlash('success', "Les données collectées ont été créées avec succès.");
    
                return $this->redirectToRoute('app_collected_data_index', [], Response::HTTP_SEE_OTHER);
            } else {
                $this->addFlash('error','Une erreur s\'est produite lors de la création de la collection de données.');
            }
        }

        return $this->render('collected_data/new.html.twig', [
            'collected_datum' => $collectedDatum,
            'form' => $form,
            'siteCollection' => $site,
            'campaign_id' => $campaignId,
            'environmentalConditions' => $environmentalConditions,
            // 'birdSpecies' => $birdSpecies,
        ]);
    }

    #[Route('/{id}', name: 'app_collected_data_show', methods: ['GET'])]
    public function show(CollectedData $collectedDatum): Response
    {
        $user = $this->getUser();

        // Vérifiez si l'utilisateur est admin, créateur ou membre du groupe
        if ($this->isGranted('ROLE_ADMIN') || $this->isGranted('ROLE_VIEW') || $collectedDatum->getCreatedBy() === $user) {
            return $this->render('collected_data/show.html.twig', [
                'collected_datum' => $collectedDatum,
            ]);
            
        } else {
            $this->addFlash('info', 'Vous n\'avez pas accès à cette collecte.');
            return $this->redirectToRoute('app_collected_data_index');
        }
    }

    #[Route('/{id}/edit', name: 'app_collected_data_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, CollectedData $collectedDatum, EntityManagerInterface $entityManager): Response
    {
        $campaign = $collectedDatum->getCountingCampaign();

        $statuses =$this->campaignStatusService->getCampaignStatuses();
        $statusClosed = $statuses['statusClosed'];
        $statusSuspended = $statuses['statusSuspended'];
        $statusCancelled = $statuses['statusCancelled'];

        if ($campaign->getCampaignStatus() === $statusClosed) {
            throw $this->createNotFoundException("Une campagne $statusClosed ne peut pas être modifiée");
        }
        if ($campaign->getCampaignStatus() === $statusSuspended) {
            throw $this->createNotFoundException("Une campagne $statusSuspended ne peut pas être modifiée");
        }
        if ($campaign->getCampaignStatus() === $statusCancelled) {
            throw $this->createNotFoundException("Une campagne $statusClosed ne peut pas être modifiée");
        }

        $form = $this->createForm(CollectedDataType::class, $collectedDatum);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                // Vérifiez que le total des comptages d'oiseaux est positif
                if ($collectedDatum->getTotalCount() <= 0) {
                    $this->addFlash('error', "La collecte ne peut pas être nulle.");
                    return $this->redirectToRoute('app_collected_data_new', [], Response::HTTP_SEE_OTHER);
                }
                $collectedDatum->setUpdatedAt(new \DateTimeImmutable);
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
