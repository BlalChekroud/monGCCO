<?php

namespace App\Repository;

use App\Entity\CollectedData;
use App\Entity\CountingCampaign;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CollectedData>
 */
class CollectedDataRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CollectedData::class);
    }


    // Récupérer les données collectées (CollectedData) associées à une campagne de comptage
    public function findByCountingCampaign(CountingCampaign $countingCampaign): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.countingCampaign = :campaign')
            ->setParameter('campaign', $countingCampaign)
            ->orderBy('c.createdAt', 'DESC') // Vous pouvez changer l'ordre selon vos besoins
            ->getQuery()
            ->getResult();
    }

    public function getLeaderByCollectedData(CollectedData $collectedData): ?array
    {
        $qb = $this->createQueryBuilder('cd')
            ->join('cd.environmentalConditions', 'ec')  // Join CollectedData à EnvironmentalConditions
            ->join('ec.siteCollection', 'site')         // Join EnvironmentalConditions à SiteCollection
            ->join('site.siteAgentsGroups', 'sag')      // Join SiteCollection à SiteAgentsGroups
            ->join('sag.agentsGroup', 'ag')             // Join SiteAgentsGroup à AgentsGroup
            ->join('ag.leader', 'leader')               // Join AgentsGroup à User (Leader)
            ->select('leader.email, ag.groupName as groupName') // Sélectionner l'id et nom du leader et du groupe
            ->where('cd = :collectedData')
            ->setParameter('collectedData', $collectedData)
            ->setMaxResults(1);
        
        // Retourner le leader et le groupe (leader et agentsGroup)
        return $qb->getQuery()->getOneOrNullResult(); // Retourne un tableau avec les données du leader et du groupe
    }
    
    // Pour récupérer toutes les collectes où l'utilisateur est le chef du groupe (leader)
    public function getCollectesByUser(User $user): array
    {
        $qb = $this->createQueryBuilder('cd')
            // Récupérer les collectes créées par l'utilisateur
            ->leftJoin('cd.createdBy', 'creator')  // Relier à l'utilisateur qui a créé la collecte
            ->join('cd.environmentalConditions', 'ec')  // Relier CollectedData à EnvironmentalConditions
            ->join('ec.siteCollection', 'site')  // Relier EnvironmentalConditions à SiteCollection
            ->join('site.siteAgentsGroups', 'sag')  // Relier SiteCollection à SiteAgentsGroups
            ->join('sag.agentsGroup', 'ag')  // Relier SiteAgentsGroup à AgentsGroup
            ->join('ag.leader', 'leader')  // Relier AgentsGroup au leader
            ->leftJoin('ag.groupMember', 'groupMember')  // Relier les membres du groupe
            // L'utilisateur est le créateur ou le leader du groupe
            ->where('creator = :user')
            ->orWhere('leader = :user')
            // Exclure les collectes où l'utilisateur est uniquement membre, mais pas leader
            ->andWhere('groupMember != :user OR leader = :user')
            ->setParameter('user', $user)  // Passer l'utilisateur comme paramètre
            ->orderBy('cd.createdAt', 'ASC');  // Optionnel : trier les résultats par date de création
        
        // Exécuter la requête et retourner les résultats
        return $qb->getQuery()->getResult();
    }
       
       
    
    //    /**
    //     * @return CollectedData[] Returns an array of CollectedData objects
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

    //    public function findOneBySomeField($value): ?CollectedData
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
