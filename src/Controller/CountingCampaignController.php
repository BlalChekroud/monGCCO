<?php

namespace App\Controller;

use App\Entity\EnvironmentalConditions;
use App\Security\Voter\CountingCampaignVoter;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Monolog\DateTimeImmutable;
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
    #[Route('/', name: 'app_counting_campaign_index', methods: ['GET'])]
    public function index(CountingCampaignRepository $countingCampaignRepository, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        
        // Vérifier si l'utilisateur a le rôle ADMIN
        if ($this->isGranted('ROLE_ADMIN')) {
            $countingCampaigns = $countingCampaignRepository->findAll();
        } else {
            $countingCampaigns = $countingCampaignRepository->findByUser($user);
        }
        
        $needsFlush = false; // Variable pour suivre si flush est nécessaire
    
        foreach ($countingCampaigns as $countingCampaign) {
            // Vérifier si la campagne n'est ni clôturée ni en suspens avant de mettre à jour le statut
            if ($countingCampaign->getCampaignStatus() !== 'Clôturé' && $countingCampaign->getCampaignStatus() !== 'Suspens') {
                $previousStatus = $countingCampaign->getCampaignStatus(); // Sauvegarder le statut précédent
                
                // Mettre à jour le statut
                $this->updateCampaignStatus($countingCampaign);
                
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
        ]);
    }

    #[IsGranted(CountingCampaignVoter::CREATE)]
    #[Route('/new', name: 'app_counting_campaign_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
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
                $this->updateCampaignStatus($countingCampaign);
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
    public function show(CountingCampaign $countingCampaign, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();  // Utilisateur actuel
    
        // Récupérer les SiteAgentsGroups associés à la campagne
        $siteAgentsGroups = $countingCampaign->getSiteAgentsGroups();
    
        // Tableau pour stocker les SiteCollection et les conditions environnementales
        $sites = [];
        $existingConditions = [];
    
        // Mettre à jour le statut de la campagne avant l'affichage uniquement si cela est nécessaire
        if ($countingCampaign->getCampaignStatus() !== 'Clôturé' && $countingCampaign->getCampaignStatus() !== 'Suspens') {
            $previousStatus = $countingCampaign->getCampaignStatus(); // Sauvegarder le statut précédent
            $this->updateCampaignStatus($countingCampaign);
    
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
        ]);
    }
    


    #[IsGranted(CountingCampaignVoter::EDIT, 'countingCampaign')]
    #[Route('/{id}/edit', name: 'app_counting_campaign_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, CountingCampaign $countingCampaign, EntityManagerInterface $entityManager): Response
    {
        if ($countingCampaign->getCampaignStatus() == 'Clôturé') {
            throw $this->createNotFoundException('Impossible de modifier une campagne clôturée.');
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
                $this->updateCampaignStatus($countingCampaign);
    
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
    public function suspendCampaign(CountingCampaign $countingCampaign, EntityManagerInterface $entityManager): Response
    {
        // Si la campagne n'est ni "Clôturé" ni déjà en "Suspens"
        if ($countingCampaign->getCampaignStatus() != 'Clôturé' && $countingCampaign->getCampaignStatus() != 'Suspens') {
            $countingCampaign->setCampaignStatus('Suspens');
            $entityManager->flush();
            $this->addFlash('success', 'La campagne a été mise en suspens.');
        } 
        // Si la campagne est déjà en "Suspens", on la reprend
        elseif ($countingCampaign->getCampaignStatus() == 'Suspens') {
            // Enlever le statut "Suspens" avant de recalculer le nouveau statut
            $countingCampaign->setCampaignStatus(''); // Supprimer l'état suspens pour forcer la mise à jour
            $this->updateCampaignStatus($countingCampaign);
            $entityManager->flush();
            $this->addFlash('success', 'La campagne a été reprise.');
        } 
        // Sinon, la campagne est clôturée, et ne peut pas être mise en suspens
        else {
            $this->addFlash('warning', 'Cette campagne ne peut pas être mise en suspens car elle est déjà clôturée.');
        }
    
        return $this->redirectToRoute('app_counting_campaign_show', ['id' => $countingCampaign->getId()]);
    }
    


    #[Route('/{id}/close', name: 'app_counting_campaign_close', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas accès à cette fonction.')]
    public function close(CountingCampaign $countingCampaign, EntityManagerInterface $entityManager): Response
    {
        return $this->closeCampaign($countingCampaign, $entityManager);
    }

    /**
     * Clôture la campagne si toutes les conditions sont remplies
     */
    public function closeCampaign(CountingCampaign $countingCampaign, EntityManagerInterface $entityManager)
    {
        try {
            // Valider la campagne avant de la clôturer
            if ($this->validateCampaignClosure($countingCampaign)) {
                $countingCampaign->setCampaignStatus('Clôturé');
                $entityManager->flush();
                $this->addFlash('success', 'La campagne a été clôturée avec succès.');
            } 

        } catch (\Exception $e) {
            $this->addFlash('error', 'Une erreur est survenue lors de la clôture de la campagne : ' . $e->getMessage());
        }

        return $this->redirectToRoute('app_counting_campaign_show', ['id' => $countingCampaign->getId()]);
    }
    
    /**
     * Valide si la campagne peut être clôturée
     *
     * @param CountingCampaign $countingCampaign
     * @return bool
     */
    private function validateCampaignClosure(CountingCampaign $countingCampaign): bool
    {
        // Vérification des dates de début et de fin de la campagne
        $now = new \DateTimeImmutable();
        if ($countingCampaign->getEndDate() > $now) {
            $this->addFlash('warning', "La campagne ne peut pas être clôturée avant sa date de fin.");
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
        if ($countingCampaign->getCampaignStatus() == 'Clôturé') {
            $this->addFlash('warning', "Cette campagne est déjà clôturée.");
            return false;
        }

        // Si toutes les vérifications passent, la campagne peut être clôturée
        return true;
    }



    /**
     * Met à jour automatiquement le statut d'une campagne en fonction des dates de début et de fin.
     */
    private function updateCampaignStatus(CountingCampaign $countingCampaign): void
    {
        $now = new \DateTimeImmutable();
        $startDate = $countingCampaign->getStartDate();
        $endDate = $countingCampaign->getEndDate();
        $status = $countingCampaign->getCampaignStatus();

        // Ne pas changer l'état si la campagne est en suspens
        if ($status === 'Suspens') {
            return;  // Quitter la fonction pour préserver l'état "Suspens"
        }

        // if (!$status || $status === 'En attente' || $status === 'En cours' || $status === 'En attente') {
        if (!$status || $status !== 'Clôturé') {
            if ($startDate > $now) {
                $countingCampaign->setCampaignStatus('En attente');
            } elseif ($startDate <= $now && $endDate >= $now) {
                $countingCampaign->setCampaignStatus('En cours');
            } elseif ($endDate < $now) {
                $countingCampaign->setCampaignStatus('Terminé');
            } else {
                $countingCampaign->setCampaignStatus('Erreur');
            }
        }

    }
    
}
