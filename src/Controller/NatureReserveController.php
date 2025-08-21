<?php

namespace App\Controller;

use App\Entity\CountingCampaign;
use App\Entity\NatureReserve;
use App\Entity\SiteCollection;
use App\Form\ExportType;
use App\Form\NatureReserveType;
use App\Repository\AgentsGroupRepository;
use App\Repository\BirdSpeciesCountRepository;
use App\Repository\CollectedDataRepository;
use App\Repository\CountingCampaignRepository;
use App\Repository\EnvironmentalConditionsRepository;
use App\Repository\NatureReserveRepository;
use App\Repository\SiteCollectionRepository;
use App\Service\CampaignStatusService;
use App\Service\ExportService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/user/nature/reserve')]
class NatureReserveController extends AbstractController
{
    public function __construct(private readonly TranslatorInterface $translator, private readonly CampaignStatusService $campaignStatusService) {}

    #[Route('/', name: 'app_nature_reserve_index', methods: ['GET'])]
    public function index(NatureReserveRepository $natureReserveRepository): Response
    {
        $user = $this->getUser();
        // Vérifier si l'utilisateur a le rôle ADMIN
        if ($this->isGranted('ROLE_ADMIN') && $this->isGranted('ROLE_VIEW')) {
            $natureReserves = $natureReserveRepository->findAll();
        } else {
            // $natureReserves = $natureReserveRepository->findByUser($user);
            $natureReserves = $natureReserveRepository->findAll();
        }
        
        return $this->render('nature_reserve/index.html.twig', [
            'nature_reserves' => $natureReserves,
        ]);
    }

