<?php

namespace App\Controller;

use App\Entity\CountingCampaign;
use App\Entity\SiteCollection;
use App\Repository\BirdSpeciesRepository;
use App\Repository\EnvironmentalConditionsRepository;
use Symfony\Component\Security\Http\Attribute\IsGranted;

use Monolog\DateTimeImmutable;
use DateTime;
use App\Entity\CollectedData;
use App\Form\CollectedDataType;
use App\Repository\CollectedDataRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/collected/data')]
#[IsGranted('ROLE_COLLECTOR', message: 'Vous n\'avez pas l\'accès.')]
class CollectedDataController extends AbstractController
{
    #[Route('/', name: 'app_collected_data_index', methods: ['GET'])]
    public function index(CollectedDataRepository $collectedDataRepository): Response
    {
        // $this->denyAccessUnlessGranted('ROLE_USER', null,'Access Denied.');
        return $this->render('collected_data/index.html.twig', [
            'collected_datas' => $collectedDataRepository->findAll(),
        ]);
    }

    // #[Route('/new', name: 'app_collected_data_new', methods: ['GET', 'POST'])]
    // public function new(Request $request, BirdSpeciesRepository $birdSpeciesRepository, EntityManagerInterface $entityManager, EnvironmentalConditionsRepository $environmentalConditionsRepository): Response
    // {
    //     // Vérifier si les conditions environnementales existent pour l'utilisateur actuel
    //     $user = $this->getUser();
    //     $environmentalConditions = $environmentalConditionsRepository->findOneBy(['user' => $user]);

    //     // Récupérer toutes les espèces d'oiseaux
    //     $birdSpecies = $birdSpeciesRepository->findAll();
    //     if (!$environmentalConditions) {
    //         // Rediriger vers la page de création des conditions environnementales si elles n'existent pas
    //         $this->addFlash('warning', 'Veuillez d\'abord créer les conditions environnementales.');
    //         return $this->redirectToRoute('app_environmental_conditions_new');
    //     }

    //     // Récupérer la campagne et le site associés aux conditions environnementales
    //     $campaign = $environmentalConditions->getCountingCampaign();
    //     $site = $environmentalConditions->getSiteCollection();

    //     // Créer une nouvelle entité CollectedData
    //     $collectedDatum = new CollectedData();

    //     // Assigner directement la campagne et le site de la collecte à partir des conditions environnementales
    //     $collectedDatum->setCountingCampaign($campaign);
    //     $collectedDatum->setSiteCollection($site);

    //     // Créer le formulaire pour les données collectées
    //     $form = $this->createForm(CollectedDataType::class, $collectedDatum);
    //     $form->handleRequest($request);

    //     if ($form->isSubmitted()) {
    //         if ($form->isValid()) {
    //              // Vérifiez les propriétés nécessaires
    //             $hasErrors = false;
    //             $errorMessage = "";

    //             // Vérifiez la présence des espèces d'oiseaux
    //             if ($collectedDatum->getBirdSpeciesCounts()->isEmpty()) {
    //                 $hasErrors = true;
    //                 $errorMessage .= "Aucune espèce d'oiseau sélectionnée. ";
    //             }

    //             // Vérifiez la présence de méthodes
    //             if ($collectedDatum->getMethod()->isEmpty()) {
    //                 $hasErrors = true;
    //                 $errorMessage .= "Aucune méthode sélectionnée. ";
    //             }

    //             // Vérifiez la présence de qualité et de type de comptage si nécessaire
    //             if ($collectedDatum->getQuality() === null) {
    //                 $hasErrors = true;
    //                 $errorMessage .= "La qualité est requise. ";
    //             }

    //             if ($collectedDatum->getCountType() === null) {
    //                 $hasErrors = true;
    //                 $errorMessage .= "Le type de comptage est requis. ";
    //             }

    //             // Vérifiez les données d'environnement
    //             if (!$campaign) {
    //                 $hasErrors = true;
    //                 $errorMessage .= "La campagne de comptage est manquante. ";
    //             }

