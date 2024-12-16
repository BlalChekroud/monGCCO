<?php

namespace App\Controller;

use App\Repository\AgentsGroupRepository;
use App\Repository\BirdSpeciesCountRepository;
use App\Repository\BirdSpeciesRepository;
use App\Repository\CollectedDataRepository;
use App\Repository\EnvironmentalConditionsRepository;
use App\Repository\LanguageRepository;
use App\Repository\SiteCollectionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use App\Repository\CountingCampaignRepository;
use App\Repository\LogoRepository;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[IsGranted('ROLE_USER')]
class HomeController extends AbstractController
{
    #[Route('/{_locale}', name: 'change_locale', requirements: ['_locale' => '[a-zA-Z]{2}'])]
    public function changeLocale(EntityManagerInterface $entityManager, Request $request, $_locale, LanguageRepository $languageRepository): RedirectResponse
    {
        // Enregistrer la locale dans la session
        $request->getSession()->set('_locale', $_locale);
        
        // Si l'utilisateur est authentifié, met à jour son profil avec la langue choisie
        $user = $this->getUser();
        if ($user) {
            // Rechercher la langue par son code ISO2
            $language = $languageRepository->findOneBy(['iso2' => $_locale]);
            if ($language) {
                $user->setLanguage($language);
                // $user->setLocale($_locale);
                $entityManager->flush();
            } else {
                // Si la langue n'est pas trouvée, vous pouvez ajouter un message d'erreur ou gérer ce cas
                $this->addFlash('error', "La langue sélectionnée n'est pas valide.");
            }
        }

        // Rediriger l'utilisateur vers la page précédente
        $referer = $request->headers->get('referer');
        return new RedirectResponse($referer ?: $this->generateUrl('home'));
    }

