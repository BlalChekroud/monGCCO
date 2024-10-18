<?php

namespace App\Controller;

use App\Entity\EnvironmentalConditions;
use App\Repository\CampaignStatusRepository;
use App\Security\Voter\CountingCampaignVoter;
use App\Service\CampaignStatusService;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Entity\CountingCampaign;
use App\Form\CountingCampaignType;
use App\Repository\CountingCampaignRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/user/counting/campaign')]
class CountingCampaignController extends AbstractController
{
    private $campaignStatusService;

    public function __construct(CampaignStatusService $campaignStatusService)
    {
        $this->campaignStatusService = $campaignStatusService;
    }

    #[Route('/', name: 'app_counting_campaign_index', methods: ['GET'])]
    public function index(CampaignStatusRepository $campaignStatusRepository, CountingCampaignRepository $countingCampaignRepository, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        
        // Appel de la méthode pour récupérer les statuts
        $statuses = $this->campaignStatusService->getCampaignStatuses();
        $statusClosed = $statuses['statusClosed'];
        $statusSuspended = $statuses['statusSuspended'];
        $statusCancelled = $statuses['statusCancelled'];
        $statusIndexMap = $statuses['statusIndexMap'];
        
        // Vérifier si l'utilisateur a le rôle ADMIN
        if ($this->isGranted('ROLE_ADMIN') || $this->isGranted('ROLE_VIEW')) {
            $countingCampaigns = $countingCampaignRepository->findAll();
        } else {
            $countingCampaigns = $countingCampaignRepository->findByUser($user);
        }
        
        $needsFlush = false; // Variable pour suivre si flush est nécessaire

    
        foreach ($countingCampaigns as $countingCampaign) {
            // Vérifier si la campagne n'est ni clôturée ni en suspens avant de mettre à jour le statut
            if ($countingCampaign->getCampaignStatus() !== $statusClosed && $countingCampaign->getCampaignStatus() !== $statusSuspended && $countingCampaign->getCampaignStatus() !== $statusCancelled) {
                $previousStatus = $countingCampaign->getCampaignStatus(); // Sauvegarder le statut précédent
                
                // Mettre à jour le statut
                $this->updateCampaignStatus($countingCampaign, $campaignStatusRepository);
                
                // Si le statut a changé, indiquer qu'il faut flusher
                if ($countingCampaign->getCampaignStatus() !== $previousStatus) {
                    $needsFlush = true;
                }
            }
        }
    
        // Flusher les changements uniquement si nécessaire
        if ($needsFlush) {
            $entityManager->flush();
        }

        return $this->render('counting_campaign/index.html.twig', [
            'counting_campaigns' => $countingCampaigns,
            'statusIndexMap' => $statusIndexMap,  // Passer le tableau d'index des statuts
        ]);
    }

    
    #[IsGranted(CountingCampaignVoter::CREATE)]
    #[Route('/new', name: 'app_counting_campaign_new', methods: ['GET', 'POST'])]
    public function new(CampaignStatusRepository $campaignStatusRepository, Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        $countingCampaign = new CountingCampaign();
        $form = $this->createForm(CountingCampaignType::class, $countingCampaign);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                foreach ($countingCampaign->getSiteAgentsGroups() as $siteAgentsGroup) {
                    $siteAgentsGroup->setCountingCampaign($countingCampaign);
                    $siteAgentsGroup->setCreatedAt(new \DateTimeImmutable());
                    $entityManager->persist($siteAgentsGroup);
                }

                if ($countingCampaign->getSiteAgentsGroups()->isEmpty()) {
                    $this->addFlash('error', 'Vous devez sélectionner au moins un site et un groupe.');
                    return $this->redirectToRoute('app_counting_campaign_new');
                }

                $countingCampaign->setCreatedAt(new \DateTimeImmutable());
                $countingCampaign->setCreatedBy($user);
                // Générer et définir le nom de la campagne
                $countingCampaign->generateCampaignName();
                // Mettre à jour le statut de la campagne
                $this->updateCampaignStatus($countingCampaign, $campaignStatusRepository);
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

    #[IsGranted(CountingCampaignVoter::VIEW, 'countingCampaign')]
    #[Route('/{id}', name: 'app_counting_campaign_show', methods: ['GET'])]
    public function show(CampaignStatusRepository $campaignStatusRepository, CountingCampaignRepository $countingCampaignRepository, CountingCampaign $countingCampaign, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();  // Utilisateur actuel

        // Appel de la méthode pour récupérer les statuts
        $statuses = $this->campaignStatusService->getCampaignStatuses();
        $statusFinished = $statuses['statusFinished'];
        $statusClosed = $statuses['statusClosed'];
        $statusSuspended = $statuses['statusSuspended'];
        $statusCancelled = $statuses['statusCancelled'];
        $statusIndexMap = $statuses['statusIndexMap'];

        // Récupérer les SiteAgentsGroups associés à la campagne
        $siteAgentsGroups = $countingCampaign->getSiteAgentsGroups();
    
        // Tableau pour stocker les SiteCollection et les conditions environnementales
        $sites = [];
        $existingConditions = [];
    
        // Mettre à jour le statut de la campagne avant l'affichage uniquement si cela est nécessaire
        if ($countingCampaign->getCampaignStatus() !== $statusClosed && $countingCampaign->getCampaignStatus() !== $statusSuspended && $countingCampaign->getCampaignStatus() !== $statusCancelled) {
            $previousStatus = $countingCampaign->getCampaignStatus(); // Sauvegarder le statut précédent
            $this->updateCampaignStatus($countingCampaign, $campaignStatusRepository);
    
            // Si le statut a changé, flusher les modifications
            if ($countingCampaign->getCampaignStatus() !== $previousStatus) {
                $entityManager->flush();
            }
        }
    
        // Récupérer tous les SiteCollection associés aux groupes d'agents
        foreach ($siteAgentsGroups as $siteAgentsGroup) {
            $siteCollection = $siteAgentsGroup->getSiteCollection();
    
            if ($siteCollection) {
                // Ajouter le site à la liste des sites
                $sites[] = $siteCollection;
            }
        }
    
        // Récupérer toutes les conditions environnementales pour les sites de la campagne en une seule requête
        if (!empty($sites)) {
            $conditions = $entityManager->getRepository(EnvironmentalConditions::class)->findBy([
                'user' => $user,
                'siteCollection' => $sites,
                'countingCampaign' => $countingCampaign,
            ], ['createdAt' => 'DESC']);
    
            // Stocker les conditions par ID de SiteCollection
            foreach ($conditions as $condition) {
                $existingConditions[$condition->getSiteCollection()->getId()] = $condition;
            }
        }
    
        return $this->render('counting_campaign/show.html.twig', [
            'counting_campaign' => $countingCampaign,
            'existingConditions' => $existingConditions,  // Transmettre les conditions environnementales
            'statusIndexMap' => $statusIndexMap,
            'statusFinished' => $statusFinished,
            'statusClosed' => $statusClosed,
            'statusSuspended' => $statusSuspended,
            'statusCancelled' => $statusCancelled,
            'collections' => $countingCampaignRepository->getCollectedDataForCampaign($countingCampaign), // Récupérer les collectes associées à la campagne
        ]);
    }
    

    #[IsGranted(CountingCampaignVoter::EDIT, 'countingCampaign')]
    #[Route('/{id}/edit', name: 'app_counting_campaign_edit', methods: ['GET', 'POST'])]
    public function edit(CampaignStatusRepository $campaignStatusRepository, Request $request, CountingCampaign $countingCampaign, EntityManagerInterface $entityManager): Response
    {
        $statuses = $this->campaignStatusService->getCampaignStatuses();
        $statusClosed = $statuses['statusClosed'];
        $statusCancelled = $statuses['statusCancelled'];

        // Empêcher la modification d'une campagne si elle est clôturée ou Annulée
        if ($countingCampaign->getCampaignStatus() === $statusClosed) {
            throw $this->createNotFoundException('Impossible de modifier une campagne ' . $statusClosed);
        }
        
        if ($countingCampaign->getCampaignStatus() === $statusCancelled) {
            throw $this->createNotFoundException('Impossible de modifier une campagne ' . $statusCancelled);
        }

        $form = $this->createForm(CountingCampaignType::class, $countingCampaign);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                // Vérification si la collection de SiteAgentsGroup est vide
                if ($countingCampaign->getSiteAgentsGroups()->isEmpty()) {
                    $this->addFlash('error', 'Vous devez sélectionner au moins un site et un groupe d\'agents.');
                    return $this->redirectToRoute('app_counting_campaign_edit', ['id' => $countingCampaign->getId()]);
                }
                // Persister chaque SiteAgentsGroup si ce n'est pas déjà fait
                foreach ($countingCampaign->getSiteAgentsGroups() as $siteAgentsGroup) {
                    $siteAgentsGroup->setCountingCampaign($countingCampaign);
                    $siteAgentsGroup->setCreatedAt(new \DateTimeImmutable());
                    $entityManager->persist($siteAgentsGroup);
                }

                $countingCampaign->setUpdatedAt(new \DateTimeImmutable());
                // Générer et définir le nom de la campagne
                $countingCampaign->generateCampaignName();
                $this->updateCampaignStatus($countingCampaign, $campaignStatusRepository);
    
                // Enregistrer les changements
                $entityManager->flush();
                $this->addFlash('success', "La campagne de comptage a bien été modifiée.");
    
                return $this->redirectToRoute('app_counting_campaign_index', [], Response::HTTP_SEE_OTHER);

            } else {
                $this->addFlash('error','Une erreur s\'est produite lors de la modification de la campagne.');
            }
        }

