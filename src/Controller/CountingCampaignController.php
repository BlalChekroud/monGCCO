<?php

namespace App\Controller;

use App\Form\ExportType;
use App\Service\ExportService;
use Symfony\Component\HttpFoundation\JsonResponse;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use League\Csv\Writer;

use Symfony\Component\HttpFoundation\StreamedResponse;

use App\Entity\EnvironmentalConditions;
use App\Repository\AgentsGroupRepository;
use App\Repository\BirdSpeciesCountRepository;
use App\Repository\CampaignStatusRepository;
use App\Repository\CollectedDataRepository;
use App\Repository\EnvironmentalConditionsRepository;
use App\Repository\SiteCollectionRepository;
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
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/user/counting/campaign')]
class CountingCampaignController extends AbstractController
{
    private $campaignStatusService;
    private $exportService;
    private $translator;

    public function __construct(CampaignStatusService $campaignStatusService, ExportService $exportService, TranslatorInterface $translator)
    {
        $this->campaignStatusService = $campaignStatusService;
        $this->exportService = $exportService;
        $this->translator = $translator;
    }

    #[Route('/', name: 'app_counting_campaign_index', methods: ['GET','POST'])]
    public function index(Request $request, CampaignStatusRepository $campaignStatusRepository, CountingCampaignRepository $countingCampaignRepository, EntityManagerInterface $entityManager): Response
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

        // Exporter les données de la campagne
        $formExport = $this->createForm(ExportType::class)
                            ->handleRequest($request);

        if ($formExport->isSubmitted() && $formExport->isValid()) {
            if (!$this->isGranted('ROLE_EXPORT')) {
                $this->addFlash('warning', $this->translator->trans('export_permission'));
                return $this->redirectToRoute('app_counting_campaign_index');
            }
        $columnNames = ['Nom de la campagne', 'Date de début', 'Date de fin', 'Etat de la campagne', 'Créé le', 'Dernière mise à jour', 'Créé par'];

        $data = [];
            foreach ($countingCampaigns as $countingCampaign) {
                $data[] = [
                    $countingCampaign->getCampaignName() ?? '',
                    $countingCampaign->getStartDate()?->format('d-m-Y H:i:s') ?? null,
                    $countingCampaign->getEndDate()?->format('d-m-Y H:i:s') ?? null,
                    $countingCampaign->getCampaignStatus() ?? '',
                    $countingCampaign->getCreatedAt()?->format('d-m-Y H:i:s') ?? null,
                    $countingCampaign->getUpdatedAt()?->format('d-m-Y H:i:s') ?? null,
                    $countingCampaign->getCreatedBy()?->getEmail() ?? '',
                ];
            }

        $format = $formExport->get('format')->getData();
        $fileName = sprintf("Counting_campaigns_export_%s", date('d-m-Y_His'));

        return $this->exportService->export($columnNames, $data, $format, $fileName);
        }

