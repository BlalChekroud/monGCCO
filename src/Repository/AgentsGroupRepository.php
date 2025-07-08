<?php

namespace App\Repository;

use App\Entity\AgentsGroup;
use App\Entity\CountingCampaign;
use App\Entity\NatureReserve;
use App\Entity\SiteAgentsGroup;
use App\Entity\SiteCollection;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * @extends ServiceEntityRepository<AgentsGroup>
 */
class AgentsGroupRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, private readonly Security $security)
    {
        parent::__construct($registry, AgentsGroup::class);
    }

    // Nombre total d'agents participants à une campagne de comptage
    public function countAgentsByCountingCampaign(CountingCampaign $countingCampaign): int
    {
        return (int) $this->createQueryBuilder('g') // 'g' pour AgentsGroup
            ->select('COUNT(DISTINCT m.id)') // Compter les ID uniques des membres
            ->join('g.groupMember', 'm') // Joindre les membres du groupe
            ->join('g.siteAgentsGroups', 's') // Joindre les sites associés
            ->where('s.countingCampaign = :campaign') // Filtrer par la campagne de comptage
            ->setParameter('campaign', $countingCampaign)
            ->getQuery()
            ->getSingleScalarResult(); // Récupérer le résultat sous forme scalaire
    }

    
    // Nombre total d'agents participants à une campagne de comptage dans une reserve
    public function countAgentsInRsvByCountingCampaign(NatureReserve $natureReserve, CountingCampaign $countingCampaign): int
    {
        return (int) $this->createQueryBuilder('g') // 'g' pour AgentsGroup
            ->select('COUNT(DISTINCT m.id)') // Compter les ID uniques des membres
            ->join('g.groupMember', 'm') // Joindre les membres du groupe
            ->join('g.siteAgentsGroups', 'sag') // Joindre les sites associés
            ->join('sag.siteCollection', 'sc')
            ->where('sag.countingCampaign = :campaign') // Filtrer par la campagne de comptage
            ->andWhere('sc.natureReserve = :reserve')
            ->setParameter('campaign', $countingCampaign)
            ->setParameter('reserve', $natureReserve)
            ->getQuery()
            ->getSingleScalarResult(); // Récupérer le résultat sous forme scalaire
    }
    
    public function findByUser($user)
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @return AgentsGroup[] Returns an array of AgentsGroup objects
     */
    public function findByUserMember(User $user)
    {
        return $this->createQueryBuilder('g')
            ->leftJoin('g.groupMember', 'm') // Joindre les membres du groupe
            ->where('m = :user')             // Vérifier si l'utilisateur est membre
            ->orWhere('g.leader = :user')    // Ou si l'utilisateur est leader
            ->orWhere('g.createdBy = :user') // Ou si l'utilisateur est créateur
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();
    }

    /**
     * Vérifie si l'utilisateur courant est membre d'un groupe dans une campagne donnée
     */
    public function userIsInGroup(CountingCampaign $countingCampaign): bool
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            return false;
        }

        $qb = $this->createQueryBuilder('g')
            ->select('COUNT(g.id)')
            ->join('g.groupMember', 'm')
            ->join('g.siteAgentsGroups', 'sag')
            ->where('sag.countingCampaign = :campaign')
            ->andWhere('m = :user')
            ->setParameter('campaign', $countingCampaign)
            ->setParameter('user', $user);

        return (int)$qb->getQuery()->getSingleScalarResult() > 0;
    }
    
    /**
     * Retourne les groupes d'agents liés à une campagne et un site donnés.
     *
     * @param int $campaignId
     * @param int $siteId
     * @return array
     */
    public function getAgentGroupsByCampaignAndSite(int $campaignId, int $siteId): array
    {
        return $this->createQueryBuilder('sag')
            ->select('g', 'm', 'l') // g = group, m = members, l = leader
            ->join('sag.agentsGroup', 'g')
            ->leftJoin('g.groupMember', 'm')
            ->leftJoin('g.leader', 'l')
            ->where('sag.countingCampaign = :campaign')
            ->andWhere('sag.siteCollection = :site')
            ->setParameter('campaign', $campaignId)
            ->setParameter('site', $siteId)
            ->getQuery()
            ->getResult();
    }
    
    /**
     * Trouve les groupes d'agents associés à une campagne et un site spécifiques
     * 
     * @param CountingCampaign $campaign
     * @param SiteCollection $site
     * @return array
     */
    public function findGroupsByCampaignAndSite(CountingCampaign $campaign, SiteCollection $site)
    {
        return $this->createQueryBuilder('ag')
            ->select('ag', 'sag', 'l', 'gm')
            ->join('ag.siteAgentsGroups', 'sag')
            ->leftJoin('ag.leader', 'l')
            ->leftJoin('ag.groupMember', 'gm')
            ->where('sag.countingCampaign = :campaign')
            ->andWhere('sag.siteCollection = :site')
            ->setParameter('campaign', $campaign)
            ->setParameter('site', $site)
            ->getQuery()
            ->getResult();
    }
    //    /**
    //     * @return AgentsGroup[] Returns an array of AgentsGroup objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('a')
    //            ->andWhere('a.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('a.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?AgentsGroup
    //    {
    //        return $this->createQueryBuilder('a')
    //            ->andWhere('a.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
