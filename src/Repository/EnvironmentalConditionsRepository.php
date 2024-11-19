<?php

namespace App\Repository;

use App\Entity\CountingCampaign;
use App\Entity\EnvironmentalConditions;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<EnvironmentalConditions>
 */
class EnvironmentalConditionsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EnvironmentalConditions::class);
    }
    
    /**
     * Récupérer les valeurs les plus fréquentes des conditions environnementales dans une campagne donnée.
     *
     * @param CountingCampaign $campaign
     * @return array
     */
    public function getMostFrequentEnvironmentalConditions(CountingCampaign $campaign): array
    {
        $result = [];

        // Valeur la plus fréquente pour 'disturbed'
        try {
            $result['disturbed'] = $this->createQueryBuilder('ec')
                ->select('d.label')
                ->join('ec.disturbed', 'd')
                ->where('ec.countingCampaign = :campaign')
                ->setParameter('campaign', $campaign)
                ->groupBy('d.id')
                ->orderBy('COUNT(d.id)', 'DESC')
                ->setMaxResults(1)
                ->getQuery()
                ->getSingleScalarResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            $result['disturbed'] = null;
        }

        // Valeur la plus fréquente pour 'ice'
        try {
            $result['ice'] = $this->createQueryBuilder('ec')
                ->select('i.label')
                ->join('ec.ice', 'i')
                ->where('ec.countingCampaign = :campaign')
                ->setParameter('campaign', $campaign)
                ->groupBy('i.id')
                ->orderBy('COUNT(i.id)', 'DESC')
                ->setMaxResults(1)
                ->getQuery()
                ->getSingleScalarResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            $result['ice'] = null;
        }

        // Valeur la plus fréquente pour 'tidal'
        try {
            $result['tidal'] = $this->createQueryBuilder('ec')
                ->select('t.label')
                ->join('ec.tidal', 't')
                ->where('ec.countingCampaign = :campaign')
                ->setParameter('campaign', $campaign)
                ->groupBy('t.id')
                ->orderBy('COUNT(t.id)', 'DESC')
                ->setMaxResults(1)
                ->getQuery()
                ->getSingleScalarResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            $result['tidal'] = null;
        }

        // Valeur la plus fréquente pour 'water'
        try {
            $result['water'] = $this->createQueryBuilder('ec')
                ->select('w.label')
                ->join('ec.water', 'w')
                ->where('ec.countingCampaign = :campaign')
                ->setParameter('campaign', $campaign)
                ->groupBy('w.id')
                ->orderBy('COUNT(w.id)', 'DESC')
                ->setMaxResults(1)
                ->getQuery()
                ->getSingleScalarResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            $result['water'] = null;
        }

        // Valeur la plus fréquente pour 'weather'
        try {
            $result['weather'] = $this->createQueryBuilder('ec')
                ->select('we.label')
                ->join('ec.weather', 'we')
                ->where('ec.countingCampaign = :campaign')
                ->setParameter('campaign', $campaign)
                ->groupBy('we.id')
                ->orderBy('COUNT(we.id)', 'DESC')
                ->setMaxResults(1)
                ->getQuery()
                ->getSingleScalarResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            $result['weather'] = null;
        }

        return $result;
    }


    //    /**
    //     * @return EnvironmentalConditions[] Returns an array of EnvironmentalConditions objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('e')
    //            ->andWhere('e.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('e.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?EnvironmentalConditions
    //    {
    //        return $this->createQueryBuilder('e')
    //            ->andWhere('e.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