        return $this->render('counting_campaign/index.html.twig', [
            'counting_campaigns' => $countingCampaigns,
            'statusIndexMap' => $statusIndexMap,  // Passer le tableau d'index des statuts
            'formExport' => $formExport->createView(),
        ]);
    }


    #[Route('/api/sync', name: 'api_sync_campaigns', methods: ['GET'])]
    public function syncCampaigns(CampaignRepository $campaignRepository): JsonResponse
    {
        $campaigns = $campaignRepository->findAll();
        return $this->json($campaigns);
    }

    // #[Route('/api/sync-campaign', name: 'app_counting_campaign_sync', methods: ['POST'])]
    // public function syncCampaign(CampaignStatusRepository $campaignStatusRepository, CountingCampaignRepository $countingCampaignRepository, Request $request, EntityManagerInterface $entityManager): JsonResponse
    // {
    //     $data = json_decode($request->getContent(), true);
    //     if (!$data) {
    //         return new JsonResponse(['error' => 'Données invalides'], 400);
    //     }

    //     try {
    //         foreach ($data as $campaignData) {
    //             $campaign = $countingCampaignRepository->find($campaignData['id']);
                
    //             if (!$campaign) {
    //                 $campaign = new CountingCampaign();
    //             }

    //             foreach ($campaign->getSiteAgentsGroups() as $siteAgentsGroup) {
    //                 $siteAgentsGroup->setCountingCampaign($campaign);
    //                 $siteAgentsGroup->setCreatedAt(new \DateTimeImmutable());
    //                 $entityManager->persist($siteAgentsGroup);
    //             }

    //             $campaign->setStartDate(new \DateTime($campaignData['startDate']));
    //             $campaign->setEndDate(new \DateTime($campaignData['endDate']));
                
    //             $campaign->setCampaignName($campaignData['name']);
    //             // Mettez à jour les autres champs nécessaires
    //             $campaign->setCreatedAt(new \DateTimeImmutable($campaignData['createdAt']));
    //             $campaign->setCreatedBy($campaignData['createdBy']);
    //             // $campaign->generateCampaignName();
    //             // Mettre à jour le statut de la campagne
    //             $this->updateCampaignStatus($campaign, $campaignStatusRepository);
    //             $entityManager->persist($campaign);
    //             // $entityManager->flush();
                    
    //             // $campaign->generateCampaignName();
    //             // $entityManager->flush();
                    
    //             $this->addFlash('success', "Campagne de comptage a bien été crée");
    //             // $entityManager->persist($campaign);
    //         }

    //         $entityManager->flush();
    //         return new JsonResponse(['status' => 'Campagnes synchronisées avec succès'], 200);
    //     } catch (\Exception $e) {
    //         return new JsonResponse(['error' => 'Erreur lors de la synchronisation'], 500);
    //     }
    // }

    
    
    // #[Route('/api/sync-campaign', name: 'app_counting_campaign_sync', methods: ['POST'])]
    // public function syncCampaign(CampaignStatusRepository $campaignStatusRepository, CountingCampaignRepository $countingCampaignRepository, Request $request, EntityManagerInterface $entityManager): JsonResponse
    // {
    //     $data = json_decode($request->getContent(), true);
    //     if (!$data) {
    //         return new JsonResponse(['error' => 'Données invalides'], 400);
    //     }

    //     try {
    //         foreach ($data as $campaignData) {
    //             $campaign = $countingCampaignRepository->find($campaignData['id']);
                
    //             if (!$campaign) {
    //                 $campaign = new CountingCampaign();
    //             }

    //             foreach ($campaign->getSiteAgentsGroups() as $siteAgentsGroup) {
    //                 $siteAgentsGroup->setCountingCampaign($campaign);
    //                 $siteAgentsGroup->setCreatedAt(new \DateTimeImmutable());
    //                 $entityManager->persist($siteAgentsGroup);
    //             }

    //             $campaign->setStartDate(new \DateTime($campaignData['startDate']));
    //             $campaign->setEndDate(new \DateTime($campaignData['endDate']));
                
    //             $campaign->setCampaignName($campaignData['name']);
    //             // Mettez à jour les autres champs nécessaires
    //             $campaign->setCreatedAt(new \DateTimeImmutable($campaignData['createdAt']));
    //             $campaign->setCreatedBy($campaignData['createdBy']);
    //             // $campaign->generateCampaignName();
    //             // Mettre à jour le statut de la campagne
    //             $this->updateCampaignStatus($campaign, $campaignStatusRepository);
    //             $entityManager->persist($campaign);
    //             // $entityManager->flush();
                    
    //             // $campaign->generateCampaignName();
    //             // $entityManager->flush();
                    
    //             $this->addFlash('success', "Campagne de comptage a bien été crée");
    //             // $entityManager->persist($campaign);
    //         }

    //         $entityManager->flush();
    //         return new JsonResponse(['status' => 'Campagnes synchronisées avec succès'], 200);
    //     } catch (\Exception $e) {
    //         return new JsonResponse(['error' => 'Erreur lors de la synchronisation'], 500);
    //     }
    // }

    
    
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

                // if ($countingCampaign->getNatureReserves()->isEmpty()) {
                //     $this->addFlash('error', 'Vous devez sélectionner au moins une réserve naturelle.');
                //     return $this->redirectToRoute('app_counting_campaign_new');
                // }

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
    #[Route('/{id}', name: 'app_counting_campaign_show', methods: ['GET', 'POST'])]
    public function show(
        Request $request,
        BirdSpeciesCountRepository $birdSpeciesCountRepository,
        EnvironmentalConditionsRepository $environmentalConditionsRepository, 
        AgentsGroupRepository $agentsGroupRepository, 
        CollectedDataRepository $collectedDataRepository, 
        SiteCollectionRepository $siteCollectionRepository, 
        CampaignStatusRepository $campaignStatusRepository, 
        CountingCampaignRepository $countingCampaignRepository, 
        CountingCampaign $countingCampaign, 
        EntityManagerInterface $entityManager): Response
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
    
        // Récupérer tous les SiteCollection associés aux SiteAgentsGroups
        foreach ($siteAgentsGroups as $siteAgentsGroup) {
            $siteCollection = $siteAgentsGroup->getSiteCollection();
    
            if ($siteCollection) {
                // Ajouter le site à la liste des sites
                $sites[] = $siteCollection;

                // Récupérer le total des comptages d'oiseaux pour ce site dans la campagne
                $totalBirdCountsForSiteInCampaign[$siteCollection->getId()] = $siteCollectionRepository->getTotalBirdCountsForSiteInCampaign($siteCollection, $countingCampaign);
            }
        }

        
        // Récupérer toutes les conditions environnementales pour les sites de la campagne en une seule requête
        if (!empty($sites)) {
            $conditions = $entityManager->getRepository(EnvironmentalConditions::class)->findBy([
                'user' => $user,
                'siteCollection' => $sites,
                'countingCampaign' => $countingCampaign,
                'collectedData' => null, // Ce filtre assure que collectedData est null (pas de collecte associée)
            ], ['createdAt' => 'DESC']);
    
            // Stocker les conditions par ID de SiteCollection
            foreach ($conditions as $condition) {
                $existingConditions[$condition->getSiteCollection()->getId()] = $condition;
            }
        }

        /**
         * DES STATISTIQUES
         */
        // Récupérer les données collectées associées à la campagne
        $collectedDataInCampaign = $collectedDataRepository->findByCountingCampaign($countingCampaign);
        // Le nombre de collectes associées à la campagne
        $totalCollectedDataCount = $countingCampaignRepository->countCollectedDataByCampaign($countingCampaign);
        // Compter le nombre total d'agents participants à la campagne
        $totalAgentsCount = $agentsGroupRepository->countAgentsByCountingCampaign($countingCampaign);
        // Toutes les méthodes de collecte de la campagne
        $methodsUsed = $countingCampaignRepository->getMethodsUsedInCampaign($countingCampaign);
        // Récupérer les valeurs les plus choisies des conditions environnementales
        $frequentConditions = $environmentalConditionsRepository->getMostFrequentEnvironmentalConditions($countingCampaign);
        // Total d'oiseaux comptés de la campagne
        $totalBirdsCountedInCampaign = $birdSpeciesCountRepository->countTotalBirdsInCampaign($countingCampaign);
        $totalcountUniqueBirdSpeciesInCampaign = $birdSpeciesCountRepository->countUniqueBirdSpeciesInCampaign($countingCampaign);
        // Les SiteCollections d'une campagne.
        $siteCollectionsByCampaign = $siteCollectionRepository->getSiteCollectionsByCampaign($countingCampaign);
        
        $totalCountBySpecies = $countingCampaignRepository->getTotalCountBySpecies($countingCampaign->getId());

        // Exporter les données de la campagne
        $formExport = $this->createForm(ExportType::class)
                            ->handleRequest($request);

        if ($formExport->isSubmitted() && $formExport->isValid()) {
            if (!$this->isGranted('ROLE_EXPORT')) {
                $this->addFlash('warning', $this->translator->trans('export_permission'));
                return $this->redirectToRoute('app_counting_campaign_show', ['id' => $countingCampaign->getId()]);
            }

        $columnNames = ['Nom de la campagne', 'Date de début', 'Date de fin', 'Etat de la campagne', 'Description', 'Créé le', 'Dernière mise à jour', 'Créé par',
         'Ville', 'Nom du site', 'Date de collecte', 'Collecté par', 'Nom de l\'espèce', 'Code de l\'espèce', 'Nombre d\'oiseaux'];
         
         $data = $countingCampaignRepository->getBirdSpeciesDataForCampaign($countingCampaign->getId());

        $format = $formExport->get('format')->getData();
        $fileName = sprintf("Counting_campaign_export_%s", date('d-m-Y_His'));

        return $this->exportService->export($columnNames, $data, $format, $fileName);
        }

        return $this->render('counting_campaign/show.html.twig', [
            'counting_campaign' => $countingCampaign,
            'existingConditions' => $existingConditions,  // Transmettre les conditions environnementales
            'statusIndexMap' => $statusIndexMap,
            'statusFinished' => $statusFinished,
            'statusClosed' => $statusClosed,
            'statusSuspended' => $statusSuspended,
            'statusCancelled' => $statusCancelled,
            'totalBirdCountsForSiteInCampaign' => $totalBirdCountsForSiteInCampaign, // Transmettre les total des oiseaux comptés 
            'collectedDataInCampaign' => $collectedDataInCampaign,
            'totalCollectedDataCount' => $totalCollectedDataCount,
            'totalAgentsCount' => $totalAgentsCount,
            'methodsUsed' => $methodsUsed,
            'frequentConditions' => $frequentConditions,
            'totalBirdsCountedInCampaign' => $totalBirdsCountedInCampaign,
            'totalcountUniqueBirdSpeciesInCampaign' => $totalcountUniqueBirdSpeciesInCampaign,
            'siteCollectionsByCampaign' => $siteCollectionsByCampaign,
            'formExport' => $formExport->createView(),
            'totalCountBySpecies' => $totalCountBySpecies,
        ]);
    }


    // #[Route('/{id}/export', name: 'app_counting_campaign_export', methods: ['GET'])]
    // public function export(ExportService $exportService, Request $request, CountingCampaign $countingCampaign, CountingCampaignRepository $countingCampaignRepository, TranslatorInterface $translator): Response
    // {
    //     $formExport = $this->createForm(ExportType::class)
    //                 ->handleRequest($request);

    //     if ($formExport->isSubmitted() && $formExport->isValid()) {
    //         $columnNames = ['Nom de la campagne', 'Date de début', 'Date de fin', 'Créé le', 'Créé par'];
    //         // $countingCampaign = $countingCampaignRepository->findAll();
            
    //         $data = [
    //             [
    //                 $countingCampaign->getCampaignName(),
    //                 $countingCampaign->getStartDate()->format('d-m-Y H:i:s'),
    //                 $countingCampaign->getEndDate()->format('d-m-Y H:i:s'),
    //                 $countingCampaign->getCreatedAt()->format('d-m-Y H:i:s'),
    //                 $countingCampaign->getCreatedBy()->getEmail(),
    //             ]
    //         ];

    //         $format = $formExport->get('format')->getData();
    //         $fileName = sprintf("Counting_campaign_export_%s", date('d-m-Y_His'));
    
    //         return $exportService->export($columnNames, $data, $format, $fileName);
    //     }

    //     return $this->render('counting_campaign/show.html.twig', [
    //         'formExport' => $formExport->createView(),
    //     ]);
    // }


    // #[Route('/{id}/export', name: 'app_counting_campaign_export', methods: ['GET'])]
    // public function exportCsv(
    //     CountingCampaign $campaign,
    //    CountingCampaignRepository $countingCampaignRepository,
    //     TranslatorInterface $translator
    // ): Response {
    //     // Vérification des autorisations
    //     if (!$this->isGranted('ROLE_EXPORT')) {
    //         $this->addFlash('warning', $translator->trans('export_permission'));
    //         return $this->redirectToRoute('app_counting_campaign_show', ['id' => $campaign->getId()]);
    //     }

    //     // Récupérer les données à exporter
    //     $data = $countingCampaignRepository->getExportDataByCampaign($campaign);
        
    //     // Limite le nombre de données à exporter (par exemple, pas plus de 1000 sites)
    //     // $maxExportSize = 1000;
    //     // if (count($data) > $maxExportSize) {
    //     //     $this->addFlash('warning', $translator->trans('msg_export_limit'));
    //     //     return $this->redirectToRoute('app_counting_campaign_show', ['id' => $campaign->getId()]);
    //     // }
    
    //     // Créer la réponse avec un flux
    //     $response = new StreamedResponse(function () use ($data) {
    //         // Ouvrir le flux pour écrire dans le fichier CSV
    //         $handle = fopen('php://output', 'w');
    
    //         if (!empty($data)) {
    //             // Ajouter les en-têtes
    //             fputcsv($handle, array_keys($data[0]), ';');
    
    //             // Ajouter les lignes de données
    //             foreach ($data as $row) {
    //                 // Appliquer le format aux dates et encoder les caractères
    //                 $row['CreatedAt'] = $row['CreatedAt']->format('d-m-Y H:i:s');
    //                 $row['startDate'] = $row['startDate']->format('d-m-Y H:i:s');
    //                 $row['endDate'] = $row['endDate']->format('d-m-Y H:i:s');
    //                 $row['Site'] = mb_convert_encoding($row['Site'], 'UTF-8', 'auto');
    //                 $row['City'] = mb_convert_encoding($row['City'], 'UTF-8', 'auto');
    //                 $row['Region'] = mb_convert_encoding($row['Region'], 'UTF-8', 'auto');
    //                 $row['AgentName'] = mb_convert_encoding($row['AgentName'], 'UTF-8', 'auto');
    //                 $row['AgentLastName'] = mb_convert_encoding($row['AgentLastName'], 'UTF-8', 'auto');
    //                 $row['Species'] = mb_convert_encoding($row['Species'], 'UTF-8', 'auto');
    //                 $row['Method'] = mb_convert_encoding($row['Method'], 'UTF-8', 'auto');
    
    //                 fputcsv($handle, $row, ';');
    //             }
    //         } else {
    //             // Ajouter une ligne vide si aucune donnée
    //             fputcsv($handle, ['No data available'], ';');
    //         }
    
    //         fclose($handle); // Fermer le fichier après l'export
    //     });
    
    //     // Définir les headers pour forcer le téléchargement du fichier CSV
    //     $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
    //     $response->headers->set('Content-Disposition', 'attachment; filename="campaign_export.csv"');
    
    //     return $response;
    // }


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


    // #[Route('/api/sync', name: 'api_sync_campaigns_post', methods: ['POST'])]
    // public function validateAndSync(Request $request, EntityManagerInterface $em): JsonResponse
    // {
    //     $data = json_decode($request->getContent(), true);
    //     $errors = [];
    
    //     foreach ($data as $item) {
    //         $existingCampaign = $em->getRepository(CountingCampaign::class)->find($item['id']);
    //         if ($existingCampaign) {
    //             if ($existingCampaign->getUpdatedAt() > new \DateTime($item['updatedAt'])) {
    //                 $errors[] = [
    //                     'id' => $item['id'],
    //                     'message' => 'Conflit de données, la campagne a été mise à jour plus récemment côté serveur.',
    //                 ];
    //                 continue;
    //             }
    //         }
    
    //         $campaign = $existingCampaign ?? new CountingCampaign();
    //         $campaign->setCampaignName($item['name']);
    //         $campaign->setCreatedAt(new \DateTimeImmutable());
    //         $campaign->setDescription($item['description']);
    //         $em->persist($campaign);
    //     }
    
    //     $em->flush();
    
    //     return $this->json(['message' => 'Synchronisation terminée', 'errors' => $errors]);
    // }
   

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
                    $this->addFlash('warning', "Une collecte de données {$collectedData->getId()} pour le site {$site->getSiteName()} n'a pas de conditions environnementales.");
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
                $newStatus = $campaignStatuses[0]; // "Planifiée"
            } elseif ($startDate <= $now && $endDate >= $now) {
                $newStatus = $campaignStatuses[1]; // "En cours"
            } elseif ($endDate < $now) {
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