    //             if (!$site) {
    //                 $hasErrors = true;
    //                 $errorMessage .= "Le site de collecte est manquant. ";
    //             }

    //             // Vérifier si le `SiteCollection` de `CollectedData` correspond à celui de `EnvironmentalConditions`
    //             if ($collectedDatum->getSiteCollection() !== $environmentalConditions->getSiteCollection()) {
    //                 $hasErrors = true;
    //                 $errorMessage .= "Le site de collecte ne correspond pas aux conditions environnementales.";
    //             }

    //             // Si des erreurs sont présentes, affichez un message d'erreur et redirigez
    //             if ($hasErrors) {
    //                 $this->addFlash('error', $errorMessage);
    //                 return $this->redirectToRoute('app_collected_data_new', [], Response::HTTP_SEE_OTHER);
    //             }

    //             // Enregistrez les données
    //             $collectedDatum->setCreatedAt(new \DateTimeImmutable());
    //             $collectedDatum->setEnvironmentalConditions($environmentalConditions);
    //             $collectedDatum->setCreatedBy($user);
    //             $entityManager->persist($collectedDatum);

    //             foreach ($collectedDatum->getBirdSpeciesCounts() as $birdSpeciesCount) {
    //                 $birdSpeciesCount->setCollectedData($collectedDatum);
    //                 $entityManager->persist($birdSpeciesCount);
    //             }
    //             $entityManager->flush();
    //             $this->addFlash('success', "Les données collectées ont été créées avec succès.");
    
    //             return $this->redirectToRoute('app_collected_data_index', [], Response::HTTP_SEE_OTHER);
    //         } else {
    //             $this->addFlash('error','Une erreur s\'est produite lors de la création de la collection de données.');
    //         }
    //     }

    //     return $this->render('collected_data/new.html.twig', [
    //         'collected_datum' => $collectedDatum,
    //         'form' => $form,
    //         'siteCollection' => $site,
    //     ]);
    // }
    
