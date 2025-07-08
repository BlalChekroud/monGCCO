<?php

namespace App\Repository;

use App\Entity\CountingCampaign;
use App\Entity\SiteCollection;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SiteCollection>
 */
class SiteCollectionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SiteCollection::class);
    }

    /**
     * Récupère toutes les SiteCollections d'une campagne.
     *
     * @param CountingCampaign $campaign
     * @return SiteCollection[] Retourne un tableau d'objets SiteCollection
     */
    public function getSiteCollectionsByCampaign(CountingCampaign $campaign): array
    {
        return $this->createQueryBuilder('sc')
            ->join('sc.siteAgentsGroups', 'sag') // Jointure avec SiteAgentsGroup depuis SiteCollection
            ->join('sag.countingCampaign', 'cc') // Jointure avec CountingCampaign
            ->where('cc.id = :campaignId')
            ->setParameter('campaignId', $campaign->getId())
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère toutes les campagnes d'un SiteCollections.
     * utilisé dans reserve.
     * @param SiteCollection $site
     * @return CountingCampaign[] Retourne un tableau d'objets CountingCampains
     */
    public function getCampaignsBySiteCollection(SiteCollection $site): array
    {
        return $this->createQueryBuilder('sc')
            ->select('DISTINCT cc.id AS campaignId, cc.campaignName AS campaignName')
            ->join('sc.siteAgentsGroups', 'sag')
            ->join('sag.countingCampaign', 'cc')
            ->where('sc.id = :siteId')
            ->setParameter('siteId', $site->getId())
            ->getQuery()
            ->getResult();
    }
    

    /**
     * Récupère le nombre total de comptages d'oiseaux pour un site spécifique
     *
     * @param SiteCollection $siteCollection
     * @return int|null
     */
    public function getTotalBirdCountsForSite(SiteCollection $siteCollection): ?int
    {
        return $this->createQueryBuilder('s')
            ->select('SUM(DISTINCT bsc.count) AS totalBirdCounts') // Total des comptages d'oiseaux, en évitant les doublons
            ->join('s.siteAgentsGroups', 'sag') // Jointure avec SiteAgentsGroups
            ->join('s.collectedData', 'cd') // Jointure avec CollectedData
            ->join('cd.birdSpeciesCounts', 'bsc') // Jointure avec BirdSpeciesCount
            ->where('s.id = :siteId')
            ->setParameter('siteId', $siteCollection->getId())
            ->getQuery()
            ->getSingleScalarResult(); // Retourne le total en tant que valeur scalaire
    }

    public function getUniqueBirdSpeciesCountForSite(SiteCollection $siteCollection): ?int
    {
        return $this->createQueryBuilder('s')
            ->select('COUNT(DISTINCT bsc.birdSpecies) AS uniqueSpeciesCount') // Nombre d'espèces uniques
            ->join('s.siteAgentsGroups', 'sag') // Jointure avec SiteAgentsGroups
            ->join('s.collectedData', 'cd') // Jointure avec CollectedData
            ->join('cd.birdSpeciesCounts', 'bsc') // Jointure avec BirdSpeciesCount
            ->where('s.id = :siteId')
            ->setParameter('siteId', $siteCollection->getId())
            ->getQuery()
            ->getSingleScalarResult(); // Retourne le nombre d'espèces uniques
    }

    // Les espèces uniques pour le site et la campagne spécifiés.
    public function getUniqueBirdSpeciesCountForSiteInCampaign(SiteCollection $siteCollection, CountingCampaign $campaign): ?int
    {
        return $this->createQueryBuilder('s')
            ->select('COUNT(DISTINCT bsc.birdSpecies) AS uniqueSpeciesCount') // Nombre d'espèces uniques
            ->join('s.siteAgentsGroups', 'sag') // Jointure avec SiteAgentsGroups
            ->join('s.collectedData', 'cd') // Jointure avec CollectedData
            ->join('cd.birdSpeciesCounts', 'bsc') // Jointure avec BirdSpeciesCount
            ->where('s.id = :siteId')
            ->andWhere('cd.countingCampaign = :campaignId') // Filtrer par la campagne
            ->setParameter('siteId', $siteCollection->getId())
            ->setParameter('campaignId', $campaign->getId()) // Ajouter le filtre sur la campagne
            ->getQuery()
            ->getSingleScalarResult(); // Retourne le nombre d'espèces uniques
    }


    /**
     * Récupère le nombre total de comptages d'oiseaux pour un site spécifique dans une campagne de comptage
     *
     * @param SiteCollection $siteCollection
     * @param CountingCampaign $countingCampaign
     * @return int|null
     */
    public function getTotalBirdCountsForSiteInCampaign(SiteCollection $siteCollection, CountingCampaign $countingCampaign): ?int
    {
        $result = $this->createQueryBuilder('s')
            ->select('SUM(bsc.count) AS totalBirdCounts')
            ->leftJoin('s.collectedData', 'cd')
            ->leftJoin('cd.birdSpeciesCounts', 'bsc')
            ->leftJoin('cd.countingCampaign', 'cc')
            ->where('s.id = :siteId')
            ->andWhere('cc.id = :campaignId')
            ->setParameter('siteId', $siteCollection->getId())
            ->setParameter('campaignId', $countingCampaign->getId())
            ->getQuery()
            ->getOneOrNullResult(); // ✅ Retourne NULL au lieu d'une erreur
    
        return $result['totalBirdCounts'] ?? 0; // ✅ Retourne 0 si aucun résultat
    }
    

    public function getExportDataByCampaign(CountingCampaign $campaign): array
    {
        $qb = $this->createQueryBuilder('siteCollection')
            ->select(
                'campaign.campaignName AS Campaign',
                'siteCollection.siteName AS Site',
                'city.name AS City',
                'region.name AS Region',
                'createdBy.email AS Agent',
                'birdSpecies.scientificName AS Species',
                'speciesCount.count AS Count',
                'method.label AS Method',
                'DATE_FORMAT(collectedData.createdAt, \'%d-%m-%Y %H:%i:%s\') AS Date' // Formatage de la date
            )
            ->join('siteCollection.siteAgentsGroups', 'site') // Relation entre SiteCollection et Site
            ->join('site.countingCampaign', 'campaign') // Relation entre Site et CountingCampaign
            ->join('siteCollection.city', 'city') // Relation entre SiteCollection et City
            ->join('city.region', 'region') // Relation entre City et Region
            ->join('siteCollection.collectedData', 'collectedData') // Relation entre SiteCollection et CollectedData
            ->join('collectedData.createdBy', 'createdBy') // Relation entre CollectedData et CreatedBy
            ->join('collectedData.birdSpeciesCounts', 'speciesCount') // Relation entre CollectedData et BirdSpeciesCounts
            ->leftJoin('speciesCount.birdSpecies', 'birdSpecies') // Relation optionnelle pour BirdSpecies
            ->leftJoin('collectedData.method', 'method') // Relation optionnelle pour Method
            ->where('campaign = :campaign')
            ->setParameter('campaign', $campaign)
            ->orderBy('collectedData.createdAt', 'ASC');
   
        return $qb->getQuery()->getArrayResult();
    }
    
    //    /**
    //     * @return SiteCollection[] Returns an array of SiteCollection objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('s')
    //            ->andWhere('s.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('s.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?SiteCollection
    //    {
    //        return $this->createQueryBuilder('s')
    //            ->andWhere('s.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
