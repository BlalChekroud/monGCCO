<?php

namespace App\Controller;

use App\Entity\CountingCampaign;
use App\Entity\EnvironmentalConditions;
use App\Entity\SiteCollection;
use App\Entity\User;
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
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/user/collected/data')]
class CollectedDataController extends AbstractController
{
    private $campaignStatusService;

    public function __construct(CampaignStatusService $campaignStatusService)
    {
        $this->campaignStatusService = $campaignStatusService;
    }
    

    #[Route('/', name: 'app_collected_data_index', methods: ['GET'])]
    public function index(CollectedDataRepository $collectedDataRepository, TranslatorInterface $translator): Response
    {
        $user = $this->getUser();

        // Vérifiez si l'utilisateur a l'un des rôles
        if (!$this->isGranted('ROLE_VIEW') && !$this->isGranted('ROLE_COLLECTOR')) {
            $this->addFlash('error', $translator->trans('access'));
            return $this->redirectToRoute('home');
        }

        if ($this->isGranted('ROLE_ADMIN')) {
            $collectedDatas = $collectedDataRepository->findAll();
        } else {
            // $collectedDatas = $collectedDataRepository->findBy(['createdBy' => $user]);
            $collectedDatas = $collectedDataRepository->getCollectesByUser($user);  // Récupérer les collectes où l'utilisateur est leader
        }
        return $this->render('collected_data/index.html.twig', [
            'collected_datas' => $collectedDatas,
        ]);
    }

    #[IsGranted('ROLE_COLLECTOR', message: 'Vous n\'avez pas l\'accès.')]
    #[Route('/new', name: 'app_collected_data_new', methods: ['GET', 'POST'])]
    public function new(Request $request, BirdSpeciesRepository $birdSpeciesRepository, EntityManagerInterface $entityManager, EnvironmentalConditionsRepository $environmentalConditionsRepository, TranslatorInterface $translator): Response
    {
        // Récupérer l'utilisateur actuel
        $user = $this->getUser();

        // $statuses =$this->campaignStatusService->getCampaignStatuses();

        // Récupérer la campagne et le site en fonction des paramètres de la requête ou d'un choix de l'utilisateur
        $campaignId = $request->query->get('campaignId');
        $siteId = $request->query->get('siteId');

        $campaign = $entityManager->getRepository(CountingCampaign::class)->find($campaignId);
        $site = $entityManager->getRepository(SiteCollection::class)->find($siteId);

        // Vérifier si la campagne et le site existent
        if (!$campaign) {
            $this->addFlash('error', 'La campagne spécifiée est introuvable.');
            return $this->redirectToRoute('app_collected_data_index');
        }
        
        if (!$site) {
            $this->addFlash('error', 'Le site spécifié est introuvable.');
            return $this->redirectToRoute('app_collected_data_index');
        }
        
        // Si l'utilisateur n'est pas membre d'un groupe, interdire l'accès
        if (!$this->isUserSiteMember($user, $site)) {
            $this->addFlash('warning', $translator->trans('member_of_a_group_assigned_to_this_site'));
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
        
        // if (!$environmentalConditions || $environmentalConditions->getCollectedData()) {
        //     // Rediriger vers la page de création des conditions environnementales si elles n'existent pas
        //     $this->addFlash('warning', $translator->trans('collect.please_create_the_environmental_conditions_for_this_site_first'));
        //     return $this->redirectToRoute('app_environmental_conditions_new', [
        //         'campaignId' => $campaignId,
        //         'siteId' => $siteId
        //     ]);
        // }
        
        // Vérifier si la campagne est modifiable
        if ($campaign && !$this->campaignStatusService->ensureCampaignIsEditable($campaign)) {
            // La vérification échoue, un message flash est déjà ajouté par le service
            $this->addFlash('error', $translator->trans('campaign.cannot_be_modified', ['%status%' => $campaign->getCampaignStatus()]));
            return $this->redirectToRoute('app_counting_campaign_show', ['id' => $campaignId]);
        }
        

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
                if ($this->validateCollectedData($collectedDatum, $environmentalConditions)) {
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
                    $this->addFlash('error', "Erreur lors de la création de la collecte.");
                }

            } else {
                $this->addFlash('error',$translator->trans('invalid_form'));
            }
        }

