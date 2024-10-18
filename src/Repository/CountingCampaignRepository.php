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

    /**
     * Récupère le total des comptages d'oiseaux par site pour une campagne donnée
     *
     * @param CountingCampaign $campaign
     * @return array
     */
    public function getTotalCountsBySite(CountingCampaign $campaign): array
    {
        return $this->createQueryBuilder('cc')
            ->select('s.id AS siteId, s.siteName, SUM(bsc.count) AS totalCounts') // Supposons que vous avez une entité BirdSpeciesCount avec un champ 'count'
            ->join('cc.siteAgentsGroups', 'sag') // Jointure avec les groupes d'agents par site
            ->join('sag.siteCollection', 's') // Jointure avec les collections de sites
            ->join('s.collectedData', 'cd') // Jointure avec les données collectées
            ->join('cd.birdSpeciesCounts', 'bsc') // Jointure avec les comptages d'espèces d'oiseaux (ou autre logique)
            ->where('cc.id = :campaignId') // Filtrer par campagne
            ->setParameter('campaignId', $campaign->getId())
            ->groupBy('s.id') // Grouper par site
            ->getQuery()
            ->getResult();
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


    public function getCollectedDataForCampaign(CountingCampaign $campaign)
    {
        return $this->createQueryBuilder('cc')
            ->select('cc, cd') // Sélectionne à la fois l'entité principale et les données collectées
            ->join('cc.collectedData', 'cd')
            ->where('cc.id = :campaignId')
            ->setParameter('campaignId', $campaign->getId())
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
