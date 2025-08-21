<?php

namespace App\Controller;

use App\Entity\CollectedData;
use App\Entity\CountingCampaign;
use App\Entity\SiteCollection;
use App\Entity\User;
use App\Service\CampaignStatusService;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Entity\EnvironmentalConditions;
use App\Form\EnvironmentalConditionsType;
use App\Repository\EnvironmentalConditionsRepository;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/user/environmental/conditions')]
class EnvironmentalConditionsController extends AbstractController
{
    private $campaignStatusService;
    
    public function __construct(CampaignStatusService $campaignStatusService, private readonly TranslatorInterface $translator)
    {
        $this->campaignStatusService = $campaignStatusService;
    }
    
    #[Route('/', name: 'app_environmental_conditions_index', methods: ['GET'])]
    public function index(EnvironmentalConditionsRepository $environmentalConditionsRepository): Response
    {
        $user = $this->getUser();
        
        // Vérifier si l'utilisateur a le rôle ADMIN
        if ($this->isGranted('ROLE_ADMIN')) {
            $environmentalConditions = $environmentalConditionsRepository->findAll();
        } else {
            $environmentalConditions = $environmentalConditionsRepository->findByUser($user);
        }
        return $this->render('environmental_conditions/index.html.twig', [
            'environmental_conditions' => $environmentalConditions,
        ]);
    }


    #[IsGranted('ROLE_COLLECTOR', message: 'Vous n\'avez pas l\'accès.')]
    #[Route('/new', name: 'app_environmental_conditions_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser(); // Récupérer l'utilisateur actuel
        $siteId = $request->query->get('siteId');
        $campaignId = $request->query->get('campaignId');
        $collectId = $request->query->get('collectId');

        $site = $entityManager->getRepository(SiteCollection::class)->find($siteId);
        $campaign = $entityManager->getRepository(CountingCampaign::class)->find($campaignId);
        
        if ($collectId) {
            $collect = $entityManager->getRepository(CollectedData::class)->find($collectId);
        } else {
            $collect = null;
        }
        
        // Vérifier si la campagne et le site existent
        if (!$campaign) {
            $this->addFlash('error', $this->translator->trans('condition.campaign_not_found'));
            return $this->redirectToRoute('app_counting_campaign_index');
        }
        
        if (!$site) {
            $this->addFlash('error', $this->translator->trans('condition.site_not_found'));
            return $this->redirectToRoute('app_counting_campaign_index');
        }
        
        // Si l'utilisateur n'est pas membre d'un groupe, interdire l'accès
        if (!$this->isUserSiteMember($user, $site) && !$this->isGranted('ROLE_SUPER_ADMIN')) {
            $this->addFlash('warning', $this->translator->trans('member_of_a_group_assigned_to_this_site'));
            return $this->redirectToRoute('app_environmental_conditions_index', [], Response::HTTP_SEE_OTHER);
        }
        
        // Vérifier si la campagne est modifiable
        if ($campaign && !$this->campaignStatusService->ensureCampaignIsEditable($campaign)) {
            // La vérification échoue, un message flash est déjà ajouté par le service
            $this->addFlash('error', $this->translator->trans('campaign.cannot_be_modified', ['%status%' => $campaign->getCampaignStatus()]));
            return $this->redirectToRoute('app_counting_campaign_show', ['id' => $campaignId]);
        }


        // Vérifiez si l'utilisateur a déjà créé une condition environnementale pour ce site et cette campagne
        $existingCondition = $entityManager->getRepository(EnvironmentalConditions::class)->findOneBy([
            'user' => $user,
            'siteCollection' => $site,
            'countingCampaign' => $campaign
        ]);
        
