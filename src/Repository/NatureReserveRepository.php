<?php

namespace App\Repository;

use App\Entity\CountingCampaign;
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

    public function getCampaignBySiteOfReserve(NatureReserve $natureReserve): array
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->select('DISTINCT cc')
            ->from('App\Entity\CountingCampaign', 'cc')
            ->join('cc.siteAgentsGroups', 'sag')
            ->join('sag.siteCollection', 'sc')
            ->join('sc.natureReserve', 'nr')
            ->where('nr = :natureReserve')
            ->setParameter('natureReserve', $natureReserve)
            ->orderBy('cc.startDate', 'ASC')
            ->getQuery()
            ->getResult();
    }
    
    /**
     * Retourne le nombre total pour chaque espèce dans une réserve naturelle pour une campagne.
     *
     * @param int $reserveId  L’ID de la réserve
     * @param int $campaignId L’ID de la campagne
     * @return array
     */
    public function getTotalCountBySpeciesForReserve(int $reserveId, int $ccampaignId): array
    {
        return $this->createQueryBuilder('nr')
            ->select(
                'bs.scientificName AS species',
                'SUM(bsc.count)      AS totalCount',
                'cc.id AS campaignId'
            )
            // Les sites de la réserve
            ->innerJoin('nr.siteCollections',    'sc')
            // Les données collectées sur ces sites
            ->innerJoin('sc.collectedData',      'cd')
            // On ne prend que celles de la campagne ciblée
            ->innerJoin('cd.countingCampaign',   'cc')
            // Les comptages d’espèces dans chaque collecte
            ->innerJoin('cd.birdSpeciesCounts',  'bsc')
            // L’espèce elle-même
            ->innerJoin('bsc.birdSpecies',       'bs')
            // Conditions sur la réserve et sur la campagne
            ->where('nr.id = :reserveId')
            ->andWhere('cc.id = :ccampaignId')
            ->setParameter('reserveId',  $reserveId)
            ->setParameter('ccampaignId', $ccampaignId)
            // Agrégation par espèce
            ->groupBy('bs.id')
            // (optionnel) tri par nom scientifique
            ->orderBy('bsc.count', 'DESC')
            ->getQuery()
            ->getScalarResult();
    }

    // Le nombre total de collectes associées à une campagne de comptage
    public function countCollectedDataByReserve(NatureReserve $natureReserve, CountingCampaign $campaign): int
    {
        return (int) $this->createQueryBuilder('nr')
            ->select('COUNT(cd.id)')
            ->join('nr.siteCollections', 'sc')
            ->join('sc.environmentalConditions', 'ec')
            ->join('ec.countingCampaign', 'cc')
            ->join('ec.collectedData', 'cd')
            ->where('nr = :reserve')
            ->andWhere('cc = :campaign')
            ->setParameter('reserve', $natureReserve)
            ->setParameter('campaign', $campaign)
            ->getQuery()
            ->getSingleScalarResult();
    }    
    

    // Récupère toutes les méthodes de collecte utilisées dans la reserve pour une campagne
    public function getMethodsUsedInReserveByCampaign(NatureReserve $natureReserve, CountingCampaign $campaign): array
    {
        $result = $this->createQueryBuilder('nr')
            ->select('DISTINCT m.label')
            ->join('nr.siteCollections', 'sc')
            ->join('sc.siteAgentsGroups', 'sag')
            ->join('sag.countingCampaign', 'cc')
            ->join('sc.collectedData', 'cd')
            ->join('cd.environmentalConditions', 'ec') // Ajout de ce lien pour filtrer avec la campagne
            ->join('cd.method', 'm')
            ->where('nr = :reserve')
            ->andWhere('cc = :campaign')
            ->andWhere('ec.countingCampaign = :campaign') // Renforce l'appartenance de cd à la campagne
            ->setParameter('reserve', $natureReserve)
            ->setParameter('campaign', $campaign)
            ->getQuery()
            ->getArrayResult();
    
        return array_column($result, 'label');
    }
    

    // Retourne les site de la reserve qui sont dans la campagne  
    public function getRsfSitesByCampaign(CountingCampaign $countingCampaign, NatureReserve $natureReserve): array
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->select('DISTINCT sc')
            ->from('App\Entity\SiteCollection', 'sc')
            ->innerJoin('sc.siteAgentsGroups', 'sag')
            ->innerJoin('sag.countingCampaign', 'cc')
            ->innerJoin('sc.natureReserve', 'nr')
            ->where('cc = :campaign')
            ->andWhere('nr = :natureReserve')
            ->setParameter('campaign', $countingCampaign)
            ->setParameter('natureReserve', $natureReserve)
            ->orderBy('sc.siteName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère la dernière campagne créée pour une réserve 
     */
    public function findMostRecentCampaignInRsf(NatureReserve $natureReserve): ?CountingCampaign
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->select('DISTINCT cc')
            ->from('App\Entity\CountingCampaign', 'cc')
            ->innerJoin('cc.siteAgentsGroups', 'sag')
            ->innerJoin('sag.siteCollection', 'sc')
            ->innerJoin('sc.natureReserve', 'nr')
            ->where('nr.id = :reserveId')
            ->setParameter('reserveId', $natureReserve->getId())
            ->orderBy('cc.createdAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
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
