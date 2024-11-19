<?php

namespace App\Service;

use App\Entity\Notification;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class NotificationService
{
    private EntityManagerInterface $entityManager;
    private TranslatorInterface $translator;

    public function __construct(EntityManagerInterface $entityManager, TranslatorInterface $translator)
    {
        $this->entityManager = $entityManager;
        $this->translator = $translator;
    }

    public function sendNotification(User $recipient, string $action, User $creator, $group)
    {
        // Créez le message traduit
        $message = match ($action) {
            'add' => $this->translator->trans('agentsGroup.notification.added', [
                '%group%' => $group->getGroupName(),
                '%user%' => $creator->getEmail(),
            ]),
            'edit' => $this->translator->trans('agentsGroup.notification.edited', [
                '%group%' => $group->getGroupName(),
                '%user%' => $creator->getEmail(),
            ]),
            'delete' => $this->translator->trans('agentsGroup.notification.deleted', [
                '%group%' => $group->getGroupName(),
                '%user%' => $creator->getEmail(),
            ]),
            'remove' => $this->translator->trans('agentsGroup.notification.removed', [
                '%group%' => $group->getGroupName(),
                '%user%' => $creator->getEmail(),
            ]),
            default => null
        };

        if ($message) {
            $notification = new Notification();
            $notification->setMessage($message);
            $notification->setUser($recipient);
            $notification->setSeen(false);
            $notification->setCreatedAt(new \DateTimeImmutable());

            $this->entityManager->persist($notification);
            $this->entityManager->flush();
        }
    }
}
