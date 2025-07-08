<?php
namespace App\Security\Voter;

use App\Entity\User;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class UserChecker implements UserCheckerInterface
{
    private TranslatorInterface $translator;
    public function __construct(
        TranslatorInterface $translator,
    ) {
        $this->translator = $translator;
    }
    public function checkPreAuth(UserInterface $user): void
    {
        if (!$user instanceof User) {
            return;
        }

        $userStatus = $user->getUserStatus(); // Récupérer le statut

        if (!$userStatus || $userStatus->getLabel() !== 'Actif') {
            throw new CustomUserMessageAuthenticationException(
                $this->translator->trans('inactive_account_cannot_log_in')
            );
        }
        // Si l'email n'est pas vérifié, bloquer la connexion
        // if ((!$user->isVerified() || !$user->isVerified() == null) & $user->getEmail() != 'gccom@gmail.com') {
        //     throw new CustomUserMessageAccountStatusException($this->translator->trans('user.account_not_verified'));
        // }
    }
    public function checkPostAuth(UserInterface $user): void
    {
        $this->checkPreAuth($user);
    }
}