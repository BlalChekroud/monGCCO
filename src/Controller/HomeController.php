<?php

namespace App\Controller;

use App\Repository\AgentsGroupRepository;
use App\Repository\BirdSpeciesCountRepository;
use App\Repository\BirdSpeciesRepository;
use App\Repository\CampaignStatusRepository;
use App\Repository\CollectedDataRepository;
use App\Repository\EnvironmentalConditionsRepository;
use App\Repository\SiteCollectionRepository;
use Doctrine\ORM\EntityManagerInterface;
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
    #[Route('/{_locale}', name: 'change_locale', requirements: ['_locale' => 'en|fr'])]
    public function changeLocale(EntityManagerInterface $entityManager, Request $request, $_locale): RedirectResponse
    {
        // Enregistrer la locale dans la session
        $request->getSession()->set('_locale', $_locale);

        // Si l'utilisateur est authentifié, met à jour son profil avec la langue choisie
        $user = $this->getUser();
        if ($user) {
            $user->setLocale($_locale);
            $entityManager->flush();
        }

        // Rediriger l'utilisateur vers la page précédente
        $referer = $request->headers->get('referer');
        return new RedirectResponse($referer ?: $this->generateUrl('home'));
    }

    #[Route('/', name: 'home')]
    public function index(
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
        $campaigns = $countingCampaignRepository->findBy([], ['endDate' => 'ASC']);

        
        // Récupérer la dernière campagne créée
        $recentCampaign = $countingCampaignRepository->findMostRecentCampaign();
        if (!$recentCampaign) {
            $this->addFlash('warning', 'Aucune campagne trouvée.');
            // return $this->redirectToRoute('home'); 
        }
       
        /**
         * DES STATISTIQUES
         */
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

        // Passer les données au template
        return $this->render('home/index.html.twig', [
            'logo' => $logo,
            'campaigns' => $campaigns,
            'recentCampaign' => $recentCampaign,
            'collectedDataInCampaign' => $collectedDataInCampaign,
            'totalCollectedDataCount' => $totalCollectedDataCount,
            'totalAgentsCount' => $totalAgentsCount,
            'methodsUsed' => $methodsUsed,
            'totalBirdsCountedInCampaign' => $totalBirdsCountedInCampaign,
            'totalcountUniqueBirdSpeciesInCampaign' => $totalcountUniqueBirdSpeciesInCampaign,
            'siteCollectionsByCampaign' => $siteCollectionsByCampaign,
            'frequentConditions' => $frequentConditions,
            'totalBirdsCountPerSitesInCamapign' => $totalBirdsCountPerSitesInCamapign,
            'uniqueBirdSpeciesCountForSite' => $uniqueBirdSpeciesCountForSite,
            'totalBirdsCountByCampaign' => $totalBirdsCountByCampaign,
            'totalUniqueBirdSpeciesCountByCampaign' => $totalUniqueBirdSpeciesCountByCampaign,
            'uniqueBirdSpeciesCountForSiteInCampaign' => $uniqueBirdSpeciesCountForSiteInCampaign,
            'topThreeBirdSpeciesInCampaign' => $topThreeBirdSpeciesInCampaign,
            'bird_species' => $birdSpeciesRepository->findAll()

            // 'totalCollects' => $totalCollects,
            // 'totalAgents' => $totalAgents,
            // 'categories' => $categories,
            // 'totalBirds' => $totalBirds,
            // 'totalUniqueSpeciesCount' => $totalUniqueSpeciesCount,
            // 'totalAgentsCount' => $totalAgentsCount,
            // 'totalCounts' => $totalCounts,

            // 'totalUniqueSpecies' => $totalUniqueSpecies,
            // 'siteNames' => $siteNames,       // Les noms des sites
            // 'totalCountsSite' => $totalCountsSite,   // Les totaux des oiseaux comptés
        ]);
    }

}
