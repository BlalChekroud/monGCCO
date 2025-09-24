<?php

namespace App\Controller;

use App\Entity\BirdSpeciesCount;
use App\Entity\CountingCampaign;
use App\Entity\EnvironmentalConditions;
use App\Entity\SiteCollection;
use App\Entity\User;
use App\Form\ExportType;
use App\Repository\BirdSpeciesRepository;
use App\Repository\CountingCampaignRepository;
use App\Repository\CountTypeRepository;
use App\Repository\EnvironmentalConditionsRepository;
use App\Repository\MethodRepository;
use App\Repository\QualityRepository;
use App\Repository\SiteCollectionRepository;
use App\Service\CampaignStatusService;
use App\Service\ExportService;
use Symfony\Component\HttpFoundation\JsonResponse;
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
    private $exportService;

    public function __construct(CampaignStatusService $campaignStatusService, ExportService $exportService, private readonly TranslatorInterface $translator)
    {
        $this->campaignStatusService = $campaignStatusService;
        $this->exportService = $exportService;
    }
    

    #[Route('/', name: 'app_collected_data_index', methods: ['GET', 'POST'])]
    public function index(ExportService $exportService, Request $request, CollectedDataRepository $collectedDataRepository): Response
    {
        $user = $this->getUser();

        // Vérifiez si l'utilisateur a l'un des rôles
        if (!$this->isGranted('ROLE_VIEW') && !$this->isGranted('ROLE_COLLECTOR')) {
            $this->addFlash('error', $this->translator->trans('access'));
            return $this->redirectToRoute('home');
        }

        if ($this->isGranted('ROLE_ADMIN')) {
            $collectedDatas = $collectedDataRepository->findAll();
        } else {
            // $collectedDatas = $collectedDataRepository->findBy(['createdBy' => $user]);
            $collectedDatas = $collectedDataRepository->getCollectesByUser($user);  // Récupérer les collectes où l'utilisateur est leader
        }

        
        // Exporter les données de la campagne
        $formExport = $this->createForm(ExportType::class)
                            ->handleRequest($request);

        if ($formExport->isSubmitted() && $formExport->isValid()) {
            if (!$this->isGranted('ROLE_EXPORT')) {
                $this->addFlash('warning', $this->translator->trans('export_permission'));
                return $this->redirectToRoute('app_collected_data_index');
            }
        $columnNames = ['Collecteur', 'Nom du site', 'Nombre total des oiseaux comptés', 'Numéro de la condition environnementale', 'Créé le'];

        $data = [];
            foreach ($collectedDatas as $collect) {
                $data[] = [
                    $collect->getCreatedBy() ?? '',
                    $collect->getSiteCollection()?->getSiteName() ?? '',
                    $collect->getTotalCount() ?? 0,
                    $collect->getEnvironmentalConditions()?->getId() ?? '',
                    $collect->getCreatedAt()?->format('d-m-Y H:i:s') ?? null,
                ];
            }

        $format = $formExport->get('format')->getData();
        $fileName = sprintf("Export_collections_%s", date('d-m-Y_His'));

        return $this->exportService->export($columnNames, $data, $format, $fileName);
        }

        return $this->render('collected_data/index.html.twig', [
            'collected_datas' => $collectedDatas,
            'formExport' => $formExport->createView(),
        ]);
    }

    #[IsGranted('ROLE_COLLECTOR', message: 'Vous n\'avez pas l\'accès.')]
    #[Route('/new', name: 'app_collected_data_new', methods: ['GET', 'POST'])]
    public function new(Request $request, BirdSpeciesRepository $birdSpeciesRepository, EntityManagerInterface $entityManager, EnvironmentalConditionsRepository $environmentalConditionsRepository): Response
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
            $this->addFlash('error', $this->translator->trans('condition.campaign_not_found'));
            return $this->redirectToRoute('app_collected_data_index');
        }
        
        if (!$site) {
            $this->addFlash('error', $this->translator->trans('collect.site_not_found'));
            return $this->redirectToRoute('app_collected_data_index');
        }
        
        // Si l'utilisateur n'est pas membre d'un groupe, interdire l'accès
        if (!$this->isUserSiteMember($user, $site) && !$this->isGranted('ROLE_SUPER_ADMIN')) {
            $this->addFlash('warning', $this->translator->trans('member_of_a_group_assigned_to_this_site'));
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
        
        if (!$environmentalConditions || $environmentalConditions->getCollectedData()) {
            // Rediriger vers la page de création des conditions environnementales si elles n'existent pas
            $this->addFlash('warning', $this->translator->trans('collect.please_create_the_environmental_conditions_for_this_site_first'));
            return $this->redirectToRoute('app_environmental_conditions_new', [
                'campaignId' => $campaignId,
                'siteId' => $siteId
            ]);
        }
        
        if ($environmentalConditions->getCollectedData() !== null) {
            // Rediriger vers la page de création des données collectées si elles existent déjà
            $this->addFlash('warning', $this->translator->trans('collect.data_already_collected'));
            return $this->redirectToRoute('app_collected_data_new', [
                'campaignId' => $campaignId,
                'siteId' => $siteId
            ]);
        }
        // Vérifier si la campagne est modifiable
        if ($campaign && !$this->campaignStatusService->ensureCampaignIsEditable($campaign)) {
            // La vérification échoue, un message flash est déjà ajouté par le service
            $this->addFlash('error', $this->translator->trans('campaign.cannot_be_modified', ['%status%' => $campaign->getCampaignStatus()]));
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
                    try {
                        // Enregistrez les données collectées
                        $collectedDatum->setCreatedAt(new \DateTimeImmutable());
                        $entityManager->persist($collectedDatum);
            
                        foreach ($collectedDatum->getBirdSpeciesCounts() as $birdSpeciesCount) {
                            $birdSpeciesCount->setCollectedData($collectedDatum);
                            $entityManager->persist($birdSpeciesCount);
                        }
                        
                        // Sauvegarde des données
                        $entityManager->flush();
                        $this->addFlash('success', $this->translator->trans('collect.msg.created_success'));
                        return $this->redirectToRoute('app_collected_data_index', [], Response::HTTP_SEE_OTHER);
                    } catch (\Exception $e) {
                        $this->addFlash('error', $e->getMessage());
                        return $this->redirectToRoute('app_collected_data_new', [], Response::HTTP_SEE_OTHER);
                    }
                } else {
                    $this->addFlash('error', $this->translator->trans('collect.msg.data_collection_not_valid'));
                }
            } else {
                $this->addFlash('error',$this->translator->trans('invalid_form'));
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

    #[Route('/api/collects', name: 'api_collects', methods: ['POST'])]
    public function storeCollect(
        Request $request,
        EntityManagerInterface $em,
        CountingCampaignRepository $campaignRepo,
        SiteCollectionRepository $siteRepo,
        BirdSpeciesRepository $speciesRepo,
        CountTypeRepository $countTypeRepo,
        QualityRepository $qualityRepo,
        MethodRepository $methodRepo
    ): JsonResponse {
        try {
            $data = json_decode($request->getContent(), true);
        
            if (!$data) {
                return $this->json(['error' => 'Invalid payload'], 400);
            }
        
            // Récupération des entités liées
            $campaign = $campaignRepo->find($data['campaignId']);
            $site = $siteRepo->find($data['siteId']);
            $countType = $countTypeRepo->find($data['countType']);
            $quality = $qualityRepo->find($data['quality']);
            $method = $methodRepo->find($data['method']);
        
            if (!$campaign || !$site) {
                return $this->json(['error' => 'Invalid campaign or site'], 400);
            }
            
            if (!$countType || !$quality || !$method) {
                return $this->json(['error' => 'Missing required data'], 400);
            }
        
            // Création collecte
            $collect = new CollectedData();
            // // ✅ Conversion createdAt (format d-m-Y H:i:s → DateTimeImmutable)
            // if (!empty($data['createdAt'])) {
            //     $createdAt = \DateTimeImmutable::createFromFormat('d-m-Y H:i:s', $data['createdAt']);
            //     if ($createdAt === false) {
            //         return $this->json(['error' => 'Invalid createdAt format'], 400);
            //     }
            //     $collect->setCreatedAt($createdAt);
            // } else {
            //     $collect->setCreatedAt(new \DateTimeImmutable());
            // }
            $collect->setCountingCampaign($campaign);
            $collect->setSiteCollection($site);
            $collect->setCountType($countType);
            $collect->setQuality($quality);
            if ($method) {
                $collect->addMethod($method);
            }
            // $collect->setCreatedAt(new \DateTimeImmutable($data['createdAt'] ?? 'now'));
            $collect->setCreatedAt(new \DateTimeImmutable());
            $collect->setCreatedBy($this->getUser());
        
            // Espèces observées
            // foreach ($data['birdSpeciesCounts'] as $spData) {
            //     $species = $speciesRepo->find($spData['birdSpeciesId']);
            //     if ($species) {
            //         $birdCount = new BirdSpeciesCount();
            //         $birdCount->setBirdSpecies($species);
            //         $birdCount->setCount($spData['count']);
            //         $birdCount->setCollectedData($collect);
            //         $em->persist($birdCount);
            //     }
            // }
            // Handle bird species counts
            if (!empty($data['birdSpeciesCounts'])) {
                foreach ($data['birdSpeciesCounts'] as $spData) {
                    if (isset($spData['birdSpeciesId'], $spData['count'])) {
                        $species = $speciesRepo->find($spData['birdSpeciesId']);
                        if ($species) {
                            $birdCount = new BirdSpeciesCount();
                            $birdCount->setBirdSpecies($species);
                            $birdCount->setCount((int)$spData['count']);
                            $birdCount->setCollectedData($collect);
                            $em->persist($birdCount);
                        }
                    }
                }
            }
        
            $em->persist($collect);
            $em->flush();
        
            return $this->json(['success' => true, 'id' => $collect->getId()]);
            
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Server error: ' . $e->getMessage()
            ], 500);
        }
    }
    
    // --- Nouvelle méthode "sync" pour offline → online ---
    // #[IsGranted('ROLE_COLLECTOR', message: 'Vous n\'avez pas l\'accès.')]
    #[Route('/sync', name: 'collected_data_sync', methods: ['POST'])]
    public function sync(
        Request $request,
        EntityManagerInterface $entityManager,
        BirdSpeciesRepository $birdSpeciesRepository,
        EnvironmentalConditionsRepository $environmentalConditionsRepository
    ): JsonResponse {
        $user = $this->getUser();
        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return new JsonResponse(['error' => 'Invalid data'], 400);
        }

        try {
            $campaign = $entityManager->getRepository(CountingCampaign::class)->find($data['campaignId'] ?? null);
            $site     = $entityManager->getRepository(SiteCollection::class)->find($data['siteId'] ?? null);

            if (!$campaign || !$site) {
                return new JsonResponse(['error' => 'Campagne ou site introuvable'], 400);
            }

            // Autorisations
            if (!$this->isUserSiteMember($user, $site) && !$this->isGranted('ROLE_SUPER_ADMIN')) {
                return new JsonResponse(['error' => 'Accès refusé'], 403);
            }

            // Conditions environnementales
            $environmentalConditions = $environmentalConditionsRepository->find($data['environmentalConditionsId'] ?? null);
            if (!$environmentalConditions) {
                return new JsonResponse(['error' => 'Conditions environnementales manquantes'], 400);
            }

            // Vérifier campagne modifiable
            if (!$this->campaignStatusService->ensureCampaignIsEditable($campaign)) {
                return new JsonResponse(['error' => 'Campagne non modifiable'], 400);
            }

            // Création CollectedData
            $collectedDatum = new CollectedData();
            $collectedDatum->setCountingCampaign($campaign);
            $collectedDatum->setSiteCollection($site);
            $collectedDatum->setEnvironmentalConditions($environmentalConditions);
            $collectedDatum->setCreatedBy($user);
            $collectedDatum->setCreatedAt(new \DateTimeImmutable($data['createdAt'] ?? 'now'));
            $collectedDatum->setQuality($data['quality'] ?? null);
            $collectedDatum->setCountType($data['countType'] ?? null);
            $collectedDatum->addMethod($data['methods'] ?? []);

            // Espèces
            foreach ($data['birdSpeciesCounts'] ?? [] as $item) {
                $species = $birdSpeciesRepository->find($item['birdSpeciesId'] ?? null);
                if ($species && isset($item['count'])) {
                    $speciesCount = new BirdSpeciesCount();
                    $speciesCount->setBirdSpecies($species);
                    $speciesCount->setCount((int) $item['count']);
                    $speciesCount->setCollectedData($collectedDatum);
                    $entityManager->persist($speciesCount);
                }
            }

            $entityManager->persist($collectedDatum);
            $entityManager->flush();

            return new JsonResponse(['status' => 'ok']);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 500);
        }
    }
    // #[IsGranted('ROLE_COLLECTOR', message: 'Vous n\'avez pas l\'accès.')]
    // #[Route('/sync', name: 'app_collected_data_sync', methods: ['POST'])]
    // public function sync(
    //     Request $request,
    //     BirdSpeciesRepository $birdSpeciesRepository,
    //     EntityManagerInterface $entityManager,
    //     EnvironmentalConditionsRepository $environmentalConditionsRepository
    // ): JsonResponse {
    //     $user = $this->getUser();
    //     $payload = json_decode($request->getContent(), true);
    
    //     // Dans sync(), avant traitement
    //     $token = $request->headers->get('X-CSRF-TOKEN');
    //     if (!$this->isCsrfTokenValid('sync_collected_data', $token)) {
    //         return new JsonResponse(['error' => 'Invalid CSRF token'], 419);
    //     }

    //     if (!$payload || !isset($payload['campaignId'], $payload['siteId'], $payload['birdSpeciesCounts'])) {
    //         return $this->json(['error' => 'Invalid JSON payload'], Response::HTTP_BAD_REQUEST);
    //     }
    
    //     $campaign = $entityManager->getRepository(CountingCampaign::class)->find($payload['campaignId']);
    //     $site = $entityManager->getRepository(SiteCollection::class)->find($payload['siteId']);
    
    //     if (!$campaign || !$site) {
    //         return $this->json(['error' => 'Campaign or Site not found'], Response::HTTP_NOT_FOUND);
    //     }
    
    //     if (!$this->isUserSiteMember($user, $site) && !$this->isGranted('ROLE_SUPER_ADMIN')) {
    //         return $this->json(['error' => 'Access denied'], Response::HTTP_FORBIDDEN);
    //     }
    
    //     $environmentalConditions = $environmentalConditionsRepository->findOneBy(
    //         ['user' => $user, 'siteCollection' => $site, 'countingCampaign' => $campaign],
    //         ['createdAt' => 'DESC']
    //     );
    
    //     if (!$environmentalConditions) {
    //         return $this->json(['error' => 'Please create environmental conditions first'], Response::HTTP_CONFLICT);
    //     }
    
    //     // Créer la collecte
    //     $collectedData = new CollectedData();
    //     $collectedData->setCountingCampaign($campaign);
    //     $collectedData->setSiteCollection($site);
    //     $collectedData->setEnvironmentalConditions($environmentalConditions);
    //     $collectedData->setCreatedBy($user);
    //     $collectedData->setCreatedAt(new \DateTimeImmutable());
    
    //     $entityManager->persist($collectedData);
    
    //     // Ajouter les espèces d’oiseaux collectées
    //     foreach ($payload['birdSpeciesCounts'] as $birdData) {
    //         $species = $birdSpeciesRepository->find($birdData['speciesId']);
    //         if ($species) {
    //             $birdSpeciesCount = new BirdSpeciesCount();
    //             $birdSpeciesCount->setCollectedData($collectedData);
    //             $birdSpeciesCount->setBirdSpecies($species);
    //             $birdSpeciesCount->setCount($birdData['count'] ?? 0);
    //             $entityManager->persist($birdSpeciesCount);
    //         }
    //     }
    
    //     $entityManager->flush();
    
    //     return $this->json([
    //         'status' => 'success',
    //         'message' => 'Collected data synchronized successfully',
    //         'collectedDataId' => $collectedData->getId(),
    //     ], Response::HTTP_CREATED);
    // }

    
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
            $this->addFlash('error', $this->translator->trans('collect.msg.total_bird_count_positive'));
            return false;
        }

        if ($collectedDatum->getSiteCollection() !== $environmentalConditions->getSiteCollection()) {
            $this->addFlash('error', $this->translator->trans('collect.msg.site_environment_mismatch'));
            return false;
        }

        if ($collectedDatum->getBirdSpeciesCounts()->isEmpty()) {
            $this->addFlash('error', $this->translator->trans('collect.msg.no_bird_species_selected'));
            return false;
        }

        if ($collectedDatum->getMethod()->isEmpty()) {
            $this->addFlash('error', $this->translator->trans('collect.msg.no_collection_method_selected'));
            return false;
        }

        if ($collectedDatum->getQuality() === null) {
            $this->addFlash('error', $this->translator->trans('collect.msg.collection_quality_required'));
            return false;
        }

        if ($collectedDatum->getCountType() === null) {
            $this->addFlash('error', $this->translator->trans('collect.msg.count_type_required'));
            return false;
        }

        return true;
    }


    #[Route('/{id}', name: 'app_collected_data_show', methods: ['GET', 'POST'])]
    public function show(ExportService $exportService,Request $request , CollectedData $collectedDatum, CollectedDataRepository $collectedDataRepository): Response
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

        // Formulaire d'exportation
        $formExport = $this->createForm(ExportType::class);
        $formExport->handleRequest($request);

        if ($formExport->isSubmitted() && $formExport->isValid()) {
            try {
                $columnNames = ['Campagne de comptage','Nom du groupe','Chef du groupe',
                                'Collecteur','Nom du site','Ville','Disturbé','Météo','Eaux',
                                'Glace', 'Marée', 'Espèce', 'Nombre compté', 'Type de comptage effectué lors de cette visite', 
                                'Qualité','Méthode(s) utilisées pour le comptage', 'créé le'];
    
                // Concaténer les méthodes dans une seule chaîne
                $methodsArray = [];
                foreach ($collectedDatum->getMethod() as $method) {
                    $methodsArray[] = $method->getLabel() ?? '';
                }
                $methodsString = implode(', ', $methodsArray);

                $collector = implode(' ', [
                    $collectedDatum->getCreatedBy()->getName(), 
                    $collectedDatum->getCreatedBy()->getLastName()
                ]);

                $leader = implode(' ', [$teamLeader['name'], $teamLeader['lastName']]);
                // Préparer les données d'export
                $data = [];
                foreach ($collectedDatum->getBirdSpeciesCounts() as $specy) {
                    $data[] = [
                        $collectedDatum->getCountingCampaign()->getCampaignName() ?? '',
                        $teamLeader['groupName'] ?? '',
                        $leader ?? '',
                        $collector ?? '',
                        $collectedDatum->getSiteCollection()->getSiteName() ?? '',
                        $collectedDatum->getSiteCollection()->getCity()->getName() ?? '',
                        $collectedDatum->getEnvironmentalConditions()?->getDisturbed()?->getLabel() ?? '',
                        $collectedDatum->getEnvironmentalConditions()?->getWeather()?->getLabel() ?? '',
                        $collectedDatum->getEnvironmentalConditions()?->getWater()?->getLabel() ?? '',
                        $collectedDatum->getEnvironmentalConditions()?->getIce()?->getLabel() ?? '',
                        $collectedDatum->getEnvironmentalConditions()?->getTidal()?->getLabel() ?? '',
                        $specy->getBirdSpecies()?->getScientificName() ?? '',
                        $specy->getCount() ?? 0,
                        $collectedDatum->getCountType()?->getLabel() ?? null,
                        $collectedDatum->getQuality()?->getLabel() ?? null,
                        $methodsString,  // Toutes les méthodes dans une seule colonne
                        ($collectedDatum->getCreatedAt() ? $collectedDatum->getCreatedAt()->format('d-m-Y H:i:s') : null)
                    ];
                }
    
                $format = $formExport->get('format')->getData();
                $fileName = sprintf("Export_Collected_data_%s", date('d-m-Y_His'));
        
                return $exportService->export($columnNames, $data, $format, $fileName);

            } catch (\Exception $e) {
                $this->addFlash('error', $e->getMessage());
            }
        }

        // Vérifiez si l'utilisateur est admin, créateur ou membre du groupe
        // if ($this->isGranted('ROLE_ADMIN') || $this->isGranted('ROLE_VIEW') || $collectedDatum->getCreatedBy() === $user || $leaderEmail === $user->getEmail()) {
        if ($this->isGranted('ROLE_ADMIN') || $this->isGranted('ROLE_VIEW') || $collectedDatum->getCreatedBy() === $user) {
            return $this->render('collected_data/show.html.twig', [
                'collected_datum' => $collectedDatum,
                'teamLeader' => $teamLeader,
                'formExport' => $formExport->createView(),
            ]);
            
        } else {
            $this->addFlash('info', $this->translator->trans('collect.msg.access_denied'));
            return $this->redirectToRoute('app_collected_data_index');
        }
    }

    #[IsGranted('ROLE_COLLECTOR', message: 'Vous n\'avez pas l\'accès.')]
    #[Route('/{id}/edit', name: 'app_collected_data_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, CollectedData $collectedDatum, EntityManagerInterface $entityManager, BirdSpeciesRepository $birdSpeciesRepository): Response
    {
        $campaign = $collectedDatum->getCountingCampaign();
        $site = $collectedDatum->getSiteCollection();
        $environmentalConditions = $collectedDatum->getEnvironmentalConditions();
        
        if ($this->getUser() !== $collectedDatum->getCreatedBy() && !$this->isGranted('ROLE_SUPER_ADMIN')) {
            $this->addFlash('error', $this->translator->trans('edit_permission'));
            return $this->redirectToRoute('app_collected_data_index', [], Response::HTTP_SEE_OTHER);
        }
        // Si l'utilisateur n'est pas membre d'un groupe, interdire l'accès
        if (!$this->isUserSiteMember($this->getUser(), $site) && !$this->isGranted('ROLE_SUPER_ADMIN')) {
            $this->addFlash('warning', $this->translator->trans('member_of_a_group_assigned_to_this_site'));
            return $this->redirectToRoute('app_collected_data_index');
        }
        // Vérifier si la campagne est modifiable
        if ($campaign && !$this->campaignStatusService->ensureCampaignIsEditable($campaign)) {
            // La vérification échoue, un message flash est déjà ajouté par le service
            $this->addFlash('error', $this->translator->trans('campaign.cannot_be_modified', ['%status%' => $campaign->getCampaignStatus()]));
            return $this->redirectToRoute('app_collected_data_index');
        }
        
        if (!$environmentalConditions) {
            // Rediriger vers la page de création des conditions environnementales si elles n'existent pas
            $this->addFlash('warning', $this->translator->trans('collect.please_create_the_environmental_conditions_for_this_site_first'));
            return $this->redirectToRoute('app_environmental_conditions_new', [
                'campaignId' => $campaign->getId(),
                'siteId' => $site->getId(),
                'collectId' => $collectedDatum->getId()
            ]);
        }

        $form = $this->createForm(CollectedDataType::class, $collectedDatum);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                if ($this->validateCollectedData($collectedDatum, $environmentalConditions)) {
                    try {
                        $collectedDatum->setUpdatedAt(new \DateTimeImmutable);
                        $entityManager->flush();
                        $this->addFlash('success', $this->translator->trans('collect.msg.updated_success'));
                        return $this->redirectToRoute('app_collected_data_show', ['id' => $collectedDatum->getId()], Response::HTTP_SEE_OTHER);
                    } catch (\Exception $e) {
                        $this->addFlash('error', $e->getMessage());
                        return $this->redirectToRoute('app_collected_data_new', [], Response::HTTP_SEE_OTHER);
                    }
                } else {
                    $this->addFlash('error', $this->translator->trans('collect.msg.data_collection_not_valid'));
                }
    
                return $this->redirectToRoute('app_collected_data_index', [], Response::HTTP_SEE_OTHER);
            } else {
                $this->addFlash('error',$this->translator->trans('invalid_form'));
            }
        }

        return $this->render('collected_data/edit.html.twig', [
            'collected_datum' => $collectedDatum,
            'form' => $form,
            $birdSpecies = $birdSpeciesRepository->findAll(), // Récupérer toutes les espèces d'oiseaux
        ]);
    }

    #[Route('/{id}/delete', name: 'app_collected_data_delete', methods: ['POST'])]
    public function delete(Request $request, CollectedData $collectedDatum, EntityManagerInterface $entityManager): Response
    {
        try {
            if ($this->getUser() !== $collectedDatum->getCreatedBy() && !$this->isGranted('ROLE_DELETE')){
                $this->addFlash('error', $this->translator->trans('delete_permission'));
                return $this->redirectToRoute('app_collected_data_index', [], Response::HTTP_SEE_OTHER);
            }
            if ($this->isCsrfTokenValid('delete'.$collectedDatum->getId(), $request->getPayload()->get('_token'))) {
                $entityManager->remove($collectedDatum);
                $entityManager->flush();
                $this->addFlash('success', $this->translator->trans('collect.msg.deleted_success'));
            } else {
                $this->addFlash('error',$this->translator->trans('collect.msg.deleted_error'));
            }
        } catch (\Exception $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('app_collected_data_index', [], Response::HTTP_SEE_OTHER);
    }
}