    #[Route('/new', name: 'app_collected_data_new', methods: ['GET', 'POST'])]
    public function new(Request $request, BirdSpeciesRepository $birdSpeciesRepository, EntityManagerInterface $entityManager, EnvironmentalConditionsRepository $environmentalConditionsRepository): Response
    {
        // Récupérer l'utilisateur actuel
        $user = $this->getUser();

        // Récupérer la campagne et le site en fonction des paramètres de la requête ou d'un choix de l'utilisateur
        $campaignId = $request->query->get('campaignId');
        $siteId = $request->query->get('siteId');

        // Vérification des paramètres de la requête
        // if (!$campaignId || !$siteId) {
        //     $this->addFlash('error', 'La campagne ou le site est manquant.');
        //     return $this->redirectToRoute('app_collected_data_index');
        // }
        // Redirection par défaut pour tester si les valeurs existent

        $campaign = $entityManager->getRepository(CountingCampaign::class)->find($campaignId);
        $site = $entityManager->getRepository(SiteCollection::class)->find($siteId);

        // Vérifier si la campagne et le site existent
        if (!$campaign) {
            $this->addFlash('error', 'La campagne spécifiée est introuvable.');
            return $this->redirectToRoute('app_collected_data_index');
        }
        
        if (!$site) {
            $this->addFlash('error', 'Le site spécifié est introuvable.');
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
        if (!$environmentalConditions) {
            // Rediriger vers la page de création des conditions environnementales si elles n'existent pas
            $this->addFlash('warning', 'Veuillez d\'abord créer les conditions environnementales pour ce site.');
            return $this->redirectToRoute('app_environmental_conditions_new', [
                'campaignId' => $campaignId,
                'siteId' => $siteId
            ]);
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
                 // Vérifiez les propriétés nécessaires
                $hasErrors = false;
                $errorMessage = "";

                // Vérifiez la présence des espèces d'oiseaux
                if ($collectedDatum->getBirdSpeciesCounts()->isEmpty()) {
                    $hasErrors = true;
                    $errorMessage .= "Aucune espèce d'oiseau sélectionnée. ";
                }

                // Vérifiez la présence de méthodes
                if ($collectedDatum->getMethod()->isEmpty()) {
                    $hasErrors = true;
                    $errorMessage .= "Aucune méthode sélectionnée. ";
                }

                // Vérifiez la présence de qualité et de type de comptage si nécessaire
                if ($collectedDatum->getQuality() === null) {
                    $hasErrors = true;
                    $errorMessage .= "La qualité est requise. ";
                }

                if ($collectedDatum->getCountType() === null) {
                    $hasErrors = true;
                    $errorMessage .= "Le type de comptage est requis. ";
                }

                // Vérifiez les données d'environnement
                if (!$campaign) {
                    $hasErrors = true;
                    $errorMessage .= "La campagne de comptage est manquante. ";
                }

                if (!$site) {
                    $hasErrors = true;
                    $errorMessage .= "Le site de collecte est manquant. ";
                }

                // Vérifier si le `SiteCollection` de `CollectedData` correspond à celui de `EnvironmentalConditions`
                if ($collectedDatum->getSiteCollection() !== $environmentalConditions->getSiteCollection()) {
                    $hasErrors = true;
                    $errorMessage .= "Le site de collecte ne correspond pas aux conditions environnementales.";
                }

                // Si des erreurs sont présentes, affichez un message d'erreur et redirigez
                if ($hasErrors) {
                    $this->addFlash('error', $errorMessage);
                    return $this->redirectToRoute('app_collected_data_new', [], Response::HTTP_SEE_OTHER);
                }

                // Enregistrez les données collectées
                $collectedDatum->setCreatedAt(new \DateTimeImmutable());
                $entityManager->persist($collectedDatum);

                foreach ($collectedDatum->getBirdSpeciesCounts() as $birdSpeciesCount) {
                    $birdSpeciesCount->setCollectedData($collectedDatum);
                    $entityManager->persist($birdSpeciesCount);
                }
                // Sauvegarde des données
                $entityManager->flush();
                $this->addFlash('success', "Les données collectées ont été créées avec succès.");
    
                return $this->redirectToRoute('app_collected_data_index', [], Response::HTTP_SEE_OTHER);
            } else {
                $this->addFlash('error','Une erreur s\'est produite lors de la création de la collection de données.');
            }
        }

        return $this->render('collected_data/new.html.twig', [
            'collected_datum' => $collectedDatum,
            'form' => $form,
            'siteCollection' => $site,
            'campaign_id' => $campaignId,
        ]);
    }

    #[Route('/{id}', name: 'app_collected_data_show', methods: ['GET'])]
    public function show(CollectedData $collectedDatum): Response
    {
        return $this->render('collected_data/show.html.twig', [
            'collected_datum' => $collectedDatum,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_collected_data_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, CollectedData $collectedDatum, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(CollectedDataType::class, $collectedDatum);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $collectedDatum->setUpdatedAt(DateTimeImmutable::createFromMutable(new DateTime()));
                $entityManager->flush();
                $this->addFlash('success', "Les données ont été mises à jour avec succès.");
    
                return $this->redirectToRoute('app_collected_data_index', [], Response::HTTP_SEE_OTHER);
            } else {
                $this->addFlash('error','Une erreur s\'est produite lors de la modification de la collection de données.');
            }
        }

        return $this->render('collected_data/edit.html.twig', [
            'collected_datum' => $collectedDatum,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_collected_data_delete', methods: ['POST'])]
    public function delete(Request $request, CollectedData $collectedDatum, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$collectedDatum->getId(), $request->getPayload()->get('_token'))) {
            $entityManager->remove($collectedDatum);
            $entityManager->flush();
            $this->addFlash('success', "Les données collectées ont été supprimées avec succès.");
        } else {
            $this->addFlash('error','Une erreur s\'est produite lors de la suppression de la collection de données.');
        }

        return $this->redirectToRoute('app_collected_data_index', [], Response::HTTP_SEE_OTHER);
    }
}
