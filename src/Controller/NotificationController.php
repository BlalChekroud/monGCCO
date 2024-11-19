<?php

namespace App\Controller;

use App\Entity\Notification;
use App\Repository\NotificationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[IsGranted('ROLE_USER', message: 'Vous n\'avez pas l\'accès.')]
class NotificationController extends AbstractController
{
    private $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    #[Route('/notifications', name: 'all_notifications')]
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


    #[Route('/notifications/{id}/mark-as-seen', name: 'notification_mark_as_seen')]
     public function markAsSeen(Notification $notification, EntityManagerInterface $entityManager): RedirectResponse
    {
        // Vérifiez que l'utilisateur est le propriétaire de la notification
        if ($notification->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException('You cannot mark this notification as seen.');
        }

        // Marquez la notification comme vue
        $notification->setSeen(true);

        // Enregistrez les changements en base de données
        $entityManager->persist($notification);
        $entityManager->flush();

        return $this->redirectToRoute('all_notifications');
    }

}
