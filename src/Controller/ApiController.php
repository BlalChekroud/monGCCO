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
use Symfony\Component\HttpFoundation\JsonResponse;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ApiController extends AbstractController
{
    /* 
    *   FAMILY
    */

    // #[Route('/api/family/', name: 'api_metadata_families', methods: ['GET'])]
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

    #[Route('/api/family/', name: 'api_metadata_families', methods: ['GET'])]
    public function index(BirdFamilyRepository $repo): JsonResponse
    {
        // $this->denyAccessUnlessGranted('ROLE_USER'); // 🔒 obligatoire

        $families = $repo->findAll();

        $data = array_map(fn($fm) => [
            'id' => $fm->getId(),
            'familyName' => $fm->getFamilyName(),
            'subFamily' => $fm->getSubFamily(),
            'family' => $fm->getFamily(),
            'tribe' => $fm->getTribe(),
            'ordre' => $fm->getOrdre(),
            'createdAt' => $fm->getCreatedAt(),
            'updatedAt' => $fm->getUpdatedAt(),
        ], $families);

        return $this->json($data);
    }
    

    #[Route('/api/family/newFamily', name: 'api_new_family_data', methods: ['POST'])]
    public function newFamily(
        Request $request,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return new JsonResponse(['error' => 'Invalid data'], 400);
        }

        try {
            // Création BirdFamily
            $birdFamily = new BirdFamily();
            $birdFamily->setFamilyName($data['familyName'] ?? null);
            $birdFamily->setSubFamily($data['subFamily'] ?? null);
            $birdFamily->setFamily($data['family'] ?? null);
            $birdFamily->setTribe($data['tribe'] ?? null);
            $birdFamily->setOrdre($data['ordre'] ?? null);
            $birdFamily->setCreatedAt(new \DateTimeImmutable());

            $entityManager->persist($birdFamily);
            $entityManager->flush();

            return new JsonResponse(['status' => 'ok']);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 500);
        }
    }

    #[Route('/api/family/f{id}', name: 'api_show_family', methods: ['GET'])]
    public function show(BirdFamily $birdFamily, BirdFamilyRepository $birdFamilyRepository): JsonResponse
    {
        $birdFamily = $birdFamilyRepository->find($birdFamily->getId());
    
        $data =  [
            'id' => $birdFamily->getId(),
            'familyName' => $birdFamily->getFamilyName() ?? null,
            'subFamily' => $birdFamily->getSubFamily() ?? null,
            'tribe' => $birdFamily->getTribe() ?? null,
            'ordre' => $birdFamily->getOrdre() ?? null,
            'family' => $birdFamily->getFamily() ?? null,
            'createdAt' => $birdFamily->getCreatedAt()?->format('d-m-Y H:i:s') ?? null,
            'updatedAt' => $birdFamily->getUpdatedAt()?->format('d-m-Y H:i:s') ?? null,
        ];
    
        return $this->json($data);
    } 

    // #[Route('/api', name: 'app_api')]
    // public function index(): Response
    // {
    //     return $this->render('api/index.html.twig', [
    //         'controller_name' => 'ApiController',
    //     ]);
    // }
}
