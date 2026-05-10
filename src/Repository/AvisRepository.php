<?php

namespace App\Repository;

use App\Entity\Avis;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Avis>
 */
class AvisRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Avis::class);
    }

    /**
     * Trouve les avis d'un logement via les réservations
     */
    public function findByLogement($logement): array
    {
        return $this->createQueryBuilder('a')
            ->join('a.reservation', 'r')
            ->where('r.logement = :logement')
            ->setParameter('logement', $logement)
            ->orderBy('a.dateCreation', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Calcule la note moyenne d'un logement
     */
    public function getAverageRatingForLogement($logement): ?float
    {
        $result = $this->createQueryBuilder('a')
            ->select('AVG(a.note) as moyenne')
            ->join('a.reservation', 'r')
            ->where('r.logement = :logement')
            ->setParameter('logement', $logement)
            ->getQuery()
            ->getSingleScalarResult();

        return $result ? (float) $result : null;
    }

    /**
     * Retourne en une seule requete la moyenne et le nombre d'avis par logement.
     *
     * @param array<int, object> $logements
     * @return array<int, array{average: ?float, total: int}>
     */
    public function getRatingsSummaryForLogements(array $logements): array
    {
        if ($logements === []) {
            return [];
        }

        $rows = $this->createQueryBuilder('a')
            ->select('IDENTITY(r.logement) AS logementId')
            ->addSelect('AVG(a.note) AS averageRating')
            ->addSelect('COUNT(a.id) AS totalAvis')
            ->join('a.reservation', 'r')
            ->where('r.logement IN (:logements)')
            ->setParameter('logements', $logements)
            ->groupBy('r.logement')
            ->getQuery()
            ->getArrayResult();

        $summary = [];
        foreach ($rows as $row) {
            $id = (int) ($row['logementId'] ?? 0);
            if ($id <= 0) {
                continue;
            }

            $averageRaw = $row['averageRating'] ?? null;
            $summary[$id] = [
                'average' => $averageRaw !== null ? (float) $averageRaw : null,
                'total' => (int) ($row['totalAvis'] ?? 0),
            ];
        }

        return $summary;
    }

    //    /**
    //     * @return Avis[] Returns an array of Avis objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('a')
    //            ->andWhere('a.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('a.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Avis
    //    {
    //        return $this->createQueryBuilder('a')
    //            ->andWhere('a.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
