<?php

namespace App\Repository;

use App\Entity\Covoiturage;
use App\Entity\Participant;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Participant>
 */
class ParticipantRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Participant::class);
    }

    //    /**
    //     * @return Participant[] Returns an array of Participant objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('p.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Participant
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }

    public function isUserParticipant(User $user, Covoiturage $covoiturage): bool
    {
        $count = (int) $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->andWhere('p.passager = :user')
            ->andWhere('p.covoiturage = :trip')
            ->setParameter('user', $user)
            ->setParameter('trip', $covoiturage)
            ->getQuery()
            ->getSingleScalarResult();

        return $count > 0;
    }

    public function countParticipantsByTrip(Covoiturage $covoiturage): int
    {
        return (int) $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->andWhere('p.covoiturage = :trip')
            ->andWhere('p.statut = :statut')
            ->setParameter('trip', $covoiturage)
            ->setParameter('statut', 'confirme')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findOneByUserAndTrip(User $user, Covoiturage $covoiturage): ?Participant
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.passager = :user')
            ->andWhere('p.covoiturage = :trip')
            ->setParameter('user', $user)
            ->setParameter('trip', $covoiturage)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @param int[] $tripIds
     * @return array<int, int> [tripId => participantCount]
     */
    public function getCountsByTripIds(array $tripIds): array
    {
        if ($tripIds === []) {
            return [];
        }

        $rows = $this->createQueryBuilder('p')
            ->select('IDENTITY(p.covoiturage) AS tripId, COUNT(p.id) AS total')
            ->andWhere('p.covoiturage IN (:tripIds)')
            ->andWhere('p.statut = :statut')
            ->setParameter('tripIds', $tripIds)
            ->setParameter('statut', 'confirme')
            ->groupBy('p.covoiturage')
            ->getQuery()
            ->getArrayResult();

        $counts = [];
        foreach ($rows as $row) {
            $counts[(int) $row['tripId']] = (int) $row['total'];
        }

        return $counts;
    }

    /**
     * @param int[] $tripIds
     * @return int[]
     */
    public function getBookedTripIdsForUser(User $user, array $tripIds): array
    {
        if ($tripIds === []) {
            return [];
        }

        $rows = $this->createQueryBuilder('p')
            ->select('IDENTITY(p.covoiturage) AS tripId')
            ->andWhere('p.passager = :user')
            ->andWhere('p.covoiturage IN (:tripIds)')
            ->setParameter('user', $user)
            ->setParameter('tripIds', $tripIds)
            ->getQuery()
            ->getArrayResult();

        return array_map(static fn (array $row): int => (int) $row['tripId'], $rows);
    }
}
