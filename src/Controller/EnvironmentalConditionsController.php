<?php

namespace App\Controller;

use App\Entity\CountingCampaign;
use App\Entity\SiteCollection;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Entity\EnvironmentalConditions;
use App\Form\EnvironmentalConditionsType;
use App\Repository\EnvironmentalConditionsRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/environmental/conditions')]
#[IsGranted('ROLE_COLLECTOR', message: 'Vous n\'avez pas l\'accès.')]
class EnvironmentalConditionsController extends AbstractController
{
    #[Route('/', name: 'app_environmental_conditions_index', methods: ['GET'])]
    public function index(EnvironmentalConditionsRepository $environmentalConditionsRepository): Response
    {
        $user = $this->getUser();
        
        // Vérifier si l'utilisateur a le rôle ADMIN
        if ($this->isGranted('ROLE_ADMIN')) {
            $environmentalConditions = $environmentalConditionsRepository->findAll();
        } else {
            $environmentalConditions = $environmentalConditionsRepository->findByUser($user);
        }
        return $this->render('environmental_conditions/index.html.twig', [
            'environmental_conditions' => $environmentalConditions,
        ]);
    }

    // #[Route('/new', name: 'app_environmental_conditions_new', methods: ['GET', 'POST'])]
    // public function new(Request $request, EntityManagerInterface $entityManager): Response
    // {
    //     $user = $this->getUser(); // Récupérer l'utilisateur actuel
    //     // Récupérer les identifiants du site et de la campagne depuis les paramètres de la requête
    //     $siteId = $request->query->get('siteId');
    //     $campaignId = $request->query->get('campaignId');

    //     // Récupérer les entités Site et Campaign associées
    //     $site = $entityManager->getRepository(SiteCollection::class)->find($siteId);
    //     $campaign = $entityManager->getRepository(CountingCampaign::class)->find($campaignId);
        
    //     if (!$site || !$campaign) {
    //         throw $this->createNotFoundException('Site or Campaign not found');
    //     }
        
    //     if ($user) {
    //         $this->addFlash('error', "L'utilsateur a deja cree conditions d'environnement pour cette site");
    //     }
    //     $environmentalCondition = new EnvironmentalConditions();
    //     $form = $this->createForm(EnvironmentalConditionsType::class, $environmentalCondition);
    //     $form->handleRequest($request);

    //     if ($form->isSubmitted()) {
    //         if ($form->isValid()) {
    //             $environmentalCondition->setCreatedAt(\DateTimeImmutable::createFromMutable(new DateTime()));
    //             $environmentalCondition->setSiteCollection($site);
    //             $environmentalCondition->setCountingCampaign($campaign);
    //             $environmentalCondition->setUser($user);
    //             $entityManager->persist($environmentalCondition);
    //             $entityManager->flush();
    //             $this->addFlash('success', "Conditions d'environnement a bien été crée");
    
    //             return $this->redirectToRoute('app_collected_data_new', [], Response::HTTP_SEE_OTHER);
    //         } else {
    //             // Log the errors for debugging
    //             foreach ($form->getErrors(true) as $error) {
    //                 error_log($error->getMessage());
    //             }
    //             $this->addFlash('error','Une erreur s\'est produite lors de la création de conditions d\'environnement.');
    //         }
    //     }

    //     return $this->render('environmental_conditions/new.html.twig', [
    //         'environmental_condition' => $environmentalCondition,
    //         'form' => $form,
    //     ]);
    // }

    #[Route('/new', name: 'app_environmental_conditions_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser(); // Récupérer l'utilisateur actuel
        $siteId = $request->query->get('siteId');
        $campaignId = $request->query->get('campaignId');

        $site = $entityManager->getRepository(SiteCollection::class)->find($siteId);
        $campaign = $entityManager->getRepository(CountingCampaign::class)->find($campaignId);
        
        if (!$site || !$campaign) {
            throw $this->createNotFoundException('Site or Campaign not found');
        }

