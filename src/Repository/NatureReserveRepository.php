<?php

namespace App\Repository;

use App\Entity\NatureReserve;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<NatureReserve>
 */
class NatureReserveRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, NatureReserve::class);
    }

    // public function getCampaignBySiteOfReserve(NatureReserve $natureReserve): ?array
    // {
    //     $qb = $this->createQueryBuilder('nr') // Créer une requête sur NatureReserve
    //         ->join('nr.siteCollections', 'sc') // Join NatureReserve à SiteCollection
    //         ->join('sc.environmentalConditions', 'ec')  // Join CollectedData à EnvironmentalConditions
    //         ->join('ec.countingCampaign', 'cc') // Join SiteCollection à CountingCampaign
    //         ->select('cc.id, cc.campaignName as campaignName') // Sélectionner l'id et le nom de la campagne
    //         ->where('nr = :natureReserve')
    //         ->setParameter('natureReserve', $natureReserve);

    //     return $qb->getQuery()->getResult(); // Retourne un tableau avec les données de la campagne
    // }
    public function getCampaignBySiteOfReserve(NatureReserve $natureReserve): array
    {
        return $this->createQueryBuilder('nr')
            ->select('DISTINCT cc.id AS campaignId, cc.campaignName AS campaignName')
            ->join('nr.siteCollections', 'sc')
            ->join('sc.environmentalConditions', 'ec')
            ->join('sc.siteAgentsGroups', 'sag') // Jointure avec SiteAgentsGroup
            ->join('sag.countingCampaign', 'cc') // Associer directement countingCampaign via siteAgentsGroups
            ->where('nr = :natureReserve')
            ->setParameter('natureReserve', $natureReserve)
            ->orderBy('cc.startDate', 'DESC') // Trier par date de début
            ->getQuery()
            ->getResult();
    }
    

    //    /**
    //     * @return NatureReserve[] Returns an array of NatureReserve objects
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

    //    public function findOneBySomeField($value): ?NatureReserve
    //    {
    //        return $this->createQueryBuilder('n')
    //            ->andWhere('n.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
