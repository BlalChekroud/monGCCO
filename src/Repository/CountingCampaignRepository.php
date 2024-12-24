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

    // Récupère les données de campagne de comptage pour l'exportation
    public function getBirdSpeciesDataForCampaign(int $campaignId): array
    {
        $qb = $this->createQueryBuilder('cc')
            ->select(
                'cc.campaignName',
                'cc.startDate',
                'cc.endDate',
                'cc.description',
                'cc.createdAt',
                'cc.updatedAt',
                'creator.email AS createdBy',
                'city.name AS cityName',
                'site.siteName',
                'cd.createdAt AS collectedDate',
                'cd.createdBy AS collectedBy',
                'bs.scientificName',
                'bs.wispeciesCode',
                'bsc.count AS birdCount'
            )
            ->innerJoin('cc.siteAgentsGroups', 'sag')
            ->innerJoin('sag.siteCollection', 'site')
            ->innerJoin('site.city', 'city')
            ->innerJoin('site.collectedData', 'cd')
            ->innerJoin('cd.birdSpeciesCounts', 'bsc')
            ->innerJoin('bsc.birdSpecies', 'bs')
            ->innerJoin('cc.createdBy', 'creator')
            ->where('cc.id = :campaignId')
            ->setParameter('campaignId', $campaignId)
            ->orderBy('city.name', 'ASC')
            ->addOrderBy('site.siteName', 'ASC')
            ->addOrderBy('bs.scientificName', 'ASC');
    
        return $qb->getQuery()->getResult();
    }    

    // /**
    //  * Récupère le nombre total de comptages d'oiseaux pour chaque site dans une campagne de comptage spécifique.
    //  *
    //  * @param CountingCampaign $campaign La campagne de comptage cible
    //  * @return array Un tableau associatif contenant le nom de chaque site et le nombre total d'oiseaux comptés
    //  */
    // public function getTotalBirdsCountPerSitesInCampaign(CountingCampaign $campaign): array
    // {
    //     $result = $this->createQueryBuilder('sc')
    //         ->select('siteCollection.siteName', 'COALESCE(SUM(bsc.count), 0) AS totalBirdCount') // Utilisation de COALESCE pour gérer les valeurs nulles
    //         ->join('sc.siteAgentsGroups', 'sag')  // Jointure avec les groupes d'agents des sites
    //         ->join('sag.countingCampaign', 'cc')  // Jointure avec la campagne de comptage
    //         ->join('sag.siteCollection', 'siteCollection')  // Jointure avec la collection de sites
    //         ->leftJoin('sc.collectedData', 'cd')  // Utilisation de LEFT JOIN pour inclure les sites sans données collectées
    //         ->leftJoin('cd.birdSpeciesCounts', 'bsc')  // Utilisation de LEFT JOIN pour inclure les sites sans comptages d'espèces
    //         ->where('cc = :campaign')  // Filtrer par la campagne spécifiée
    //         ->setParameter('campaign', $campaign)  // Passer la campagne spécifiée
    //         ->groupBy('siteCollection.id')  // Grouper par chaque site pour obtenir un total par site
    //         ->getQuery()
    //         ->getResult();

    //     // Assurez-vous que si un site n'a pas de comptage, il retourne 0
    //     return $result;
    // }
    // public function getBirdsCountPerSitesInCampaign(CountingCampaign $campaign): array
    // {
    //     $result = $this->createQueryBuilder('sc')
    //         ->select('siteCollection.siteName', 'bsc.count AS totalBirdCount')  // Sélectionner les comptages sans somme
    //         ->join('sc.siteAgentsGroups', 'sag') 
    //         ->join('sag.countingCampaign', 'cc')  // Associer avec la campagne
    //         ->join('sc.collectedData', 'cd')
    //         ->join('cd.birdSpeciesCounts', 'bsc')
    //         ->join('sag.siteCollection', 'siteCollection')  // Joindre avec les collections de sites
    //         ->where('cc.id = :campaignId')  // Filtrer par l'ID de la campagne
    //         ->setParameter('campaignId', $campaign->getId())  // Utiliser l'ID de la campagne spécifiée
    //         ->getQuery()
    //         ->getResult();

    //     return $result;
    // }


    // /**
    //  * Calcule le nombre total d'oiseaux comptés dans la campagne
    //  *
    //  * @param CountingCampaign $campaign
    //  * @return int
    //  */
    // public function getTotalCountsCampaign(CountingCampaign $campaign): int
    // {
    //     return (int) $this->createQueryBuilder('cc')
    //         ->select('SUM(bsc.count) AS totalCount') // Somme des comptages d'oiseaux
    //         ->join('cc.siteAgentsGroups', 'sag') // Jointure avec les groupes d'agents de site
    //         ->join('sag.siteCollection', 'sc') // Accès aux collections de site via siteAgentsGroups
    //         ->join('sc.collectedData', 'cd') // Jointure avec les données collectées
    //         ->join('cd.birdSpeciesCounts', 'bsc') // Jointure avec les comptages d'espèces d'oiseaux
    //         ->where('cc.id = :campaignId') // Condition sur l'identifiant de la campagne
    //         ->setParameter('campaignId', $campaign->getId()) // Paramètre de la campagne
    //         ->getQuery()
    //         ->getSingleScalarResult(); // Récupère le résultat unique
    // }


    // Le nombre total de collectes associées à une campagne de comptage
    public function countCollectedDataByCampaign(CountingCampaign $campaign): int
    {
        return (int) $this->createQueryBuilder('cc')
            ->select('COUNT(cd.id)')
            ->join('cc.collectedData', 'cd') // Jointure avec les données collectées
            ->where('cc.id = :campaignId')
            ->setParameter('campaignId', $campaign->getId())
            ->getQuery()
            ->getSingleScalarResult(); // Récupère le résultat sous forme scalaire
    }

    // Récupère toutes les méthodes de collecte utilisées dans la campagne
    public function getMethodsUsedInCampaign(CountingCampaign $campaign): array
    {
        $result = $this->createQueryBuilder('cc')
            ->select('DISTINCT m.label') // Récupérer les labels uniques des méthodes
            ->join('cc.siteAgentsGroups', 'sag') // Jointure avec les SiteAgentsGroups
            ->join('sag.siteCollection', 'sc') // Jointure avec les SiteCollections
            ->join('sc.collectedData', 'cd') // Jointure avec les données collectées
            ->join('cd.method', 'm') // Jointure avec les méthodes de collecte
            ->where('cc.id = :campaignId')
            ->setParameter('campaignId', $campaign->getId())
            ->getQuery()
            ->getArrayResult();

        // Extraire les labels de méthode de l'array de résultats
        return array_column($result, 'label');
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
    
    // public function findByCampaign($campaign)
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
     * Récupère la dernière campagne créée
     */
    public function findMostRecentCampaign(): ?CountingCampaign
    {
        return $this->createQueryBuilder('c')
            ->orderBy('c.createdAt', 'DESC') // Trier par la date de création décroissante
            ->setMaxResults(1) // Limiter à une seule campagne
            ->getQuery()
            ->getOneOrNullResult(); // Récupère un seul résultat ou null si aucune campagne
    }


    public function getSiteCollectionsForCampaign(CountingCampaign $campaign): array
    {
        return $this->createQueryBuilder('cc')
            ->select('sc') // Sélectionner uniquement les entités SiteCollection
            ->join('cc.siteAgentsGroups', 'sag') // Jointure avec SiteAgentsGroups
            ->join('sag.siteCollection', 'sc') // Jointure avec SiteCollection
            ->where('cc.id = :campaignId') // Filtrer par campagne
            ->setParameter('campaignId', $campaign->getId()) // Utiliser l'ID de la campagne
            ->getQuery()
            ->getResult();
    }

    public function getExportDataByCampaign(CountingCampaign $campaign): array
    {
        $qb = $this->createQueryBuilder('campaign')
            ->select(
                'DISTINCT campaign.campaignName AS Campaign', // Utilisez DISTINCT pour éviter les doublons
                'campaign.startDate AS startDate',
                'campaign.endDate AS endDate',
                'siteCollection.siteName AS Site',
                'city.name AS City',
                'region.name AS Region',
                'createdBy.name AS AgentName',
                'createdBy.lastName AS AgentLastName',
                'birdSpecies.scientificName AS Species',
                'speciesCount.count AS Count',
                'method.label AS Method',
                'collectedData.createdAt AS CreatedAt' // Récupère la date brute
            )
            // Joins pour lier les entités avec CountingCampaign
            ->join('campaign.siteAgentsGroups', 'sag') // CountingCampaign -> SiteAgentsGroups
            ->join('sag.siteCollection', 'siteCollection') // SiteAgentsGroup -> SiteCollection
            ->leftJoin('siteCollection.city', 'city') // SiteCollection -> City
            ->leftJoin('city.region', 'region') // City -> Region
    
            // Joins pour lier les données collectées et les espèces d'oiseaux
            ->join('siteCollection.collectedData', 'collectedData') // SiteCollection -> CollectedData
            ->leftJoin('collectedData.createdBy', 'createdBy') // CollectedData -> CreatedBy
            ->leftJoin('collectedData.birdSpeciesCounts', 'speciesCount') // CollectedData -> BirdSpeciesCounts
            ->leftJoin('speciesCount.birdSpecies', 'birdSpecies') // BirdSpeciesCounts -> BirdSpecies
            ->leftJoin('collectedData.method', 'method') // CollectedData -> Method
    
            // Joins pour lier les conditions environnementales liées à la campagne
            ->join('siteCollection.environmentalConditions', 'environmentalCondition') // SiteCollection -> EnvironmentalConditions
            ->join('environmentalCondition.countingCampaign', 'ecCampaign') // EnvironmentalCondition -> CountingCampaign
    
            // Filtrer les données spécifiques à la campagne
            ->where('campaign = :campaign') // Ne récupère que les données pour la campagne spécifique
            ->andWhere('ecCampaign = :campaign') // Assurez-vous que la condition environnementale est associée à cette campagne
            ->setParameter('campaign', $campaign)
    
            // Trier les résultats par date de création des données collectées
            ->orderBy('collectedData.createdAt', 'ASC');
    
        return $qb->getQuery()->getArrayResult();
    }
    
    // public function getExportDataForAllCampaigns(): array
    // {
    //     $qb = $this->createQueryBuilder('campaign')
    //         ->select(
    //             'campaign.campaignName AS Campaign',
    //             'campaign.startDate AS startDate',
    //             'campaign.endDate AS endDate',
    //             'siteCollection.siteName AS Site',
    //             'city.name AS City',
    //             'region.name AS Region',
    //             'createdBy.email AS Agent',
    //             'birdSpecies.scientificName AS Species',
    //             'speciesCount.count AS Count',
    //             'method.label AS Method',
    //             'collectedData.createdAt AS CreatedAt' // Récupère la date brute
    //         )
    //         ->join('campaign.siteCollections', 'siteCollection') // Relation entre CountingCampaign et SiteCollection
    //         ->join('siteCollection.city', 'city') // Relation entre SiteCollection et City
    //         ->join('city.region', 'region') // Relation entre City et Region
    //         ->join('siteCollection.collectedData', 'collectedData') // Relation entre SiteCollection et CollectedData
    //         ->join('collectedData.createdBy', 'createdBy') // Relation entre CollectedData et CreatedBy
    //         ->join('collectedData.birdSpeciesCounts', 'speciesCount') // Relation entre CollectedData et BirdSpeciesCounts
    //         ->leftJoin('speciesCount.birdSpecies', 'birdSpecies') // Relation optionnelle pour BirdSpecies
    //         ->leftJoin('collectedData.method', 'method') // Relation optionnelle pour Method
    //         ->orderBy('collectedData.createdAt', 'ASC');

    //     return $qb->getQuery()->getArrayResult();
    // }

    

    // public function getCollectedDataForCampaign(CountingCampaign $campaign)
    // {
    //     return $this->createQueryBuilder('cc')
    //         ->select('cc, cd') // Sélectionne à la fois l'entité principale et les données collectées
    //         ->join('cc.collectedData', 'cd')
    //         ->where('cc.id = :campaignId')
    //         ->setParameter('campaignId', $campaign->getId())
    //         ->getQuery()
    //         ->getResult();
    // }




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
