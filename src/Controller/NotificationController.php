<?php

namespace App\Controller;

use App\Entity\Notification;
use App\Repository\NotificationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[IsGranted('ROLE_USER', message: 'Vous n\'avez pas l\'accès.')]
class NotificationController extends AbstractController
{
    // private $requestStack;

    // public function __construct(RequestStack $requestStack)
    // {
    //     $this->requestStack = $requestStack;
    // }
    public function __construct(private readonly TranslatorInterface $translator, 
                                private readonly RequestStack $requestStack
                                ) {}


    #[Route('/user/notifications', name: 'all_notifications')]
    public function index(NotificationRepository $notificationRepository): Response
    {
        $user = $this->getUser();
        $notifications = $notificationRepository->findByUser($user->getId());
        // $notifications = $notificationRepository->findAll();
        // $unreadNotifications = $notificationRepository->countUnreadNotifications($user->getId());
        
        // Récupérez toutes les notifications (ou les messages flash)
        // $session = $this->requestStack->getSession();
        // $notifSession = $session->getFlashBag()->all();
        return $this->render('notification/index.html.twig', [
            'notifications' => $notifications,
            // 'unreadNotifications' => $unreadNotifications,
            // 'notifSession' => $notifSession,
        ]);
    }


    // #[Route('/user/notifications/{id}/mark-as-seen', name: 'notification_mark_as_seen')]
    //  public function markAsSeen(Notification $notification, EntityManagerInterface $entityManager): RedirectResponse
    // {
    //     // Vérifiez que l'utilisateur est le propriétaire de la notification
    //     if ($notification->getUser() !== $this->getUser()) {
    //         throw $this->createAccessDeniedException('You cannot mark this notification as seen.');
    //     }

    //     // Marquez la notification comme vue
    //     $notification->setSeen(true);

    //     // Enregistrez les changements en base de données
    //     $entityManager->persist($notification);
    //     $entityManager->flush();
        

    //     return $this->redirectToRoute('all_notifications');
    // }
    #[Route('/user/notifications/{id}/mark-as-seen', name: 'notification_mark_as_seen', methods: ['POST'])]
    public function markAsSeen(Notification $notification, EntityManagerInterface $entityManager): JsonResponse
    {
        if ($notification->getUser() !== $this->getUser()) {
            return new JsonResponse(['error' => 'Access denied'], 403);
        }
    
        $notification->setSeen(true);
        $entityManager->flush();
    
        return new JsonResponse([
            'success' => true,
            'id' => $notification->getId()
        ]);
    }


    #[Route('/user/notifications/{id}/delete', name: 'notification_delete', methods: ['POST'])]
    public function delete(Request $request, Notification $notification, EntityManagerInterface $entityManager): Response
    {
        try {
            if ($this->isCsrfTokenValid('delete'.$notification->getId(), $request->getPayload()->get('_token'))) {
                $entityManager->remove($notification);
                $entityManager->flush();
                $this->addFlash('success', $this->translator->trans('notification.msg.deleted_success'));
            } else {
                $this->addFlash('error',$this->translator->trans('notification.msg.deleted_error'));
            } 
        } catch (\Exception $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('all_notifications', [], Response::HTTP_SEE_OTHER);
    }

}
