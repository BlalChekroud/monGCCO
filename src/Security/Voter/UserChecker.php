<?php
namespace App\Security;

use App\Entity\User;
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
        if ($user->getUserStatus()->getLabel() !== 'Actif') {
            throw new CustomUserMessageAuthenticationException(
                $this->translator->trans('inactive_account_cannot_log_in')
            );
        }
    }
    public function checkPostAuth(UserInterface $user): void
    {
        $this->checkPreAuth($user);
    }
}