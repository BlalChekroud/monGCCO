<?php

namespace App\Repository;

use App\Entity\AgentsGroup;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AgentsGroup>
 */
class AgentsGroupRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AgentsGroup::class);
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
