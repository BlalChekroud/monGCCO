<?php

namespace App\Controller;

use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Entity\AgentsGroup;
use App\Form\AgentsGroupType;
use App\Repository\AgentsGroupRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/user/agents/group')]
class AgentsGroupController extends AbstractController
{
    #[Route('/', name: 'app_agents_group_index', methods: ['GET'])]
    public function index(AgentsGroupRepository $agentsGroupRepository): Response
    {
        $user = $this->getUser();
        
        // Vérifier si l'utilisateur a le rôle ADMIN
        if ($this->isGranted('ROLE_ADMIN')) {
            $agentsGroups = $agentsGroupRepository->findAll();
        } else {
            $agentsGroups = $agentsGroupRepository->findByUserMember($user);
        }
        return $this->render('agents_group/index.html.twig', [
            'agents_groups' => $agentsGroups,
        ]);
    }

    #[Route('/new', name: 'app_agents_group_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_CREAT', message: 'Vous n\'avez pas l\'accès.')]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $agentsGroup = new AgentsGroup();
        $form = $this->createForm(AgentsGroupType::class, $agentsGroup);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                if ($agentsGroup->getGroupMember()->isEmpty()) {
                    $this->addFlash('error', 'Vous devez sélectionner au moins un membre pour créer un groupe d\'agents.');
                    return $this->redirectToRoute('app_agents_group_new');
                }
                if (!$agentsGroup->validateLeader()) {
                    $this->addFlash('error', 'Le chef du groupe doit être parmi les membres sélectionnés.');
                    return $this->redirectToRoute('app_agents_group_new');
                }
                
                $agentsGroup->setCreatedAt(\DateTimeImmutable::createFromMutable(new DateTime()));
                 // Initialisez group_name avec une valeur temporaire
                $agentsGroup->setGroupName('Temp Name');
                // Persist the entity to get the ID
                $entityManager->persist($agentsGroup);
                $entityManager->flush();
                // Now generate the group name using the ID
                $agentsGroup->generateAgentsGroup();
                // Flush again to save the updated group name
                $entityManager->flush();
                $this->addFlash('success', "Le groupe a bien été crée");
    
                return $this->redirectToRoute('app_agents_group_index', [], Response::HTTP_SEE_OTHER);
            } else {
                $this->addFlash('error','Une erreur s\'est produite lors de la création du groupe.');
            }
        }

        return $this->render('agents_group/new.html.twig', [
            'agents_group' => $agentsGroup,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_agents_group_show', methods: ['GET'])]
    public function show(AgentsGroup $agentsGroup): Response
    {
        return $this->render('agents_group/show.html.twig', [
            'agents_group' => $agentsGroup,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_agents_group_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_EDIT', message: 'Vous n\'avez pas l\'accès.')]
    public function edit(Request $request, AgentsGroup $agentsGroup, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        // Vérifiez si l'utilisateur est le leader du groupe ou un administrateur
        if ($user != $agentsGroup->getLeader() && !$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('info', 'La modification est autorisée seulement au chef du groupe.');
            return $this->redirectToRoute('app_agents_group_index', [], Response::HTTP_SEE_OTHER);
        }

        $form = $this->createForm(AgentsGroupType::class, $agentsGroup);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if($form->isValid()) {
                if ($agentsGroup->getGroupMember()->isEmpty()) {
                    $this->addFlash('error', 'Vous devez sélectionner au moins un membre pour créer un groupe d\'agents.');
                    return $this->redirectToRoute('app_agents_group_edit', ['id'=> $agentsGroup->getId()], Response::HTTP_SEE_OTHER);
                }
                if (!$agentsGroup->validateLeader()) {
                    $this->addFlash('error', 'Le chef du groupe doit être parmi les membres sélectionnés.');
                    return $this->redirectToRoute('app_agents_group_edit', ['id'=> $agentsGroup->getId()], Response::HTTP_SEE_OTHER);
                }
                $agentsGroup->setUpdatedAt(\DateTimeImmutable::createFromMutable(new DateTime()));
                $agentsGroup->generateAgentsGroup();
                $entityManager->flush();
                $this->addFlash('success', "Le groupe a bien été modifié");
    
                return $this->redirectToRoute('app_agents_group_index', [], Response::HTTP_SEE_OTHER);

            } else {
                $this->addFlash('error','Une erreur s\'est produite lors de la modification du groupe.');
            }
        }

        return $this->render('agents_group/edit.html.twig', [
            'agents_group' => $agentsGroup,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_agents_group_delete', methods: ['POST'])]
    #[IsGranted('ROLE_DELETE', message: 'Vous n\'avez pas l\'accès.')]
    public function delete(Request $request, AgentsGroup $agentsGroup, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$agentsGroup->getId(), $request->getPayload()->get('_token'))) {
            $entityManager->remove($agentsGroup);
            $entityManager->flush();
            $this->addFlash('success', "Le groupe a bien été supprimée");
        } else {
            $this->addFlash('error','Une erreur s\'est produite lors de la suppression du groupe');
        }

        return $this->redirectToRoute('app_agents_group_index', [], Response::HTTP_SEE_OTHER);
    }
}
