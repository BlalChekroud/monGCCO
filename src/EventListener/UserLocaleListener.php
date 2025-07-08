<?php

namespace App\EventListener;

use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Translation\LocaleSwitcher;
use App\Entity\User;
use Symfony\Contracts\Translation\TranslatorInterface;

final class UserLocaleListener
{
    public function __construct(
        private readonly Security $security,
        private readonly LocaleSwitcher $localeSwitcher,
        private readonly RouterInterface $router,
        private readonly LoggerInterface $logger, // Optionnel
        private readonly TranslatorInterface $translator,
        private readonly string $defaultLocale = 'fr'
    ) {}

    #[AsEventListener(event: KernelEvents::REQUEST)]
    public function onKernelRequest(RequestEvent $event): void
    {
        $user = $this->security->getUser();
    
        if ($user instanceof User) {  
            if ($user->getUserStatus() === null || $user->getUserStatus()->getLabel() === null) {
                // Log or handle unexpected status
                return;
            }

            // Si l'email n'est pas vérifié, bloquer la connexion
            // if ((!$user->isVerified() || !$user->isVerified() == null) & $user->getEmail() != 'gccom@gmail.com') {
            //     throw new CustomUserMessageAccountStatusException($this->translator->trans('user.account_not_verified'));
            // }
              
            if ($user->getUserStatus()->getLabel() !== 'Actif') {
                // Déconnexion de l'utilisateur
                // $this->security->logout();
    
                // Génération de l'URL de la route app_logout
                $logoutUrl = $this->router->generate('app_logout');
    
                // Redirection vers la page de déconnexion
                $this->logger?->warning('User inactive, redirecting to logout.');
                $event->setResponse(new RedirectResponse($logoutUrl));
                return;
            }
    
            // Si l'utilisateur est connecté, appliquez sa locale
            $language = $user->getLanguage()?->getIso2();
    
            if ($language) {
                $this->logger?->info('Applying user locale: ' . $language);
                $this->localeSwitcher->setLocale($language);
            }
        } else {
            // Pour les utilisateurs non connectés
            $locale = $event->getRequest()->getSession()->get('_locale', $this->defaultLocale);
            $this->logger?->info('Applying default locale: ' . $locale);
            $this->localeSwitcher->setLocale($locale);
        }
    }
    
}
