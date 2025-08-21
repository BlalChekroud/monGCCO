<?php

namespace App\Controller;

use App\Entity\CountingCampaign;
use App\Repository\BirdSpeciesRepository;
use App\Repository\CountingCampaignRepository;
use App\Repository\CountTypeRepository;
use App\Repository\DisturbedRepository;
use App\Repository\IceRepository;
use App\Repository\MethodRepository;
use App\Repository\QualityRepository;
use App\Repository\TidalRepository;
use App\Repository\WaterRepository;
use App\Repository\WeatherRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class MetadataController extends AbstractController
{
    #[Route('/api/metadata/campaigns', name: 'api_metadata_campaigns', methods: ['GET'])]
    public function campaigns(CountingCampaignRepository $countingCampaignRepository): JsonResponse
    {
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
    public function species(BirdSpeciesRepository $repo): JsonResponse
    {
        $species = $repo->findAll();

        $data = array_map(fn($sp) => [
            'id' => $sp->getId(),
            'scientificName' => $sp->getScientificName(),
            'frenchName' => $sp->getFrenchName(),
        ], $species);

        return $this->json($data);
    }


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
