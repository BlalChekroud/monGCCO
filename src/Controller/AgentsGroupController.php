<?php

namespace App\Controller;

use App\Service\NotificationService;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Entity\AgentsGroup;
use App\Form\AgentsGroupType;
use App\Repository\AgentsGroupRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/user/agents/group')]
class AgentsGroupController extends AbstractController
{
    private $notificationService;
    public function __construct(
        NotificationService $notificationService,
        TranslatorInterface $translator,
    ) {
        $this->notificationService = $notificationService;
        $this->translator = $translator;
    }

    // public function __construct(NotificationService $notificationService)
    // {
    //     $this->notificationService = $notificationService;
    // }
    
    // Méthode pour ajouter une notification flash
    // private function addUserNotification($action, $user, $group, TranslatorInterface $translator)
    // {
    //     $messageKey = match ($action) {
    //         'add' => 'agentsGroup.notification.added',
    //         'edit' => 'agentsGroup.notification.edited',
    //         'delete' => 'agentsGroup.notification.deleted',
    //         default => null
    //     };

    //     if ($messageKey) {
    //         $parameters = [
    //             '%user%' => $user->getEmail(),
    //             '%group%' => $group->getGroupName(),
    //         ];

    //         // Utilisation du service de notification pour créer une notification
    //         $this->notificationService->createNotification($messageKey, $parameters, $this->getUser(), $user);
    //     }
    // }

    #[IsGranted('ROLE_VIEW', message: 'Vous n\'avez pas l\'accès.')]
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

