<?php

namespace App\Repository;

use App\Entity\Notification;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Notification>
 */
class NotificationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Notification::class);
    }
    
    /**
     * Récupère les notifications pour un utilisateur spécifique.
     *
     * @param int $userId L'ID de l'utilisateur.
     * @return Notification[] Un tableau de notifications.
     */
    public function findByUser(int $userId): array
    {
        return $this->createQueryBuilder('n')
            ->andWhere('n.user = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('n.createdAt', 'DESC')  // Trier par date de création (décroissante)
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les notifications non lues pour un utilisateur spécifique.
     *
     * @param int $userId L'ID de l'utilisateur.
     * @return int Le nombre de notifications non lues.
     */
    public function countUnreadNotifications(int $userId): int
    {
        return $this->createQueryBuilder('n')
            ->select('COUNT(n.id)')
            ->andWhere('n.user = :userId')
            ->andWhere('n.seen = :seen')
            ->setParameter('userId', $userId)
            ->setParameter('seen', false)  // Cherche uniquement les notifications non lues
            ->getQuery()
            ->getSingleScalarResult();  // Retourne un seul résultat (le nombre)
    }
    
    //    /**
    //     * @return Notification[] Returns an array of Notification objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('n')
    //            ->andWhere('n.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('n.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Notification
    //    {
    //        return $this->createQueryBuilder('n')
    //            ->andWhere('n.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
