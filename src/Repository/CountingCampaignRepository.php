<?php

namespace App\Repository;

use App\Entity\CountingCampaign;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CountingCampaign>
 */
class CountingCampaignRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CountingCampaign::class);
    }

    // public function findByUser($user)
    // {
    //     return $this->createQueryBuilder('c')
    //         ->join('c.agentsGroups', 'g')
    //         ->join('g.groupMember', 'm')
    //         ->where('m = :user')
    //         ->setParameter('user', $user)
    //         ->getQuery()
    //         ->getResult();
    // }

    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.siteAgentsGroups', 'sag') // Jointure avec les SiteAgentsGroups (ou agentsGroups si relation directe)
            ->leftJoin('sag.agentsGroup', 'g') // Jointure avec les groupes d'agents
            ->leftJoin('g.groupMember', 'm') // Jointure avec les membres des groupes
            ->where('c.createdBy = :user') // Soit l'utilisateur est le créateur de la campagne
            ->orWhere('m = :user') // Soit l'utilisateur est un membre d'un groupe assigné
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();
    }
    

    //    /**
    //     * @return CountingCampaign[] Returns an array of CountingCampaign objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('c.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?CountingCampaign
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