    #[Route('/', name: 'home')]
    public function index(
        Request $request,
        BirdSpeciesRepository $birdSpeciesRepository,
        BirdSpeciesCountRepository $birdSpeciesCountRepository,
        EnvironmentalConditionsRepository $environmentalConditionsRepository, 
        AgentsGroupRepository $agentsGroupRepository, 
        CollectedDataRepository $collectedDataRepository, 
        SiteCollectionRepository $siteCollectionRepository, 
        CountingCampaignRepository $countingCampaignRepository, 
        LogoRepository $logoRepository): Response
    {
        $logo = $logoRepository->findOneBy([]); // Fetch the logo
        $campaigns = $countingCampaignRepository->findBy([], ['createdAt' => 'ASC']);

        // Récupérer l'ID de la campagne sélectionnée depuis la requête GET
        $selectedCampaignId = $request->query->get('campaign_id');
        $recentCampaign = $selectedCampaignId 
            ? $countingCampaignRepository->find($selectedCampaignId) 
            : $countingCampaignRepository->findMostRecentCampaign();
        // Récupérer la dernière campagne créée
        // $recentCampaign = $countingCampaignRepository->findMostRecentCampaign();
        if (!$recentCampaign) {
            $this->addFlash('warning', 'Aucune campagne trouvée.');
        }
       
        /**
         * DES STATISTIQUES
         */
        if ($recentCampaign) {
            // Récupérer les données collectées associées à la campagne
            $collectedDataInCampaign = $collectedDataRepository->findByCountingCampaign($recentCampaign);
            // Le nombre de collectes associées à la campagne
            $totalCollectedDataCount = $countingCampaignRepository->countCollectedDataByCampaign($recentCampaign);
            // Compter le nombre total d'agents participants à la campagne
            $totalAgentsCount = $agentsGroupRepository->countAgentsByCountingCampaign($recentCampaign);
            // Toutes les méthodes de collecte de la campagne
            $methodsUsed = $countingCampaignRepository->getMethodsUsedInCampaign($recentCampaign);
            // Total d'oiseaux comptés de la campagne
            $totalBirdsCountedInCampaign = $birdSpeciesCountRepository->countTotalBirdsInCampaign($recentCampaign);
            $totalcountUniqueBirdSpeciesInCampaign = $birdSpeciesCountRepository->countUniqueBirdSpeciesInCampaign($recentCampaign);
            // Le nom de chaque campagne et le nombre total d'oiseaux comptés dans chaque campagne.
            $totalBirdsCountByCampaign = $birdSpeciesCountRepository->getTotalBirdsCountByCampaign();
            // Le nom de chaque campagne et le nombre total d'espèces d'oiseaux uniques comptées dans chaque campagne.
            $totalUniqueBirdSpeciesCountByCampaign = $birdSpeciesCountRepository->getTotalUniqueBirdSpeciesCountByCampaign();
            // Les SiteCollections d'une campagne.
            $siteCollectionsByCampaign = $siteCollectionRepository->getSiteCollectionsByCampaign($recentCampaign);
            //Le nombre total de comptages d'oiseaux pour un site spécifique dans une campagne de comptage
            // $totalBirdCountsForSiteInCampaign = [];
            // foreach ($siteCollectionsByCampaign as $site) {
            //     $totalBirdCountsForSiteInCampaign[$site] = $siteCollectionRepository->getTotalBirdCountsForSiteInCampaign($site, $recentCampaign);
            // }
            // $totalBirdCountsForSiteInCampaign = $siteCollectionRepository->getTotalBirdCountsForSiteInCampaign($site, $recentCampaign);
            // $totalBirdsCountPerSitesInCamapign = $countingCampaignRepository->getTotalBirdsCountPerSitesInCampaign($recentCampaign);
            // Récupérer tous les SiteCollection associés aux groupes d'agents
            foreach ($siteCollectionsByCampaign as $site) {    
                if ($site) {
                    // Récupérer le total des comptages d'oiseaux pour ce site dans la campagne
                    $totalBirdsCountPerSitesInCamapign[$site->getId()] = $siteCollectionRepository->getTotalBirdCountsForSiteInCampaign($site, $recentCampaign);
                    $uniqueBirdSpeciesCountForSite[$site->getId()] = $siteCollectionRepository->getUniqueBirdSpeciesCountForSite($site);
                    $uniqueBirdSpeciesCountForSiteInCampaign[$site->getId()] = $siteCollectionRepository->getUniqueBirdSpeciesCountForSiteInCampaign($site, $recentCampaign);
                }
            }
    
            $topThreeBirdSpeciesInCampaign = $birdSpeciesCountRepository->getTopThreeBirdSpeciesInCampaignWithImages($recentCampaign);
    
            // Récupérer les valeurs les plus choisies des conditions environnementales
            $frequentConditions = $environmentalConditionsRepository->getMostFrequentEnvironmentalConditions($recentCampaign);
            
        }

        // Passer les données au template
        return $this->render('home/index.html.twig', [
            'logo' => $logo,
            'campaigns' => $campaigns ?? [],
            'recentCampaign' => $recentCampaign ?? null,
            'collectedDataInCampaign' => $collectedDataInCampaign ?? [],
            'totalCollectedDataCount' => $totalCollectedDataCount ?? 0,
            'totalAgentsCount' => $totalAgentsCount ?? 0,
            'methodsUsed' => $methodsUsed ?? [],
            'totalBirdsCountedInCampaign' => $totalBirdsCountedInCampaign ?? 0,
            'totalcountUniqueBirdSpeciesInCampaign' => $totalcountUniqueBirdSpeciesInCampaign ?? 0,
            'siteCollectionsByCampaign' => $siteCollectionsByCampaign ?? [],
            'frequentConditions' => $frequentConditions ?? [],
            'totalBirdsCountPerSitesInCamapign' => $totalBirdsCountPerSitesInCamapign ?? 0,
            'uniqueBirdSpeciesCountForSite' => $uniqueBirdSpeciesCountForSite ?? 0,
            'totalBirdsCountByCampaign' => $totalBirdsCountByCampaign ?? 0,
            'totalUniqueBirdSpeciesCountByCampaign' => $totalUniqueBirdSpeciesCountByCampaign ?? 0,
            'uniqueBirdSpeciesCountForSiteInCampaign' => $uniqueBirdSpeciesCountForSiteInCampaign ?? 0,
            'topThreeBirdSpeciesInCampaign' => $topThreeBirdSpeciesInCampaign ?? [],
            'bird_species' => $birdSpeciesRepository->findAll()

        ]);
    }

    // #[Route('/get-campaign-data', name: 'campaign_details', methods: ['GET'])]
    // public function getCampaignDetails(
    //     Request $request,
    //     BirdSpeciesRepository $birdSpeciesRepository,
    //     BirdSpeciesCountRepository $birdSpeciesCountRepository,
    //     EnvironmentalConditionsRepository $environmentalConditionsRepository, 
    //     AgentsGroupRepository $agentsGroupRepository, 
    //     CollectedDataRepository $collectedDataRepository, 
    //     SiteCollectionRepository $siteCollectionRepository, 
    //     CountingCampaignRepository $countingCampaignRepository
    // ): JsonResponse {
    //     $campaignId = $request->query->get('campaign_id');