    #[IsGranted('ROLE_CREAT', message: 'Vous n\'avez pas l\'accès.')]
    #[Route('/new', name: 'app_agents_group_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, TranslatorInterface $translator): Response
    {
        $agentsGroup = new AgentsGroup();
        $form = $this->createForm(AgentsGroupType::class, $agentsGroup);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                if ($agentsGroup->getGroupMember()->isEmpty()) {
                    $this->addFlash('error', $translator->trans('agentsGroup.error.no_member_selected'));
                    return $this->redirectToRoute('app_agents_group_new');
                }
                if (!$agentsGroup->validateLeader()) {
                    $this->addFlash('error', $translator->trans('agentsGroup.error.leader_not_in_members'));
                    return $this->redirectToRoute('app_agents_group_new');
                }
                
                $agentsGroup->setCreatedAt(new \DateTimeImmutable());
                $agentsGroup->setCreatedBy($this->getUser());
                 // Initialisez group_name avec une valeur temporaire
                $agentsGroup->setGroupName('Temp Name');
                // Persist the entity to get the ID
                $entityManager->persist($agentsGroup);
                $entityManager->flush();
                // Now generate the group name using the ID
                $agentsGroup->generateAgentsGroup();
                // Flush again to save the updated group name
                $entityManager->flush();
                $this->addFlash('success', $translator->trans("agentsGroup.msg.created"));
                
                // Envoyer une notification pour chaque utilisateur ajouté dans le groupe
                // foreach ($agentsGroup->getGroupMember() as $member) {
                //     $this->addUserNotification('add', $member, $agentsGroup, $translator);
                // }
                // Exemple dans la méthode new() du contrôleur
                foreach ($agentsGroup->getGroupMember() as $member) {
                    $this->notificationService->sendNotification($member, 'add', $this->getUser(), $agentsGroup);
                }
    
                return $this->redirectToRoute('app_agents_group_index', [], Response::HTTP_SEE_OTHER);
            } else {
                $this->addFlash('error', $translator->trans('agentsGroup.error.creation_failed'));
            }
        }

        return $this->render('agents_group/new.html.twig', [
            'agents_group' => $agentsGroup,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_agents_group_show', methods: ['GET'])]
    public function show(AgentsGroup $agentsGroup, TranslatorInterface $translator): Response
    {
        $user = $this->getUser();

        // Vérifiez si l'utilisateur est admin, créateur ou membre du groupe
        if ($this->isGranted('ROLE_ADMIN') || $this->isGranted('ROLE_VIEW') || $agentsGroup->getCreatedBy() === $user || $agentsGroup->getLeader() === $user || $agentsGroup->getGroupMember()->contains($user)) {
            return $this->render('agents_group/show.html.twig', [
                'agents_group' => $agentsGroup,
            ]);
            
        } else {
            $this->addFlash('info', $translator->trans('agentsGroup.msg.no_access_to_group'));
            return $this->redirectToRoute('app_agents_group_index');
        }
    }

    #[IsGranted('ROLE_EDIT', message: 'Vous n\'avez pas l\'accès.')]
    #[Route('/{id}/edit', name: 'app_agents_group_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, AgentsGroup $agentsGroup, EntityManagerInterface $entityManager, TranslatorInterface $translator): Response
    {
        $user = $this->getUser();
        // Vérifiez si l'utilisateur est le leader du groupe ou un administrateur
        if ($user !== $agentsGroup->getLeader() && !$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('info', $translator->trans('agentsGroup.msg.no_edit_permission'));
            return $this->redirectToRoute('app_agents_group_index', [], Response::HTTP_SEE_OTHER);
        }

        // Récupérer les membres actuels avant modification
        $originalMembers = new ArrayCollection($agentsGroup->getGroupMember()->toArray());

        $form = $this->createForm(AgentsGroupType::class, $agentsGroup);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if($form->isValid()) {
                if ($agentsGroup->getGroupMember()->isEmpty()) {
                    $this->addFlash('error', $translator->trans('agentsGroup.error.no_member_selected'));
                    return $this->redirectToRoute('app_agents_group_edit', ['id'=> $agentsGroup->getId()], Response::HTTP_SEE_OTHER);
                }
                if (!$agentsGroup->validateLeader()) {
                    $this->addFlash('error', $translator->trans('agentsGroup.error.leader_not_in_members'));
                    return $this->redirectToRoute('app_agents_group_edit', ['id'=> $agentsGroup->getId()], Response::HTTP_SEE_OTHER);
                }
                
                // Vérifier les utilisateurs supprimés
                // foreach ($originalMembers as $member) {
                //     if (!$agentsGroup->getGroupMember()->contains($member)) {
                //         // Notification pour l'utilisateur supprimé
                //         $this->notificationService->createNotification('delete', [
                //             '%user%' => $member->getEmail(),
                //             '%group%' => $agentsGroup->getGroupName()
                //         ], $this->getUser(), $member);
                //     }
                // }
                foreach ($originalMembers as $member) {
                    if (!$agentsGroup->getGroupMember()->contains($member)) {
                        $this->notificationService->sendNotification($member, 'remove', $this->getUser(), $agentsGroup);
                    }
                }

                $agentsGroup->setUpdatedAt(new \DateTimeImmutable());
                $agentsGroup->generateAgentsGroup();
                $entityManager->flush();
                $this->addFlash('success', $translator->trans('agentsGroup.msg.updated'));
    
                // Notification pour les membres modifiés
                foreach ($agentsGroup->getGroupMember() as $member) {
                    $this->notificationService->sendNotification($member, 'edit', $this->getUser(), $agentsGroup);
                }
                
                return $this->redirectToRoute('app_agents_group_show', ['id'=> $agentsGroup->getId()], Response::HTTP_SEE_OTHER);
            } else {
                $this->addFlash('error', $translator->trans('agentsGroup.error.modification_failed'));
            }
        }

        return $this->render('agents_group/edit.html.twig', [
            'agents_group' => $agentsGroup,
            'form' => $form,
        ]);
    }

    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas l\'accès.')]
    #[Route('/{id}', name: 'app_agents_group_delete', methods: ['POST'])]
    public function delete(Request $request, AgentsGroup $agentsGroup, EntityManagerInterface $entityManager, TranslatorInterface $translator): Response
    {
        try {
            if ($this->isCsrfTokenValid('delete'.$agentsGroup->getId(), $request->getPayload()->get('_token'))) {
                
                foreach ($agentsGroup->getGroupMember() as $member) {
                    $this->notificationService->sendNotification($member, 'delete', $this->getUser(), $agentsGroup);
                }
    
                $entityManager->remove($agentsGroup);
                $entityManager->flush();
                $this->addFlash('success', $translator->trans('agentsGroup.msg.deleted'));
            } else {
                $this->addFlash('error', $translator->trans('agentsGroup.error.deletion_failed'));
            }
        } catch (\Exception $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('app_agents_group_index', [], Response::HTTP_SEE_OTHER);
    }
}