        $environmentalCondition = new EnvironmentalConditions();
        $form = $this->createForm(EnvironmentalConditionsType::class, $environmentalCondition);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                try{
                    $environmentalCondition->setCreatedAt(new \DateTimeImmutable());
                    $environmentalCondition->setSiteCollection($site);
                    $environmentalCondition->setCountingCampaign($campaign);
                    $environmentalCondition->setUser($user);
                      
                    if ($collect !== null  && $collect->getEnvironmentalConditions() === null) {
                        $environmentalCondition->setCollectedData($collect);
                        $entityManager->persist($environmentalCondition);
                        $entityManager->flush();
                        $this->addFlash('success', $this->translator->trans('collect.condition'));
                        return $this->redirectToRoute('app_collected_data_show', ['id' => $collect->getId()  ], Response::HTTP_SEE_OTHER);
                    } else {
                        $entityManager->persist($environmentalCondition);
                        $entityManager->flush();
                        $this->addFlash('success', $this->translator->trans('condition.msg.created_success'));
                        return $this->redirectToRoute('app_collected_data_new', ['campaignId' => $campaignId, 'siteId' => $siteId  ], Response::HTTP_SEE_OTHER);
                    }
                } catch (\Exception $e) {
                    $this->addFlash('error', $e->getMessage());
                    return $this->redirectToRoute('app_environmental_conditions_new', [], Response::HTTP_SEE_OTHER);
                }
            } else {
                $this->addFlash('error',$this->translator->trans('condition.msg.created_error'));
            }
        }

        return $this->render('environmental_conditions/new.html.twig', [
            'environmental_condition' => $environmentalCondition,
            'form' => $form,
            'siteCollection' => $site,
            'countingCampaign' => $campaign,
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


    #[IsGranted('ROLE_VIEW', message: 'Vous n\'avez pas l\'accès.')]
    #[Route('/{id}', name: 'app_environmental_conditions_show', methods: ['GET'])]
    public function show(EnvironmentalConditions $environmentalCondition): Response
    {
        // Vérifiez si l'utilisateur est admin, créateur ou membre du groupe
        if ($this->isGranted('ROLE_ADMIN') || $this->isGranted('ROLE_VIEW') || $environmentalCondition->getUser() === $this->getUser()) {
            return $this->render('environmental_conditions/show.html.twig', [
                'environmental_condition' => $environmentalCondition,
            ]);
        } else {
            $this->addFlash('info', $this->translator->trans('condition.access_denied_environment'));
            return $this->redirectToRoute('app_environmental_conditions_index');
        }
    }

    #[IsGranted('ROLE_EDIT', message: 'Vous n\'avez pas l\'accès.')]
    #[Route('/{id}/edit', name: 'app_environmental_conditions_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, EnvironmentalConditions $environmentalCondition, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser(); // Récupérer l'utilisateur actuel
        $campaign = $environmentalCondition->getCountingCampaign();

        $statuses =$this->campaignStatusService->getCampaignStatuses();
        $statusClosed = $statuses['statusClosed'];
        $statusSuspended = $statuses['statusSuspended'];
        $statusCancelled = $statuses['statusCancelled'];
        
        if ($user != $environmentalCondition->getUser() && !$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_EDIT')) {
            $this->addFlash('error', $this->translator->trans('campaign.not_allowed_modify'));
            return $this->redirectToRoute('app_environmental_conditions_index', [], Response::HTTP_SEE_OTHER);
        }
        
        if ($campaign->getCampaignStatus() === $statusClosed) {
            throw $this->createNotFoundException($this->translator->trans('campaign.campaign_closed_no_modify'));
        }
        if ($campaign->getCampaignStatus() === $statusSuspended) {
            throw $this->createNotFoundException($this->translator->trans('campaign.campaign_suspended_no_modify'));
        }
        if ($campaign->getCampaignStatus() === $statusCancelled) {
            throw $this->createNotFoundException($this->translator->trans('campaign.campaign_cancelled_no_modify'));
        }
        
        $form = $this->createForm(EnvironmentalConditionsType::class, $environmentalCondition);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                try {
                    $environmentalCondition->setUpdatedAt(new \DateTimeImmutable());
                    $entityManager->flush();
                    $this->addFlash('success', $this->translator->trans('condition.msg.updated_success'));
        
                    return $this->redirectToRoute('app_environmental_conditions_index', [], Response::HTTP_SEE_OTHER);
                } catch (\Exception $e) {
                    $this->addFlash('error', $e->getMessage());
                    return $this->redirectToRoute('app_environmental_conditions_edit', ['id' => $environmentalCondition->getId()], Response::HTTP_SEE_OTHER);
                }
            } else {
                $this->addFlash('error',$this->translator->trans('condition.msg.updated_error'));
            }
        }

        return $this->render('environmental_conditions/edit.html.twig', [
            'environmental_condition' => $environmentalCondition,
            'form' => $form,
            'countingCampaign' => $campaign,
        ]);
    }

    #[Route('/{id}', name: 'app_environmental_conditions_delete', methods: ['POST'])]
    public function delete(Request $request, EnvironmentalConditions $environmentalCondition, EntityManagerInterface $entityManager): Response
    {
        try {
            if ($this->getUser() !== $environmentalCondition->getUser() && !$this->isGranted('ROLE_DELETE')){
                $this->addFlash('error', $this->translator->trans('delete_permission'));
                return $this->redirectToRoute('app_environmental_conditions_index', [], Response::HTTP_SEE_OTHER);
            }
            if ($this->isCsrfTokenValid('delete'.$environmentalCondition->getId(), $request->getPayload()->get('_token'))) {
                    $entityManager->remove($environmentalCondition);
                    $entityManager->flush();
                    $this->addFlash('success', $this->translator->trans('condition.msg.deleted_success'));
            } else {
                $this->addFlash('error',$this->translator->trans('condition.msg.deleted_error'));
            }
        } catch (\Exception $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('app_environmental_conditions_index', [], Response::HTTP_SEE_OTHER);
    }
}