        return $this->render('collected_data/new.html.twig', [
            'collected_datum' => $collectedDatum,
            'form' => $form,
            'siteCollection' => $site,
            'campaign_id' => $campaignId,
            'environmentalConditions' => $environmentalConditions,
            'birdSpecies' => $birdSpecies,
        ]);
    }

    private function isUserSiteMember(User $user, SiteCollection $site): bool
    {
        foreach ($site->getSiteAgentsGroups() as $siteAgentsGroup) {
            foreach ($siteAgentsGroup->getAgentsGroup() as $group) {
                if ($group->getGroupMember()->contains($user)) {
                    return true;
                }
            }
        }
        return false;
    }

    
    /**
     * Valide les données collectées pour correspondre aux conditions environnementales.
     */
    private function validateCollectedData(CollectedData $collectedDatum, EnvironmentalConditions $environmentalConditions): bool
    {
        if ($collectedDatum->getTotalCount() <= 0) {
            $this->addFlash('error', "Le total des comptages d'oiseaux doit être positif.");
            return false;
        }

        if ($collectedDatum->getSiteCollection() !== $environmentalConditions->getSiteCollection()) {
            $this->addFlash('error', "Le site de collecte ne correspond pas aux conditions environnementales.");
            return false;
        }

        if ($collectedDatum->getBirdSpeciesCounts()->isEmpty()) {
            $this->addFlash('error', "Aucune espèce d'oiseau sélectionnée.");
            return false;
        }

        if ($collectedDatum->getMethod()->isEmpty()) {
            $this->addFlash('error', "Aucune méthode de collecte sélectionnée.");
            return false;
        }

        if ($collectedDatum->getQuality() === null) {
            $this->addFlash('error', "La qualité de la collecte est requise.");
            return false;
        }

        if ($collectedDatum->getCountType() === null) {
            $this->addFlash('error', "Le type de comptage est requis.");
            return false;
        }

        return true;
    }


    #[Route('/{id}', name: 'app_collected_data_show', methods: ['GET'])]
    public function show(CollectedData $collectedDatum, CollectedDataRepository $collectedDataRepository): Response
    {
        $user = $this->getUser();
        $teamLeader = $collectedDataRepository->getLeaderByCollectedData($collectedDatum);
        // Vérification du type de $teamLeader
        if (is_array($teamLeader)) {
            // Si c'est un tableau, on accède à l'email via la clé 'email'
            $leaderEmail = $teamLeader['email'];
        } elseif (is_object($teamLeader) && method_exists($teamLeader, 'getEmail')) {
            // Si c'est un objet et qu'il a la méthode getEmail()
            $leaderEmail = $teamLeader->getEmail();
        } else {
            // Si $teamLeader n'est ni un tableau, ni un objet avec la méthode getEmail
            $leaderEmail = null;
        }

        // Vérifiez si l'utilisateur est admin, créateur ou membre du groupe
        if ($this->isGranted('ROLE_ADMIN') || $this->isGranted('ROLE_VIEW') || $collectedDatum->getCreatedBy() === $user || $leaderEmail === $user->getEmail()) {
            return $this->render('collected_data/show.html.twig', [
                'collected_datum' => $collectedDatum,
                'teamLeader' => $teamLeader,
            ]);
            
        } else {
            $this->addFlash('info', 'Vous n\'avez pas accès à cette collecte.');
            return $this->redirectToRoute('app_collected_data_index');
        }
    }

    #[Route('/{id}/edit', name: 'app_collected_data_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, CollectedData $collectedDatum, EntityManagerInterface $entityManager, TranslatorInterface $translator, BirdSpeciesRepository $birdSpeciesRepository): Response
    {
        $campaign = $collectedDatum->getCountingCampaign();
        $site = $collectedDatum->getSiteCollection();
        $environmentalConditions = $collectedDatum->getEnvironmentalConditions();
        
        if ($this->getUser() !== $collectedDatum->getCreatedBy() && !$this->isGranted('ROLE_EDIT')){
            $this->addFlash('error', $translator->trans('edit_permission'));
            return $this->redirectToRoute('app_collected_data_index', [], Response::HTTP_SEE_OTHER);
        }
        // Si l'utilisateur n'est pas membre d'un groupe, interdire l'accès
        if (!$this->isUserSiteMember($this->getUser(), $site)) {
            $this->addFlash('warning', $translator->trans('member_of_a_group_assigned_to_this_site'));
            return $this->redirectToRoute('app_collected_data_index');
        }
        // Vérifier si la campagne est modifiable
        if ($campaign && !$this->campaignStatusService->ensureCampaignIsEditable($campaign)) {
            // La vérification échoue, un message flash est déjà ajouté par le service
            $this->addFlash('error', $translator->trans('campaign.cannot_be_modified', ['%status%' => $campaign->getCampaignStatus()]));
            return $this->redirectToRoute('app_collected_data_index');
        }
        
        if (!$environmentalConditions) {
            // Rediriger vers la page de création des conditions environnementales si elles n'existent pas
            $this->addFlash('warning', $translator->trans('collect.please_create_the_environmental_conditions_for_this_site_first'));
            return $this->redirectToRoute('app_environmental_conditions_new', [
                'campaignId' => $campaign->getId(),
                'siteId' => $site->getId()
            ]);
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
            $birdSpecies = $birdSpeciesRepository->findAll(), // Récupérer toutes les espèces d'oiseaux
        ]);
    }

    #[Route('/{id}', name: 'app_collected_data_delete', methods: ['POST'])]
    public function delete(Request $request, CollectedData $collectedDatum, EntityManagerInterface $entityManager, TranslatorInterface $translator): Response
    {
        if ($this->getUser() !== $collectedDatum->getCreatedBy() && !$this->isGranted('ROLE_DELETE')){
            $this->addFlash('error', $translator->trans('delete_permission'));
            return $this->redirectToRoute('app_collected_data_index', [], Response::HTTP_SEE_OTHER);
        }
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
