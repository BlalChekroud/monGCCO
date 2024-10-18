<?php

namespace App\Controller;

use App\Repository\CountingCampaignRepository;
use App\Repository\LogoRepository;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[IsGranted('ROLE_USER')]
class HomeController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function index(CountingCampaignRepository $countingCampaignRepository, LogoRepository $logoRepository): Response
{
    $logo = $logoRepository->findOneBy([]); // Fetch the logo
    $campaigns = $countingCampaignRepository->findBy([], ['endDate' => 'ASC']);

    // Initialiser les tableaux pour les données des graphiques
    $totalCounts = [];
    $totalCollects = [];
    $totalAgents = [];
    $totalUniqueSpecies = [];
    $categories = [];
    
    // Récupérer la dernière campagne créée
    $recentCampaign = $countingCampaignRepository->findMostRecentCampaign();
    if (!$recentCampaign) {
        $this->addFlash('warning', 'Aucune campagne trouvée.');
        return $this->redirectToRoute('home'); 
    }
    
    // Utiliser la méthode pour récupérer les comptages par site
    $totalCountsBySite = $countingCampaignRepository->getTotalCountsBySite($recentCampaign);

    // Initialiser les tableaux pour les données du graphique
    $siteNames = [];
    $totalCountsSite = [];

    foreach ($totalCountsBySite as $siteData) {
        $siteNames[] = $siteData['siteName']; // Récupérer les noms des sites
        $totalCountsSite[] = $siteData['totalCounts']; // Récupérer le total des comptages par site
    }

    // Parcourir les campagnes pour extraire les données
    foreach ($campaigns as $campaign) {
        $totalCounts[] = $campaign->getTotalCountsCampaign();
        $totalCollects[] = $campaign->getTotalCollects();
        $totalAgents[] = $campaign->getTotalAgents();
        $totalUniqueSpecies[] = $campaign->getTotalUniqueSpecies();
        $categories[] = $campaign->getEndDate()->format('d-m-Y');

    }





    // Calculer les totaux
    $totalBirds = array_sum($totalCounts);
    $totalUniqueSpeciesCount = array_sum($totalUniqueSpecies);
    $totalAgentsCount = array_sum($totalAgents);

    // Passer les données au template
    return $this->render('home/index.html.twig', [
        'logo' => $logo,
        'campaigns' => $campaigns,
        'totalCollects' => $totalCollects,
        'totalAgents' => $totalAgents,
        'categories' => $categories,
        'totalBirds' => $totalBirds,
        'totalUniqueSpeciesCount' => $totalUniqueSpeciesCount,
        'totalAgentsCount' => $totalAgentsCount,
        'totalCounts' => $totalCounts,

        'recentCampaign' => $recentCampaign,
        'totalUniqueSpecies' => $totalUniqueSpecies,
        'siteNames' => $siteNames,       // Les noms des sites
        'totalCountsSite' => $totalCountsSite,   // Les totaux des oiseaux comptés
    ]);
}

}
