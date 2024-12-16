<?php

namespace App\Service\EventSubscriber;
// namespace App\EventSubscriber;

use App\Repository\LanguageRepository;
use App\Repository\LogoRepository;
use App\Repository\NotificationRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Twig\Environment;

class LogoSubscriber implements EventSubscriberInterface
{
    private $twig;
    private $logoRepository;
    private $notificationRepository;
    private $security;
    private $languageRepository;

    public function __construct(Environment $twig, LogoRepository $logoRepository, NotificationRepository $notificationRepository, Security $security, LanguageRepository $languageRepository)
    {
        $this->twig = $twig;
        $this->logoRepository = $logoRepository;
        $this->notificationRepository = $notificationRepository;
        $this->security = $security;
        $this->languageRepository = $languageRepository;
    }

    public function onKernelController(ControllerEvent $event)
    {
        $logo = $this->logoRepository->findOneBy([]); // Récupère le premier logo
        $this->twig->addGlobal('logo', $logo); // Injecte la variable globale
        
        $languages = $this->languageRepository->findAll();
        $this->twig->addGlobal('languages', $languages);
        $user = $this->security->getUser();
        // Vérifier si l'utilisateur est authentifié
        if ($user) {
            // Obtenir le nombre de notifications non lues
            $unreadNotifications = $this->notificationRepository->countUnreadNotifications($user->getId());

            // Rendre la variable globale dans Twig
            $this->twig->addGlobal('unreadNotifications', $unreadNotifications); // Injecte la variable globale
        }

    }

    public static function getSubscribedEvents(): array
    {
        return [
            'kernel.controller' => 'onKernelController',
        ];
    }
}