<?php

namespace App\Controller;

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
    public function index(): Response
    {
        // Récupérez toutes les notifications (ou les messages flash)
        $session = $this->requestStack->getSession();
        $notifications = $session->getFlashBag()->all();

        return $this->render('notification/index.html.twig', [
            'notifications' => $notifications,
        ]);
    }
}
