<?php

namespace App\EventListener;

use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Translation\LocaleSwitcher;
use App\Entity\User;

final class UserLocaleListener
{
    public function __construct(
        private readonly Security $security,
        private readonly LocaleSwitcher $localeSwitcher,
        private readonly RouterInterface $router,
    ) {}

    #[AsEventListener(event: KernelEvents::REQUEST)]
    public function onKernelRequest(RequestEvent $event): void
    {
        $user = $this->security->getUser();
    
        if ($user instanceof User) {    
            if ($user->getUserStatus()->getLabel() !== 'Actif') {
                // Déconnexion de l'utilisateur
                // $this->security->logout();
    
                // Génération de l'URL de la route app_logout
                $logoutUrl = $this->router->generate('app_logout');
    
                // Redirection vers la page de déconnexion
                $response = new RedirectResponse($logoutUrl);
                $event->setResponse($response);
                return;
            }
    
            // Si l'utilisateur est connecté, appliquez sa locale
            $language = $user->getLanguage();
    
            if ($language !== null) {
                $this->localeSwitcher->setLocale($language->getIso2());
            }
        } else {
            // Pour les utilisateurs non connectés
            $locale = $event->getRequest()->getSession()->get('_locale', 'fr');
            $this->localeSwitcher->setLocale($locale);
        }
    }
    
}