        return $this->render('counting_campaign/edit.html.twig', [
            'counting_campaign' => $countingCampaign,
            'form' => $form,
        ]);
    }

    #[IsGranted(CountingCampaignVoter::DELETE, 'countingCampaign')]
    #[Route('/{id}', name: 'app_counting_campaign_delete', methods: ['POST'])]
    public function delete(Request $request, CountingCampaign $countingCampaign, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$countingCampaign->getId(), $request->getPayload()->get('_token'))) {
            try {
                $entityManager->remove($countingCampaign);
                $entityManager->flush();
                $this->addFlash('success', "Campagne de comptage a bien été supprimée");
            } catch (\Exception $e) {
                $this->addFlash('error', "Erreur lors de la suppression : " . $e->getMessage());
            }
        }  else {
            // Ajouter un message d'erreur si le jeton CSRF est invalide
            $this->addFlash('error', 'Jeton CSRF invalide. Suppression annulée.');
        }

        return $this->redirectToRoute('app_counting_campaign_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/suspend', name: 'app_counting_campaign_suspend', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas accès à cette fonction.')]
    public function suspendCampaign(CampaignStatusRepository $campaignStatusRepository, CountingCampaign $countingCampaign, EntityManagerInterface $entityManager): Response
    {
        // Appel de la méthode pour récupérer les statuts
        $statuses = $this->campaignStatusService->getCampaignStatuses();
        $statusClosed = $statuses['statusClosed'];
        $statusSuspended = $statuses['statusSuspended'];
        $statusCancelled = $statuses['statusCancelled'];

        // Si la campagne n'est ni "Clôturé" ni déjà en "Suspens"
        if ($countingCampaign->getCampaignStatus() != $statusClosed && $countingCampaign->getCampaignStatus() != $statusSuspended && $countingCampaign->getCampaignStatus() !== $statusCancelled) {
            $countingCampaign->setCampaignStatus($statusSuspended);
            $entityManager->flush();
            $this->addFlash('success', 'La campagne a été mise en suspens.');
        } 
        // Si la campagne est déjà en "Suspens", on la reprend
        elseif ($countingCampaign->getCampaignStatus() == $statusSuspended) {
            // Enlever le statut "Suspens" avant de recalculer le nouveau statut
            $countingCampaign->setCampaignStatus(null); // Supprimer l'état suspens pour forcer la mise à jour
            $this->updateCampaignStatus($countingCampaign, $campaignStatusRepository);
            $entityManager->flush();
            $this->addFlash('success', 'La campagne a été reprise.');
        } 
        // Sinon, la campagne est clôturée, et ne peut pas être mise en suspens
        else {
            $this->addFlash('warning', "Cette campagne ne peut pas être $statusSuspended car elle est déjà $statusClosed.");
        }
    
        return $this->redirectToRoute('app_counting_campaign_show', ['id' => $countingCampaign->getId()]);
    }
    
    
    #[Route('/{id}/cancel', name: 'app_counting_campaign_cancel', methods: ['GET'])]
    #[IsGranted('ROLE_SUPER_ADMIN', message: 'Vous n\'avez pas accès à cette fonction.')]
    public function cancelCampaign(CampaignStatusRepository $campaignStatusRepository, CountingCampaign $countingCampaign, EntityManagerInterface $entityManager): Response
    {
        // Appel de la méthode pour récupérer les statuts
        $statuses = $this->campaignStatusService->getCampaignStatuses();
        $statusClosed = $statuses['statusClosed'];
        $statusSuspended = $statuses['statusSuspended'];
        $statusCancelled = $statuses['statusCancelled'];

        // Si la campagne n'est ni "Clôturé", ni annulée, ni déjà en "Suspens"
        if ($countingCampaign->getCampaignStatus() != $statusClosed 
            && $countingCampaign->getCampaignStatus() != $statusSuspended 
            && $countingCampaign->getCampaignStatus() !== $statusCancelled) {
            try {
                $countingCampaign->setCampaignStatus($statusCancelled);
                $entityManager->flush();
                $this->addFlash('success', "La campagne a bien été $statusCancelled.");
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de l\'annulation de la campagne : ' . $e->getMessage());
                return $this->redirectToRoute('app_counting_campaign_index', [], Response::HTTP_SEE_OTHER);
            }
        } 
        elseif ($countingCampaign->getCampaignStatus() == $statusSuspended) {
            // Enlever le statut "Suspens" avant l'annulation
            $this->addFlash('success', "La campagne est actuellement $statusSuspended. Veuillez la lever avant d'annuler.");
        } 
        // Sinon, la campagne est clôturée, et ne peut pas être mise en suspens
        else {
            // Si la campagne est déjà clôturée ou annulée
            $this->addFlash('warning', "Cette campagne est déjà $statusClosed ou $statusCancelled, elle ne peut pas être modifiée.");
        }
    
        return $this->redirectToRoute('app_counting_campaign_show', ['id' => $countingCampaign->getId()]);
    }
    
    #[Route('/{id}/close', name: 'app_counting_campaign_close', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas accès à cette fonction.')]
    public function close(CampaignStatusRepository $campaignStatusRepository, CountingCampaign $countingCampaign, EntityManagerInterface $entityManager): Response
    {
        return $this->closeCampaign($campaignStatusRepository, $countingCampaign, $entityManager);
    }

    /**
     * Clôture la campagne si toutes les conditions sont remplies
     */
    public function closeCampaign(CampaignStatusRepository $campaignStatusRepository, CountingCampaign $countingCampaign, EntityManagerInterface $entityManager)
    {
        try {
            // Appel de la méthode pour récupérer les statuts
            $statuses = $this->campaignStatusService->getCampaignStatuses();
            $statusClosed = $statuses['statusClosed'];

            // Valider la campagne avant de la clôturer
            if ($this->validateCampaignClosure($campaignStatusRepository,$countingCampaign)) {
                $countingCampaign->setCampaignStatus($statusClosed);
                $entityManager->flush();
                $this->addFlash('success', "La campagne a été $statusClosed avec succès.");
            } 

        } catch (\Exception $e) {
            $this->addFlash('error', "Une erreur est survenue lors de la $statusClosed de la campagne : " . $e->getMessage());
        }

        return $this->redirectToRoute('app_counting_campaign_show', ['id' => $countingCampaign->getId()]);
    }
    
    /**
     * Valide si la campagne peut être clôturée
     *
     * @param CountingCampaign $countingCampaign
     * @return bool
     */
    private function validateCampaignClosure(CampaignStatusRepository $campaignStatusRepository, CountingCampaign $countingCampaign): bool
    {
        // Récupérer les statuts de campagne indexés
        $campaignStatuses = $campaignStatusRepository->findBy([], ['createdAt' => 'ASC']);
        $statusClosed = $campaignStatuses[3];  // "Clôturée"

        // Vérification des dates de début et de fin de la campagne
        $now = new \DateTimeImmutable();
        if ($countingCampaign->getEndDate() > $now) {
            $this->addFlash('warning', "La campagne ne peut pas être $statusClosed avant sa date de fin.");
            return false;
        }
        // Vérifier si la campagne a des sites associés
        if ($countingCampaign->getSiteAgentsGroups()->isEmpty()) {
            $this->addFlash('warning', "Aucun site n'est associé à la campagne.");
            return false;
        }

        // Parcourir tous les groupes de sites associés à la campagne
        foreach ($countingCampaign->getSiteAgentsGroups() as $siteAgentsGroup) {
            $site = $siteAgentsGroup->getSiteCollection();

            // Vérification de l'existence des groupes d'agents pour chaque site
            $agentsGroup = $siteAgentsGroup->getAgentsGroup();
            if ($agentsGroup === null || $agentsGroup->isEmpty()) {
                $this->addFlash('warning', "Aucun groupe d'agents n'est assigné au site: " . $site->getSiteName());
                return false;
            }
            
            // Vérifier si le site existe
            if (!$site) {
                $this->addFlash('warning', "Le site associé est manquant.");
                return false;
            }

            // Vérifier si les conditions environnementales sont présentes pour le site
            if ($site->getEnvironmentalConditions()->isEmpty()) {
                $this->addFlash('warning', "Le site {$site->getSiteName()} n'a pas de conditions environnementales.");
                return false;
            }

            foreach ($site->getEnvironmentalConditions() as $condition) {
                // Vérifier si les collectes sont présentes
                if ($condition->getCollectedData() == null) {
                    $this->addFlash('warning', "Conditions environnementales {$condition->getId()} du site {$site->getSiteName()} est sans collecte de données");
                    return false;
                }
            }

            // Parcourir toutes les collectes associées au site
            foreach ($site->getCollectedData() as $collectedData) {

                // Vérifier si les conditions environnementales sont associées à chaque collecte
                if ($collectedData->getEnvironmentalConditions() == null) {
                    $this->addFlash('warning', "Une collecte de données pour le site {$site->getSiteName()} n'a pas de conditions environnementales.");
                    return false;
                }

                // Vérifier si des comptages d'espèces sont présents pour chaque collecte
                if ($collectedData->getBirdSpeciesCounts()->isEmpty()) {
                    $this->addFlash('warning', "La collecte de données pour le site {$site->getSiteName()} n'a pas de comptage d'espèces d'oiseaux.");
                    return false;
                }
            }
        }

        // Vérification supplémentaire pour s'assurer que la campagne est dans un état pouvant être clôturé
        if ($countingCampaign->getCampaignStatus() == $statusClosed) {
            $this->addFlash('warning', "Cette campagne est déjà $statusClosed");
            return false;
        }

        // Si toutes les vérifications passent, la campagne peut être clôturée
        return true;
    }



    /**
     * Met à jour automatiquement le statut d'une campagne en fonction des dates de début et de fin,
     * en se basant sur l'ordre des statuts dans la base de données.
     */
    private function updateCampaignStatus(CountingCampaign $countingCampaign, CampaignStatusRepository $campaignStatusRepository): void
    {
        $now = new \DateTimeImmutable();
        $startDate = $countingCampaign->getStartDate();
        $endDate = $countingCampaign->getEndDate();
        $currentStatus = $countingCampaign->getCampaignStatus();

        // Récupérer tous les statuts de campagne classés par ordre (par exemple, par champ 'createdAt' ou un autre champ d'ordre)
        $campaignStatuses = $campaignStatusRepository->findBy([], ['createdAt' => 'ASC']);

        // Si aucun statut n'est défini dans la base de données, sortir de la fonction
        if (empty($campaignStatuses)) {
            throw new \Exception("Aucun statut de campagne n'est disponible.");
        }

        /**
         * Indexation des statuts (exemple hypothétique) :
         * $campaignStatuses[0] -> "Planifiée"
         * $campaignStatuses[1] -> "En cours"
         * $campaignStatuses[2] -> "Terminée"
         * $campaignStatuses[3] -> "Clôturée"
         * $campaignStatuses[4] -> "Erreur"
         * $campaignStatuses[5] -> "Suspendue"
         * $campaignStatuses[6] -> "Annulée"
        */

        // Si le statut actuel est "Suspendue" (6ème position), ne rien changer
        if ($currentStatus && $currentStatus === $campaignStatuses[5]) {
            return; // Préserver le statut "Suspendue"
        }

        // Si le statut n'est pas "Clôturée" (4ème position)
        if (!$currentStatus || $currentStatus !== $campaignStatuses[3]) {
            if ($startDate > $now) {
                // Campagne à venir -> Statut "Planifiée" (1ère position)
                $newStatus = $campaignStatuses[0]; // "Planifiée"
            } elseif ($startDate <= $now && $endDate >= $now) {
                // Campagne en cours -> Statut "En cours" (2ème position)
                $newStatus = $campaignStatuses[1]; // "En cours"
            } elseif ($endDate < $now) {
                // Campagne terminée -> Statut "Terminée" (3ème position)
                $newStatus = $campaignStatuses[2]; // "Terminée"
            } else {
                // Statut d'erreur (5ème position)
                $newStatus = $campaignStatuses[4]; // "Erreur"
            }

            // Mettre à jour le statut de la campagne
            $countingCampaign->setCampaignStatus($newStatus);
        }
    }
    
}
