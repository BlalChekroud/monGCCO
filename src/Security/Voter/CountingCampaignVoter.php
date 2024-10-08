<?php

namespace App\Security\Voter;

use App\Entity\CountingCampaign;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class CountingCampaignVoter extends Voter
{
    public const EDIT = 'ROLE_EDIT';
    public const VIEW = 'ROLE_VIEW';
    public const CREATE = 'ROLE_SUPER_CREAT';
    public const DELETE = 'ROLE_SUPER_ADMIN';
    public const IMPORT = 'ROLE_IMPORT';

    protected function supports(string $attribute, mixed $subject): bool
    {
        // Prend en charge EDIT, VIEW, CREATE, DELETE, IMPORT pour une campagne de comptage
        return in_array($attribute, [self::EDIT, self::VIEW, self::CREATE, self::DELETE, self::IMPORT])
            && $subject instanceof CountingCampaign;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        // Si l'utilisateur n'est pas connecté, accès refusé
        if (!$user instanceof User) {
            return false;
        }

        /** @var CountingCampaign $campaign */
        $campaign = $subject;

        switch ($attribute) {
            case self::VIEW:
                return $this->canView($campaign, $user);
            case self::EDIT:
                return $this->canEdit($campaign, $user);
            case self::CREATE:
                return $this->canCreate($campaign, $user);
            case self::DELETE:
                return $this->canDelete($campaign, $user);
            case self::IMPORT:
                return $this->canImport($campaign, $user);
        }

        return false;
    }

    private function canView(CountingCampaign $campaign, User $user): bool
    {
        // L'administrateur peut tout voir
        if (in_array('ROLE_ADMIN', $user->getRoles())) {
            return true;
        }
    
        // L'utilisateur peut voir la campagne s'il est le créateur
        if ($campaign->getCreatedBy() === $user) {
            return true;
        }
    
        // L'utilisateur peut voir la campagne s'il est membre de l'un des groupes associés
        foreach ($campaign->getSiteAgentsGroups() as $siteAgentsGroup) {
            // Parcourir chaque AgentsGroup associé au SiteAgentsGroup
            foreach ($siteAgentsGroup->getAgentsGroup() as $agentsGroup) {
                // Si l'utilisateur est membre de cet AgentsGroup
                if ($agentsGroup->getGroupMember()->contains($user)) {
                    return true;
                }
            }
        }
    
        return false;
    }

    private function canEdit(CountingCampaign $campaign, User $user): bool
    {
        // L'administrateur peut tout modifier
        if (in_array('ROLE_ADMIN', $user->getRoles())) {
            return true;
        }

        // // L'utilisateur peut modifier la campagne s'il en est le créateur
        // return $campaign->getCreatedBy() === $user;
        
        // // Le leader d'un groupe peut modifier la campagne si son groupe est associé à la campagne
        // if ($this->isLeaderOfAssociatedGroup($campaign, $user)) {
        //     return true;
        // }

        return false;
    }

    private function canCreate(CountingCampaign $campaign, User $user): bool
    {
        // L'administrateur et les créateurs peuvent créer des campagnes
        return in_array('ROLE_ADMIN', $user->getRoles());
    }

    private function canDelete(CountingCampaign $campaign, User $user): bool
    {
        // L'administrateur peut tout supprimer
        if (in_array('ROLE_SUPER_ADMIN', $user->getRoles())) {
            return true;
        }

        // // L'utilisateur peut supprimer la campagne s'il en est le créateur
        // return $campaign->getCreatedBy() === $user;
        return false;
    }

    private function canImport(CountingCampaign $campaign, User $user): bool
    {
        // Le super admin peut importer des données
        return in_array('ROLE_ADMIN', $user->getRoles());
    }

    // /**
    //  * Vérifie si l'utilisateur est le leader d'un des groupes associés à un SiteAgentsGroup d'une campagne.
    //  */
    // private function isLeaderOfAssociatedGroup(CountingCampaign $campaign, User $user): bool
    // {
    //     // Parcourir chaque SiteAgentsGroup associé à la campagne
    //     foreach ($campaign->getSiteAgentsGroups() as $siteAgentsGroup) {
    //         // Parcourir chaque AgentsGroup dans ce SiteAgentsGroup
    //         foreach ($siteAgentsGroup->getAgentsGroup() as $agentsGroup) {
    //             // Vérifier si l'utilisateur est le leader de l'un des AgentsGroup
    //             if ($agentsGroup->getLeader() === $user) {
    //                 return true;
    //             }
    //         }
    //     }

    //     return false; // Aucun groupe dans cette campagne n'a l'utilisateur comme leader
    // }

}
