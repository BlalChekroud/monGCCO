<?php

namespace App\Controller;

use App\Entity\BirdFamily;
use App\Entity\BirdSpeciesCount;
use App\Entity\CollectedData;
use App\Entity\CountingCampaign;
use App\Entity\SiteCollection;
use App\Repository\BirdFamilyRepository;
use App\Repository\BirdSpeciesRepository;
use App\Repository\CountingCampaignRepository;
use App\Repository\CountTypeRepository;
use App\Repository\DisturbedRepository;
use App\Repository\EnvironmentalConditionsRepository;
use App\Repository\IceRepository;
use App\Repository\MethodRepository;
use App\Repository\QualityRepository;
use App\Repository\TidalRepository;
use App\Repository\WaterRepository;
use App\Repository\WeatherRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class MetadataController extends AbstractController
{    
    // use ApiAuthenticationTrait;
    // --- Nouvelle méthode "create" pour offline → online ---
    #[Route('/api/collected_data', name: 'api_collected_data', methods: ['POST'])]
    public function create(
        Request $request,
        EntityManagerInterface $entityManager,
        BirdSpeciesRepository $birdSpeciesRepository,
        EnvironmentalConditionsRepository $environmentalConditionsRepository
    ): JsonResponse {
        // Vérification du token CSRF
        if (!$this->isCsrfTokenValid('collected_data', $request->headers->get('X-CSRF-TOKEN'))) {
            return new JsonResponse(['error' => 'Token CSRF invalide'], 403);
        }

        // Vérification de l'authentification
        if (!$this->isGranted('ROLE_USER')) {
            return new JsonResponse(['error' => 'Non autorisé'], 401);
        }
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
            // if (!$this->isUserSiteMember($user, $site) && !$this->isGranted('ROLE_SUPER_ADMIN')) {
            //     return new JsonResponse(['error' => 'Accès refusé'], 403);
            // }

            // // Conditions environnementales
            // $environmentalConditions = $environmentalConditionsRepository->find($data['environmentalConditionsId'] ?? null);
            // if (!$environmentalConditions) {
            //     return new JsonResponse(['error' => 'Conditions environnementales manquantes'], 400);
            // }

            // // Vérifier campagne modifiable
            // if (!$this->campaignStatusService->ensureCampaignIsEditable($campaign)) {
            //     return new JsonResponse(['error' => 'Campagne non modifiable'], 400);
            // }

            // Création CollectedData
            $collectedDatum = new CollectedData();
            $collectedDatum->setCountingCampaign($campaign);
            $collectedDatum->setSiteCollection($site);
            // $collectedDatum->setEnvironmentalConditions($environmentalConditions);
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

    #[Route('/api/metadata/campaigns', name: 'api_metadata_campaigns', methods: ['GET'])]
    public function campaigns(Request $request, CountingCampaignRepository $countingCampaignRepository): JsonResponse
    {
        // Vérification de l'authentification
        if (!$this->isGranted('ROLE_USER')) {
            return new JsonResponse(['error' => 'Non autorisé'], 401);
        }

        // Vérification du token CSRF
        if (!$this->isCsrfTokenValid('api_token', $request->headers->get('X-CSRF-TOKEN'))) {
            return new JsonResponse(['error' => 'Token CSRF invalide'], 403);
        }

        $user = $this->getUser();
        $campaigns = $countingCampaignRepository->findCampaignByUser($user, ['En cours', 'Terminée']);
    
        $data = array_map(fn($c) => [
            'id' => $c->getId(),
            'campaignName' => $c->getCampaignName(),
            'sites' => array_map(fn($sag) => [
                'id' => $sag->getSiteCollection()->getId(),
                'siteName' => $sag->getSiteCollection()->getSiteName(),
            ], $c->getSiteAgentsGroups()->toArray())
        ], $campaigns);
    
        return $this->json($data);
    }    

    #[Route('/api/metadata/species', name: 'api_metadata_species', methods: ['GET'])]
    public function species(Request $request, BirdSpeciesRepository $repo): JsonResponse
    {
        // Vérification de l'authentification
        if (!$this->isGranted('ROLE_USER')) {
            return new JsonResponse(['error' => 'Non autorisé'], 401);
        }

        // Vérification du token CSRF
        if (!$this->isCsrfTokenValid('api_token', $request->headers->get('X-CSRF-TOKEN'))) {
            return new JsonResponse(['error' => 'Token CSRF invalide'], 403);
        }

        $species = $repo->findAll();

        $data = array_map(fn($sp) => [
            'id' => $sp->getId(),
            'scientificName' => $sp->getScientificName(),
            'frenchName' => $sp->getFrenchName(),
        ], $species);

        return $this->json($data);
    }

    /* 
    *   FAMILY
    */

    // #[Route('/api/familiesOLD', name: 'api_metadata_familiesOLD', methods: ['GET'])]
    // public function index(Request $request, BirdFamilyRepository $repo): JsonResponse
    // {
    //     // // Vérification de l'authentification
    //     // if (!$this->isGranted('ROLE_USER')) {
    //     //     return new JsonResponse(['error' => 'Non autorisé'], 401);
    //     // }

    //     // // Vérification du token CSRF
    //     // if (!$this->isCsrfTokenValid('api_token', $request->headers->get('X-CSRF-TOKEN'))) {
    //     //     return new JsonResponse(['error' => 'Token CSRF invalide'], 403);
    //     // }


    //     // Vérification de l'authentification pour les appels API mobiles
    //     // $authHeader = $request->headers->get('Authorization');
    //     // if ($authHeader && str_starts_with($authHeader, 'Bearer ')) {
    //     //     $token = substr($authHeader, 7);
    //     //     // Validation du token JWT ici
    //     //     // TODO: Injecter ApiAuthenticator et valider le token
    //     // }
    //     // // Sinon, vérification web classique
    //     // else {
    //     //     // Vérification de l'authentification
    //     //     if (!$this->isGranted('ROLE_USER')) {
    //     //         return new JsonResponse(['error' => 'Non autorisé'], 401);
    //     //     }

    //     //     // Vérification du token CSRF pour les appels web
    //     //     if (!$request->headers->has('X-API-Platform') && 
    //     //         !$this->isCsrfTokenValid('api_token', $request->headers->get('X-CSRF-TOKEN'))) {
    //     //         return new JsonResponse(['error' => 'Token CSRF invalide'], 403);
    //     //     }
    //     // }

    //     $families = $repo->findAll();

    //     $data = array_map(fn($fm) => [
    //         'id' => $fm->getId(),
    //         'familyName' => $fm->getFamilyName(),
    //         'subFamily' => $fm->getSubFamily(),
    //         'family' => $fm->getFamily(),
    //         'tribe' => $fm->getTribe(),
    //         'ordre' => $fm->getOrdre(),
    //         'createdAt' => $fm->getCreatedAt(),
    //         'updatedAt' => $fm->getUpdatedAt(),
    //     ], $families);
    //     return $this->json($data);
    // }
    

    // #[Route('/api/newFamilyOLD', name: 'api_new_family_dataOLD', methods: ['POST'])]
    // public function newFamily(
    //     Request $request,
    //     EntityManagerInterface $entityManager,
    // ): JsonResponse {
    //     $data = json_decode($request->getContent(), true);

    //     if (!$data) {
    //         return new JsonResponse(['error' => 'Invalid data'], 400);
    //     }

    //     try {
    //         // Création BirdFamily
    //         $birdFamily = new BirdFamily();
    //         $birdFamily->setFamilyName($data['familyName'] ?? null);
    //         $birdFamily->setSubFamily($data['subFamily'] ?? null);
    //         $birdFamily->setFamily($data['family'] ?? null);
    //         $birdFamily->setTribe($data['tribe'] ?? null);
    //         $birdFamily->setOrdre($data['ordre'] ?? null);
    //         $birdFamily->setCreatedAt(new \DateTimeImmutable());

    //         $entityManager->persist($birdFamily);
    //         $entityManager->flush();

    //         return new JsonResponse(['status' => 'ok']);
    //     } catch (\Exception $e) {
    //         return new JsonResponse(['error' => $e->getMessage()], 500);
    //     }
    // }

    // #[Route('/api/showFamily/{id}OLD', name: 'api_show_familyOLD', methods: ['GET'])]
    // public function show(BirdFamily $birdFamily, BirdFamilyRepository $birdFamilyRepository): JsonResponse
    // {
    //     $birdFamily = $birdFamilyRepository->find($birdFamily->getId());
    
    //     $data =  [
    //         'id' => $birdFamily->getId(),
    //         'familyName' => $birdFamily->getFamilyName() ?? null,
    //         'subFamily' => $birdFamily->getSubFamily() ?? null,
    //         'tribe' => $birdFamily->getTribe() ?? null,
    //         'ordre' => $birdFamily->getOrdre() ?? null,
    //         'family' => $birdFamily->getFamily() ?? null,
    //         'createdAt' => $birdFamily->getCreatedAt()?->format('d-m-Y H:i:s') ?? null,
    //         'updatedAt' => $birdFamily->getUpdatedAt()?->format('d-m-Y H:i:s') ?? null,
    //     ];
    
    //     return $this->json($data);
    // } 


    #[Route('/api/metadata/countType', name: 'api_metadata_countType', methods: ['GET'])]
    public function countTypes(CountTypeRepository $repo): JsonResponse
    {
        $countTypes = $repo->findAll();

        $data = array_map(fn($ctp) => [
            'id' => $ctp->getId(),
            'label' => $ctp->getLabel(),
        ], $countTypes);

        return $this->json($data);
    }


    #[Route('/api/metadata/quality', name: 'api_metadata_quality', methods: ['GET'])]
    public function qualities(QualityRepository $repo): JsonResponse
    {
        $qualities = $repo->findAll();

        $data = array_map(fn($qlt) => [
            'id' => $qlt->getId(),
            'label' => $qlt->getLabel(),
        ], $qualities);

        return $this->json($data);
    }


    #[Route('/api/metadata/method', name: 'api_metadata_method', methods: ['GET'])]
    public function methodes(MethodRepository $repo): JsonResponse
    {
        $methods = $repo->findAll();

        $data = array_map(fn($mtd) => [
            'id' => $mtd->getId(),
            'label' => $mtd->getLabel(),
        ], $methods);

        return $this->json($data);
    }


    // # Environnemental conditions
    // #[Route('/api/metadata/disturbed', name: 'api_metadata_disturbed', methods: ['GET'])]
    // public function disturbed(DisturbedRepository $repo): JsonResponse
    // {
    //     $disturbeds = $repo->findAll();

    //     $data = array_map(fn($cond) => [
    //         'id' => $cond->getId(),
    //         'label' => $cond->getLabel(),
    //     ], $disturbeds);

    //     return $this->json($data);
    // }

    // #[Route('/api/metadata/weather', name: 'api_metadata_weather', methods: ['GET'])]
    // public function weather(WeatherRepository $repo): JsonResponse
    // {
    //     $weathers = $repo->findAll();

    //     $data = array_map(fn($cond) => [
    //         'id' => $cond->getId(),
    //         'label' => $cond->getLabel(),
    //     ], $weathers);

    //     return $this->json($data);
    // }

    // #[Route('/api/metadata/tidal', name: 'api_metadata_tidal', methods: ['GET'])]
    // public function tidal(TidalRepository $repo): JsonResponse
    // {
    //     $tidals = $repo->findAll();

    //     $data = array_map(fn($cond) => [
    //         'id' => $cond->getId(),
    //         'label' => $cond->getLabel(),
    //     ], $tidals);

    //     return $this->json($data);
    // }

    // #[Route('/api/metadata/water', name: 'api_metadata_water', methods: ['GET'])]
    // public function water(WaterRepository $repo): JsonResponse
    // {
    //     $waters = $repo->findAll();

    //     $data = array_map(fn($cond) => [
    //         'id' => $cond->getId(),
    //         'label' => $cond->getLabel(),
    //     ], $waters);

    //     return $this->json($data);
    // }

    // #[Route('/api/metadata/ice', name: 'api_metadata_ice', methods: ['GET'])]
    // public function ice(IceRepository $repo): JsonResponse
    // {
    //     $ices = $repo->findAll();

    //     $data = array_map(fn($cond) => [
    //         'id' => $cond->getId(),
    //         'label' => $cond->getLabel(),
    //     ], $ices);

    //     return $this->json($data);
    // }
}