    //     if (!$campaignId) {
    //         return new JsonResponse(['error' => 'Campaign ID is required'], 400);
    //     }

    //     $campaign = $countingCampaignRepository->find($campaignId);

    //     if (!$campaign) {
    //         return new JsonResponse(['error' => 'Campaign not found'], 404);
    //     }

    //     // Récupérer les données principales
    //     $collectedDataInCampaign = $collectedDataRepository->findByCountingCampaign($campaign);
    //     $totalCollectedDataCount = $countingCampaignRepository->countCollectedDataByCampaign($campaign);
    //     $totalAgentsCount = $agentsGroupRepository->countAgentsByCountingCampaign($campaign);
    //     $methodsUsed = $countingCampaignRepository->getMethodsUsedInCampaign($campaign);
    //     $totalBirdsCountedInCampaign = $birdSpeciesCountRepository->countTotalBirdsInCampaign($campaign);
    //     $totalUniqueBirdSpeciesInCampaign = $birdSpeciesCountRepository->countUniqueBirdSpeciesInCampaign($campaign);
    //     $totalBirdsCountByCampaign = $birdSpeciesCountRepository->getTotalBirdsCountByCampaign();
    //     $totalUniqueBirdSpeciesCountByCampaign = $birdSpeciesCountRepository->getTotalUniqueBirdSpeciesCountByCampaign();
    //     $siteCollectionsByCampaign = $siteCollectionRepository->getSiteCollectionsByCampaign($campaign);
    //     $topThreeBirdSpeciesInCampaign = $birdSpeciesCountRepository->getTopThreeBirdSpeciesInCampaignWithImages($campaign);
    //     $frequentConditions = $environmentalConditionsRepository->getMostFrequentEnvironmentalConditions($campaign);

    //     // Calculer les données par site
    //     $totalBirdsCountPerSitesInCampaign = [];
    //     $uniqueBirdSpeciesCountForSite = [];
    //     $uniqueBirdSpeciesCountForSiteInCampaign = [];

    //     foreach ($siteCollectionsByCampaign as $site) {    
    //         if ($site) {
    //             $totalBirdsCountPerSitesInCampaign[$site->getId()] = $siteCollectionRepository->getTotalBirdCountsForSiteInCampaign($site, $campaign);
    //             $uniqueBirdSpeciesCountForSite[$site->getId()] = $siteCollectionRepository->getUniqueBirdSpeciesCountForSite($site);
    //             $uniqueBirdSpeciesCountForSiteInCampaign[$site->getId()] = $siteCollectionRepository->getUniqueBirdSpeciesCountForSiteInCampaign($site, $campaign);
    //         }
    //     }

    //     // Préparer la réponse
    //     $response = [
    //         'collectedDataInCampaign' => $collectedDataInCampaign,
    //         'totalCollectedDataCount' => $totalCollectedDataCount,
    //         'totalAgentsCount' => $totalAgentsCount,
    //         'methodsUsed' => $methodsUsed,
    //         'totalBirdsCountedInCampaign' => $totalBirdsCountedInCampaign,
    //         'totalUniqueBirdSpeciesInCampaign' => $totalUniqueBirdSpeciesInCampaign,
    //         'siteCollectionsByCampaign' => $siteCollectionsByCampaign,
    //         'frequentConditions' => $frequentConditions,
    //         'totalBirdsCountPerSitesInCampaign' => $totalBirdsCountPerSitesInCampaign,
    //         'uniqueBirdSpeciesCountForSite' => $uniqueBirdSpeciesCountForSite,
    //         'totalBirdsCountByCampaign' => $totalBirdsCountByCampaign,
    //         'totalUniqueBirdSpeciesCountByCampaign' => $totalUniqueBirdSpeciesCountByCampaign,
    //         'uniqueBirdSpeciesCountForSiteInCampaign' => $uniqueBirdSpeciesCountForSiteInCampaign,
    //         'topThreeBirdSpeciesInCampaign' => $topThreeBirdSpeciesInCampaign,
    //         'bird_species' => $birdSpeciesRepository->findAll(),
    //     ];

    //     return new JsonResponse($response);
    // }

}