        // Vérifiez si l'utilisateur a déjà créé une condition environnementale pour ce site et cette campagne
        $existingCondition = $entityManager->getRepository(EnvironmentalConditions::class)->findOneBy([
            'user' => $user,
            'siteCollection' => $site,
            'countingCampaign' => $campaign
        ]);

        // if ($existingCondition) {
        //     $this->addFlash('info', "L'utilisateur a déjà créé des conditions d'environnement pour ce site.");
        //     return $this->redirectToRoute('app_environmental_conditions_index', [], Response::HTTP_SEE_OTHER);
        // }

        // if ($user not in $campaign->getAgentsGroups()) {
        //     $this->addFlash('info','Vous etes pas membre pour creer condition d\'environnement');
        //     throw $this->createNotFoundException('Vous etes pas membre pour creer condition d\'environnement');
        // }

        $environmentalCondition = new EnvironmentalConditions();
        $form = $this->createForm(EnvironmentalConditionsType::class, $environmentalCondition);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()){
                $environmentalCondition->setCreatedAt(\DateTimeImmutable::createFromMutable(new \DateTime()));
                $environmentalCondition->setSiteCollection($site);
                $environmentalCondition->setCountingCampaign($campaign);
                $environmentalCondition->setUser($user);
    
                $entityManager->persist($environmentalCondition);
                $entityManager->flush();
    
                $this->addFlash('success', "Conditions d'environnement ont bien été créées");
                return $this->redirectToRoute('app_collected_data_new', ['campaignId' => $campaignId, 'siteId' => $siteId  ], Response::HTTP_SEE_OTHER);
            } else {
                $this->addFlash('error','Une erreur s\'est produite lors de la création de conditions d\'environnement.');
            }
        }

        return $this->render('environmental_conditions/new.html.twig', [
            'environmental_condition' => $environmentalCondition,
            'form' => $form,
            'siteCollection' => $site,
        ]);
    }


    #[Route('/{id}', name: 'app_environmental_conditions_show', methods: ['GET'])]
    public function show(EnvironmentalConditions $environmentalCondition): Response
    {
        return $this->render('environmental_conditions/show.html.twig', [
            'environmental_condition' => $environmentalCondition,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_environmental_conditions_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, EnvironmentalConditions $environmentalCondition, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser(); // Récupérer l'utilisateur actuel

        if ($user != $environmentalCondition->getUser() && !$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('error', "Vous n'avez pas l'autorisation pour faire la modification");
            return $this->redirectToRoute('app_environmental_conditions_index', [], Response::HTTP_SEE_OTHER);
        }

        $form = $this->createForm(EnvironmentalConditionsType::class, $environmentalCondition);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $environmentalCondition->setUpdatedAt(\DateTimeImmutable::createFromMutable(new DateTime()));
                $entityManager->flush();
                $this->addFlash('success', "Conditions a bien été modifié");
    
                return $this->redirectToRoute('app_environmental_conditions_index', [], Response::HTTP_SEE_OTHER);
            } else {
                // Log the errors for debugging
                foreach ($form->getErrors(true) as $error) {
                    error_log($error->getMessage());
                }
                $this->addFlash('error','Une erreur s\'est produite lors de la modification de conditions d\'environnement.');
            }
        }

        return $this->render('environmental_conditions/edit.html.twig', [
            'environmental_condition' => $environmentalCondition,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_environmental_conditions_delete', methods: ['POST'])]
    public function delete(Request $request, EnvironmentalConditions $environmentalCondition, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$environmentalCondition->getId(), $request->getPayload()->get('_token'))) {
            $entityManager->remove($environmentalCondition);
            $entityManager->flush();
            $this->addFlash('success', "Conditions a bien été supprimée");
        } else {
            $this->addFlash('error','Une erreur s\'est produite lors de la suppression de conditions d\'environnement.');
        }

        return $this->redirectToRoute('app_environmental_conditions_index', [], Response::HTTP_SEE_OTHER);
    }
}
