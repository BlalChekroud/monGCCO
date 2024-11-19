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