    #[Route('/new', name: 'app_nature_reserve_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        $natureReserve = new NatureReserve();
        $form = $this->createForm(NatureReserveType::class, $natureReserve);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                try {
                    if (!$this->valideNatureReserve($natureReserve)) {
                        return $this->redirectToRoute('app_nature_reserve_new', [], Response::HTTP_SEE_OTHER);
                    }                                             
                    $natureReserve->setCreatedAt(new \DateTimeImmutable());
                    $natureReserve->setCreatedBy($user);
                    $entityManager->persist($natureReserve);
                    $entityManager->flush();
                    $this->addFlash('success', $this->translator->trans('nature_reserve.msg.created_success'));
        
                    return $this->redirectToRoute('app_nature_reserve_index', [], Response::HTTP_SEE_OTHER);
                } catch (\Exception $e) {
                    $this->addFlash('error', $this->translator->trans('nature_reserve.msg.created_error') . $e->getMessage());
                    return $this->redirectToRoute('app_nature_reserve_new', [], Response::HTTP_SEE_OTHER);
                }
            } else {
                $this->addFlash('error',$this->translator->trans('invalid_form'));
            }
        }

        return $this->render('nature_reserve/new.html.twig', [
            'nature_reserve' => $natureReserve,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_nature_reserve_show', methods: ['GET', 'POST'])]
    public function show(
        SiteCollectionRepository $siteCollectionRepository, 
        NatureReserveRepository $natureReserveRepository, 
        NatureReserve $natureReserve, 
        CountingCampaignRepository $countingCampaignRepository,
        BirdSpeciesCountRepository $birdSpeciesCountRepository,
        EnvironmentalConditionsRepository $environmentalConditionsRepository,
        AgentsGroupRepository $agentsGroupRepository,
        Request $request,
        ExportService $exportService,
        CollectedDataRepository $collectedDataRepository,
    ): Response {

        $formExport = $this->createForm(ExportType::class);
        $formExport->handleRequest($request);

        // Appel de la méthode pour récupérer les statuts
        $statuses = $this->campaignStatusService->getCampaignStatuses();

        // Récupérer l'ID de la campagne sélectionnée depuis la requête GET
        $selectedCampaignId = $request->query->get('rsf');
        $campaignSelected = $selectedCampaignId 
            ? $countingCampaignRepository->find($selectedCampaignId) 
            : $natureReserveRepository->findMostRecentCampaignInRsf($natureReserve);

        $reserveCampaigns = $natureReserveRepository->getCampaignBySiteOfReserve($natureReserve);
        
        
        $totalCollectedDataCount =$natureReserveRepository->countCollectedDataByReserve($natureReserve, $campaignSelected);
        $campaignsBySite = [];
        $totalBirdCountsForReserveSiteInCampaign = [];
        $rsfSites = [];
        $userIsInGroup = [];
        $groupsByCampAndSite = [];
        $conditions = [];
        $collectedDataBySiteInCampaign = [];
        $speciesAndCountsByCollectedData = [];
        $totalCountForCollect = [];
        
        foreach ($natureReserve->getSiteCollections() as $site) {
            // Récupérer les campagnes associées à ce site
            $campaignsBySite[$site->getId()] = $siteCollectionRepository->getCampaignsBySiteCollection($site);
            $collectedDataBySiteInCampaign[$site->getId()][$campaignSelected->getId()] = $collectedDataRepository->getCollectedDataBySiteInCampaign($site, $campaignSelected);
            // Stocker les espèces et leurs nombres pour chaque collecte
            foreach ($collectedDataBySiteInCampaign[$site->getId()][$campaignSelected->getId()] as $collect) {
                $speciesAndCountsByCollectedData[$collect->getId()] = $collectedDataRepository->getSpeciesAndCountsByCollectedData($collect);
                $totalCountForCollect[$collect->getId()] = $collectedDataRepository->finByTotalCountForCollect($collect);
            }
            
            // Pour chaque campagne de la réserve, récupérer le total des comptages pour ce site
            foreach ($reserveCampaigns as $campaign) {
                $totalBirdCountsForReserveSiteInCampaign[$site->getId()][$campaign->getId()] =
                $siteCollectionRepository->getTotalBirdCountsForSiteInCampaign($site, $campaign);
                $groupsByCampAndSite[$campaign->getId()][$site->getId()] = $agentsGroupRepository->findGroupsByCampaignAndSite($campaign,$site);
                // Conditions
                $conditions[$campaign->getId()][$site->getId()] = $environmentalConditionsRepository->getLatestConditionForUserAndCampaign($this->getUser(), $campaign, $site);
            }
        }        
        foreach ($reserveCampaigns as $countingCampaign) {
            $totalCountBySpecies[$countingCampaign->getId()] = $countingCampaignRepository->getTotalCountBySpecies($countingCampaign->getId());
            $userIsInGroup[$countingCampaign->getId()] = $agentsGroupRepository->userIsInGroup($countingCampaign);
            $rsfSites[$countingCampaign->getId()] = $natureReserveRepository->getRsfSitesByCampaign($countingCampaign, $natureReserve);
        }

        // Total d'oiseaux comptés de la reserve dans une campagne
        $totalBirdsCountedInCampaignForReserve = $birdSpeciesCountRepository->countTotalBirdsInCampaignForReserve($natureReserve, $campaignSelected);
        
        // Récupérer les valeurs les plus choisies des conditions environnementales
        $frequentConditions = $environmentalConditionsRepository->getMostFrequentEnvCondsForReserve($natureReserve, $campaignSelected);
        $methodsUsed = $natureReserveRepository->getMethodsUsedInReserveByCampaign($natureReserve, $campaignSelected);
        $totalAgentsCount = $agentsGroupRepository->countAgentsInRsvByCountingCampaign($natureReserve, $campaignSelected);
        
        $rsfSitesByCampaign = $natureReserveRepository->getRsfSitesByCampaign($campaignSelected, $natureReserve);
        $countUniqueBirdSpeciesInCampaignForReserve = $birdSpeciesCountRepository->countUniqueBirdSpeciesInCampaignForReserve($natureReserve, $campaignSelected);
        $totalCountBySpeciesForReserve = $natureReserveRepository->getTotalCountBySpeciesForReserve($natureReserve->getId(), $campaignSelected->getId());

        if ($formExport->isSubmitted() && $formExport->isValid()) {
            if (!$this->isGranted('ROLE_EXPORT')) {
                $this->addFlash('warning', $this->translator->trans('export_permission'));
                return $this->redirectToRoute('app_nature_reserve_show', ['id' => $natureReserve->getId()]);
            }
            $columnNames = ['Nom de la réserve naturelle', 'Responsable de la réserve', 'Créé le', 'Nom de la campagne', 'Date de début', 'Date de fin', 'État de la campagne', 'Total général des comptages dans la campagne', "Total d'espèces uniques observées", 
                            'Site', 'Nombre total des oiseaux comptés', 'Disturbé(e)', 'Météo', 'Glace', 'Marée', 'Eaux',  
                            'Numéro de collecte', 'Collecteur', 'Type de comptage', 'Qualité de comptage','Méthodes de collecte utilisées', 
                            "Nom scientifique de l'Espèce", 'Nombre total'];

            $data = [];
            foreach ($rsfSitesByCampaign as $site) {
                foreach ($collectedDataRepository->getCollectedDataBySiteInCampaign($site, $campaignSelected) as $collect) {
                    // Convertir les méthodes en une seule chaîne
                    $methodLabels = array_map(
                        fn($method) => $method->getLabel(), 
                        $collect->getMethod()->toArray()
                    );
                    $methodsString = implode(', ', $methodLabels);
                    foreach ($speciesAndCountsByCollectedData[$collect->getId()] as $speciesData) {
                        $data[] = [
                            $natureReserve->getReserveName() ?? '',
                            $natureReserve->getReserveLeader() ?? '',
                            $natureReserve->getCreatedAt()->format('d-m-Y H:i:s') ?? '',
                            $campaignSelected->getCampaignName() ?? '',
                            $campaignSelected->getStartDate()->format('d-m-Y H:i:s') ?? '',
                            $campaignSelected->getEndDate()->format('d-m-Y H:i:s') ?? '',
                            $campaignSelected->getCampaignStatus()->getLabel() ?? '',
                            $totalBirdsCountedInCampaignForReserve ?? 0,
                            $countUniqueBirdSpeciesInCampaignForReserve ?? 0,
                            $site->getSiteName() ?? '',
                            $siteCollectionRepository->getTotalBirdCountsForSiteInCampaign($site, $campaignSelected) ?? 0,
                            $collect->getEnvironmentalConditions()->getDisturbed()->getLabel() ?? '',
                            $collect->getEnvironmentalConditions()->getWeather()->getLabel() ?? '',
                            $collect->getEnvironmentalConditions()->getIce()->getLabel() ?? '',
                            $collect->getEnvironmentalConditions()->getTidal()->getLabel() ?? '',
                            $collect->getEnvironmentalConditions()->getWater()->getLabel() ?? '',                           
                            $collect->getId() ?? '',
                            $collect->getCreatedBy()->getEmail() ?? '',
                            $collect->getCountType()->getLabel() ?? '',
                            $collect->getQuality()->getLabel() ?? '',
                            $methodsString ?? '',
                            $speciesData['specy'] ?? '',
                            $speciesData['count'] ?? 0,
                        ];                            
                    }
                }
            }
            $format = $formExport->get('format')->getData();
            $fileName = sprintf("Nature_reserve_export_%s", date('d-m-Y_His'));
    
            return $exportService->export($columnNames, $data, $format, $fileName);
        }

        return $this->render('nature_reserve/show.html.twig', [
            'nature_reserve' => $natureReserve,
            'reserveCampaigns' => $reserveCampaigns,
            'campaignsBySite' => $campaignsBySite,
            'totalBirdCountsForReserveSiteInCampaign' => $totalBirdCountsForReserveSiteInCampaign,
            'totalCountBySpecies' => $totalCountBySpecies,
            'totalBirdsCountedInCampaignForReserve' => $totalBirdsCountedInCampaignForReserve,
            'countUniqueBirdSpeciesInCampaignForReserve' => $countUniqueBirdSpeciesInCampaignForReserve,
            'totalCountBySpeciesForReserve' => $totalCountBySpeciesForReserve,
            'speciesAndCountsByCollectedData' => $speciesAndCountsByCollectedData,
            'totalCountForCollect' => $totalCountForCollect,
            'campaignSelected' => $campaignSelected,
            'rsfSitesByCampaign' => $rsfSitesByCampaign,
            'rsfSites' => $rsfSites,
            'userIsInGroup' => $userIsInGroup,
            'groupsByCampAndSite' => $groupsByCampAndSite,
            'conditions' => $conditions,
            'totalCollectedDataCount' => $totalCollectedDataCount,
            'frequentConditions' => $frequentConditions,
            'methodsUsed' => $methodsUsed,
            'totalAgentsCount' => $totalAgentsCount,
            'statusIndexMap' => $statuses['statusIndexMap'],
            'statusFinished' => $statuses['statusFinished'],
            'statusClosed' => $statuses['statusClosed'],
            'statusSuspended' => $statuses['statusSuspended'],
            'statusCancelled' => $statuses['statusCancelled'],
            'formExport' => $formExport->createView(),
        ]);
    }
    
    // #[Route('/{id}/export', name: 'app_nature_reserve_export', methods: ['GET', 'POST'])]
    // public function export(SiteCollectionRepository $siteCollectionRepository, CountingCampaignRepository $countingCampaignRepository, NatureReserveRepository $natureReserveRepository, ExportService $exportService, NatureReserve $natureReserve, Request $request): Response
    // {
    //     // Récupérer l'ID de la campagne sélectionnée depuis la requête GET
    //     $selectedCampaignId = $request->query->get('rsf');
    //     $campaignSelected = $selectedCampaignId 
    //         ? $countingCampaignRepository->find($selectedCampaignId) 
    //         : $natureReserveRepository->findMostRecentCampaignInRsf($natureReserve);
    //     // Formulaire d'exportation
    //     $formExport = $this->createForm(ExportType::class);
    //     $formExport->handleRequest($request);

    //     $rsfSitesByCampaign = $natureReserveRepository->getRsfSitesByCampaign($campaignSelected, $natureReserve);

    //     if ($formExport->isSubmitted() && $formExport->isValid()) {
    //         $columnNames = ['Nom de la réserve naturelle', 'Responsable de la réserve', 'Créé le', 'Nom de la campagne', 'Date de début', 'Date de fin', 'État de la campagne', 
    //                         'Site', 'Nombre total des oiseaux comptés', 'Collecteur', 'Numéro de collecte', 'Type de comptage', 'Qualité de comptage','Méthodes de collecte utilisées', 'Disturbé(e)', 'Météo', 'Glace', 'Marée', 'Eaux', 'Espèce', 'Nombre total', ' Total général des comptages dans la campagne', "Total d'espèces uniques observées"];

    //         $data = [];
    //         foreach ($rsfSitesByCampaign as $site) {

    //             $data[] = [
    //                 $natureReserve->getReserveName(),
    //                 $natureReserve->getReserveLeader(),
    //                 $natureReserve->getCreatedAt()->format('d-m-Y H:i:s'),
    //                 $campaignSelected->getCampaignName(),
    //                 $campaignSelected->getStartDate()->format('d-m-Y H:i:s'),
    //                 $campaignSelected->getEndDate()->format('d-m-Y H:i:s'),
    //                 $campaignSelected->getCampaignStatus()->getLabel(),
    //                 $site->getSiteName(),
    //                 $siteCollectionRepository->getTotalBirdCountsForSiteInCampaign($site, $campaignSelected),
    //             ];
    //     }
    //         $format = $formExport->get('format')->getData();
    //         $fileName = sprintf("Nature_reserve_export_%s", date('d-m-Y_His'));
    
    //         return $exportService->export($columnNames, $data, $format, $fileName);
    //     }

    //     return $this->render('nature_reserve/show.html.twig', [
    //         'nature_reserve' => $natureReserve,
    //         'formExport' => $formExport->createView(),
    //     ]);
    // }

    #[Route('/{id}/edit', name: 'app_nature_reserve_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, NatureReserve $natureReserve, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(NatureReserveType::class, $natureReserve);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                try {
                    if (!$this->valideNatureReserve($natureReserve)) {
                        return $this->redirectToRoute('app_nature_reserve_edit', ['id' => $natureReserve->getId()], Response::HTTP_SEE_OTHER);
                    }
                    $natureReserve->setUpdatedAt(new \DateTimeImmutable());
                    $entityManager->flush();
                    $this->addFlash('success', $this->translator->trans('nature_reserve.msg.updated_success'));
        
                    return $this->redirectToRoute('app_nature_reserve_index', [], Response::HTTP_SEE_OTHER);
                } catch (\Exception $e) {
                    $this->addFlash('error', $e->getMessage());
                    return $this->redirectToRoute('app_nature_reserve_edit', ['id' => $natureReserve->getId()], Response::HTTP_SEE_OTHER);
                }
            } else {
                $this->addFlash('error', $this->translator->trans('invalid_form'));
            }
        }

        return $this->render('nature_reserve/edit.html.twig', [
            'nature_reserve' => $natureReserve,
            'form' => $form,
        ]);
    }

    /**
     * Valide la reserve naturelle
     *
     * @param NatureReserve $natureReserve
     * @return bool
     */
    private function valideNatureReserve(NatureReserve $natureReserve): bool
    {
        if ($natureReserve->getReserveName() === null) {
            $this->translator->trans('nature_reserve.reserve_name_required');
            return false;
        }
        if ($natureReserve->getSiteCollections()->isEmpty()) {
            $this->addFlash('error', $this->translator->trans('nature_reserve.select_at_least_one_site'));
            return false;
        }

        if ($natureReserve->getReserveLeader() === null) {
            $this->addFlash('error', $this->translator->trans('nature_reserve.select_at_least_one_site'));
            return false;
        }

        foreach ($natureReserve->getSiteCollections() as $siteCollection) {
            $siteCollection->setNatureReserve($natureReserve);
            $siteCollection->setUpdatedAt(new \DateTimeImmutable());
        }

        return true;
    }

    #[Route('/{id}', name: 'app_nature_reserve_delete', methods: ['POST'])]
    public function delete(Request $request, NatureReserve $natureReserve, EntityManagerInterface $entityManager): Response
    {
        try {
            if ($this->isCsrfTokenValid('delete'.$natureReserve->getId(), $request->getPayload()->get('_token'))) {
                if (!$natureReserve->getSiteCollections()->isEmpty()) {
                    foreach ($natureReserve->getSiteCollections() as $siteCollection) {
                        $siteCollection->setNatureReserve(null);
                        $entityManager->persist($siteCollection);
                    }
                }
                $entityManager->remove($natureReserve);
                $entityManager->flush();
                $this->addFlash('success', $this->translator->trans('nature_reserve.msg.deleted_success'));
            } else {
                $this->addFlash('error',$this->translator->trans('nature_reserve.msg.deleted_error'));
            }
        } catch (\Exception $e) {
            $this->addFlash('error', $e->getMessage());
        }
        return $this->redirectToRoute('app_nature_reserve_index', [], Response::HTTP_SEE_OTHER);
    }
}
