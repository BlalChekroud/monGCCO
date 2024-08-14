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

    /**
     * @param User $user
     * @return CountingCampaign[]
     */
    public function findByUser(User $user): array
    {
        $qb = $this->createQueryBuilder('c');

        // Joindre les groupes d'agents et les membres des groupes
        $qb->leftJoin('c.agentsGroups', 'g')
           ->leftJoin('g.groupMember', 'm')
           ->where('c.createdBy = :user')
           ->orWhere('m = :user')
           ->setParameter('user', $user);

        return $qb->getQuery()->getResult();
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
