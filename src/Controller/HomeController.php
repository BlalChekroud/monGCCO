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
    $totalCountsPerSite = []; // Initialiser ici

    // Parcourir les campagnes pour extraire les données
    foreach ($campaigns as $campaign) {
        $totalCounts[] = $campaign->getTotalCountsCampaign();
        $totalCollects[] = $campaign->getTotalCollects();
        $totalAgents[] = $campaign->getTotalAgents();
        $totalUniqueSpecies[] = $campaign->getTotalUniqueSpecies();
        $categories[] = $campaign->getEndDate()->format('d-m-Y');

        // Récupérer les données par site
        foreach ($campaign->getSiteAgentsGroups() as $siteAgentsGroup) {
            foreach ($siteAgentsGroup->getSiteCollection() as $site) {
                $siteName = $site->getSiteName();
                $totalCountsPerSite[$siteName] = ($totalCountsPerSite[$siteName] ?? 0) + $site->getTotalCounts();
            }
        }
    }

    // Récupérer la campagne la plus récente
    $recentCampaign = end($campaigns); // Récupère la dernière campagne de la liste triée

    // Calculer les totaux
    $totalBirds = array_sum($totalCounts);
    $totalUniqueSpeciesCount = array_sum($totalUniqueSpecies);
    $totalAgentsCount = array_sum($totalAgents);

    // Passer les données au template
    return $this->render('home/index.html.twig', [
        'logo' => $logo,
        'campaigns' => $campaigns,
        'totalCounts' => $totalCounts,
        'totalCollects' => $totalCollects,
        'totalAgents' => $totalAgents,
        'totalUniqueSpecies' => $totalUniqueSpecies,
        'categories' => $categories,
        'recentCampaign' => $recentCampaign,
        'totalBirds' => $totalBirds,
        'totalUniqueSpeciesCount' => $totalUniqueSpeciesCount,
        'totalAgentsCount' => $totalAgentsCount,
        'totalCountsPerSite' => $totalCountsPerSite,
    ]);
}

}
