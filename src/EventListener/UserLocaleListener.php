<?php

namespace App\EventListener;

use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Translation\LocaleSwitcher;

final class UserLocaleListener
{

    public function __construct(
        private readonly Security $security,
        private readonly LocaleSwitcher $localeSwitcher
    ){

    }

    #[AsEventListener(event: KernelEvents::REQUEST)]
    public function onKernelRequest(RequestEvent $event): void
    {
        // Vérifie s'il y a un utilisateur authentifié
        $user = $this->security->getUser();
        if ($user && $user instanceof User) {
            // Récupère la locale de l'utilisateur et la définit dans la requête
            $this->localeSwitcher->setLocale($user->getLocale());
        }
    }
}
