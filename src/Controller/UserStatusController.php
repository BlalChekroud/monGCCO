<?php

namespace App\Controller;

use App\Entity\UserStatus;
use App\Form\UserStatusType;
use App\Repository\UserStatusRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/super-admin/user/status')]
class UserStatusController extends AbstractController
{
    #[Route('/', name: 'app_user_status_index', methods: ['GET'])]
    public function index(UserStatusRepository $userStatusRepository): Response
    {
        return $this->render('user_status/index.html.twig', [
            'user_statuses' => $userStatusRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_user_status_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, TranslatorInterface $translator): Response
    {
        $userStatus = new UserStatus();
        $form = $this->createForm(UserStatusType::class, $userStatus);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $userStatus->setCreatedAt(new \DateTimeImmutable());
                $entityManager->persist($userStatus);
                $entityManager->flush();
                $this->addFlash('success', $translator->trans("userStatus.msg.created"));
                return $this->redirectToRoute('app_user_status_index', [], Response::HTTP_SEE_OTHER);
            } else {
                $this->addFlash('error', $translator->trans('userStatus.error.creation_failed'));
            }
        }

        return $this->render('user_status/new.html.twig', [
            'user_status' => $userStatus,
            'form' => $form,
        ]);
    }


    #[Route('/{id}/edit', name: 'app_user_status_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, UserStatus $userStatus, EntityManagerInterface $entityManager, TranslatorInterface $translator): Response
    {
        $form = $this->createForm(UserStatusType::class, $userStatus);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {

                $userStatus->setUpdatedAt(new \DateTimeImmutable());
                $entityManager->flush();
                $this->addFlash('success', $translator->trans('userStatus.msg.updated'));
    
                return $this->redirectToRoute('app_user_status_index', [], Response::HTTP_SEE_OTHER);
            } else {
                $this->addFlash('error', $translator->trans('userStatus.error.modification_failed'));
            }
        }

        return $this->render('user_status/edit.html.twig', [
            'user_status' => $userStatus,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_user_status_delete', methods: ['POST'])]
    public function delete(Request $request, UserStatus $userStatus, EntityManagerInterface $entityManager, TranslatorInterface $translator): Response
    {
        try {
            if (!$this->isGranted('ROLE_SUPER_ADMIN')) {
                $this->addFlash('error', $translator->trans('delete_permission'));
                return $this->redirectToRoute('app_user_status_index', [], Response::HTTP_SEE_OTHER);
            }
            if ($this->isCsrfTokenValid('delete'.$userStatus->getId(), $request->getPayload()->get('_token'))) {
                $entityManager->remove($userStatus);
                $entityManager->flush();
                $this->addFlash('success', $translator->trans('userStatus.msg.deleted'));
            } else {
                $this->addFlash('error', $translator->trans('userStatus.error.deletion_failed'));
            }
        } catch (\Exception $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('app_user_status_index', [], Response::HTTP_SEE_OTHER);
    }
}
