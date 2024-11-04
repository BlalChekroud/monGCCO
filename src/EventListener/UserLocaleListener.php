<?php

namespace App\EventListener;

use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Translation\LocaleSwitcher;

final class UserLocaleListener
{
    // private $defaultLocale;

    public function __construct(
        private readonly Security $security,
        private readonly LocaleSwitcher $localeSwitcher,
    ){}

    // #[AsEventListener(event: KernelEvents::REQUEST)]
    // public function onKernelRequest(RequestEvent $event): void
    // {
    //     $request = $event->getRequest();
    //     $locale = $request->getSession()->get('_locale', 'en');  // 'en' comme langue par défaut

    //     // Appliquer la langue choisie par l'utilisateur
    //     $this->localeSwitcher->setLocale($locale);
    // }
    #[AsEventListener(event: KernelEvents::REQUEST)]
    public function onKernelRequest(RequestEvent $event): void
    {
        $user = $this->security->getUser();
        if ($user instanceof User) {
            // Si l'utilisateur est connecté, appliquez sa locale
            $this->localeSwitcher->setLocale($user->getLocale());
        } else {
            // Pour les utilisateurs non connectés, appliquez la locale de la session
            $locale = $event->getRequest()->getSession()->get('_locale', 'fr');
            $this->localeSwitcher->setLocale($locale);
        }
    }

}
