<?php

namespace App\Repository;

use App\Entity\BirdSpeciesCount;
use App\Entity\CountingCampaign;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<BirdSpeciesCount>
 */
class BirdSpeciesCountRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BirdSpeciesCount::class);
    }

    // Les trois espèces les plus comptées dans une campagne spécifique
    // public function getTopThreeBirdSpeciesInCampaign(CountingCampaign $campaign)
    // {
    //     return $this->createQueryBuilder('bsc')
            // ->select('species.scientificName AS scientificName', 'SUM(bsc.count) AS topThreeCount') // Sélectionne le nom de l'espèce et le total compté
            // ->join('bsc.collectedData', 'cd') // Jointure avec CollectedData
            // ->join('bsc.birdSpecies', 'species') // Jointure avec l'entité BirdSpecies
            // ->where('cd.countingCampaign = :campaignId') // Filtre par campagne
            // ->setParameter('campaignId', $campaign->getId()) // Passe l'ID de la campagne
            // ->groupBy('species.scientificName') // Groupe par nom de l'espèce
            // ->orderBy('topThreeCount', 'DESC') // Trie par total compté de manière décroissante
            // ->setMaxResults(3) // Limite aux trois espèces les plus comptées
            // ->getQuery()
            // ->getResult(); // Exécute la requête et récupère les résultats
    // }
    public function getTopThreeBirdSpeciesInCampaignWithImages(CountingCampaign $campaign): array
    {
        return $this->createQueryBuilder('bsc')
            ->select('species.scientificName AS scientificName', 'SUM(bsc.count) AS topThreeCount', 'img.imageFilename AS imagePath') // Sélectionne le nom de l'espèce et le total compté
            ->join('bsc.collectedData', 'cd') // Jointure avec CollectedData
            ->join('bsc.birdSpecies', 'species') // Jointure avec l'entité BirdSpecies
            ->leftJoin('species.image', 'img')
            ->where('cd.countingCampaign = :campaignId') // Filtre par campagne
            ->setParameter('campaignId', $campaign->getId()) // Passe l'ID de la campagne
            ->groupBy('species.scientificName') // Groupe par nom de l'espèce
            ->orderBy('topThreeCount', 'DESC') // Trie par total compté de manière décroissante
            ->setMaxResults(10) // Limite aux trois espèces les plus comptées
            ->getQuery()
            ->getResult(); // Exécute la requête et récupère les résultats
    }
    

    /**
     * Récupère le nom de chaque campagne et le nombre total d'oiseaux comptés dans chaque campagne.
     *
     * @return array Un tableau associatif contenant le nom de chaque campagne et le total d'oiseaux comptés
     */
    public function getTotalBirdsCountByCampaign(): array
    {
        $query = $this->createQueryBuilder('bsc')
            ->select('cc.campaignName AS campaignName', 'SUM(bsc.count) AS totalBirdCount', 'cc.endDate AS endDate')
            ->join('bsc.collectedData', 'cd')                // Jointure avec CollectedData
            ->join('cd.countingCampaign', 'cc')              // Jointure avec CountingCampaign pour obtenir le nom de la campagne
            ->groupBy('cc.id')                               // Grouper par campagne
            ->orderBy('cc.endDate', 'ASC')                   // Tri par date de fin décroissante
            ->setMaxResults(12)                               // Limiter à 10 résultats
            ->getQuery();

        // Exécute la requête et retourne le résultat
        return $query->getResult();
    }

    /**
     * Récupère le nom de chaque campagne et le nombre total d'espèces d'oiseaux uniques comptées dans chaque campagne.
     *
     * @return array Un tableau associatif contenant le nom de chaque campagne et le total d'espèces d'oiseaux uniques comptées
     */
    public function getTotalUniqueBirdSpeciesCountByCampaign(): array
    {
        $query = $this->createQueryBuilder('bsc')
            ->select('cc.campaignName AS campaignName', 'COUNT(DISTINCT bsc.birdSpecies) AS uniqueBirdSpeciesCount')
            ->join('bsc.collectedData', 'cd')               // Jointure avec CollectedData
            ->join('cd.countingCampaign', 'cc')             // Jointure avec CountingCampaign pour obtenir le nom de la campagne
            ->groupBy('cc.id')                              // Grouper par campagne
            ->orderBy('cc.campaignName', 'ASC')             // Optionnel : trier par nom de campagne
            ->getQuery();

        // Exécute la requête et retourne le résultat
        return $query->getResult();
    }


    /**
     * Calcule le nombre total d'oiseaux comptés par campagne
     *
     * @param CountingCampaign $campaign
     * @return int
     */
    public function countTotalBirdsInCampaign(CountingCampaign $campaign): int
    {
        $query = $this->createQueryBuilder('bsc')
            ->select('SUM(bsc.count) AS total') // Somme des comptages d'oiseaux
            ->join('bsc.collectedData', 'cd') // Jointure avec les données collectées
            ->where('cd.countingCampaign = :campaignId') // Condition sur la campagne
            ->setParameter('campaignId', $campaign->getId()); // Paramètre pour l'ID de la campagne

        // Exécute la requête et obtient le résultat
        $result = $query->getQuery()->getScalarResult();

        // Vérifie si le résultat est vide ou null
        return !empty($result[0]['total']) ? (int) $result[0]['total'] : 0; // Retourner 0 si aucune donnée n'est trouvée
    }

    /**
     * Compte le nombre total d'espèces uniques observées dans une campagne.
     *
     * @param CountingCampaign $campaign
     * @return int
     */
    public function countUniqueBirdSpeciesInCampaign(CountingCampaign $campaign): int
    {
        $query = $this->createQueryBuilder('bsc')
            ->select('COUNT(DISTINCT bsc.birdSpecies) AS uniqueSpeciesCount') // Compte distinct des espèces d'oiseaux
            ->join('bsc.collectedData', 'cd') // Jointure avec les données collectées
            ->where('cd.countingCampaign = :campaignId') // Condition sur la campagne
            ->setParameter('campaignId', $campaign->getId()); // Paramètre pour l'ID de la campagne

        // Exécute la requête et obtient le résultat
        $result = $query->getQuery()->getScalarResult();

        // Vérifie si le résultat est vide ou null
        return !empty($result[0]['uniqueSpeciesCount']) ? (int) $result[0]['uniqueSpeciesCount'] : 0; // Retourner 0 si aucune donnée n'est trouvée
    }


    //    /**
    //     * @return BirdSpeciesCount[] Returns an array of BirdSpeciesCount objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('b')
    //            ->andWhere('b.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('b.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?BirdSpeciesCount
    //    {
    //        return $this->createQueryBuilder('b')
    //            ->andWhere('b.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
