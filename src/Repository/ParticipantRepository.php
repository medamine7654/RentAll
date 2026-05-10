<?php

namespace App\Repository;

use App\Entity\Covoiturage;
use App\Entity\Participant;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Participant>
 *
 * NOTE: The 'participant' table does not exist in the remote DB.
 * All methods return safe empty values to prevent runtime errors.
 * The participant/booking feature is disabled until the table is created.
 */
class ParticipantRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Participant::class);
    }

    public function isUserParticipant(User $user, Covoiturage $covoiturage): bool
    {
        return false;
    }

    public function countParticipantsByTrip(Covoiturage $covoiturage): int
    {
        return 0;
    }

    public function findOneByUserAndTrip(User $user, Covoiturage $covoiturage): ?Participant
    {
        return null;
    }

    /**
     * @param int[] $tripIds
     * @return array<int, int>
     */
    public function getCountsByTripIds(array $tripIds): array
    {
        return [];
    }

    /**
     * @param int[] $tripIds
     * @return int[]
     */
    public function getBookedTripIdsForUser(User $user, array $tripIds): array
    {
        return [];
    }

    /**
     * Override findBy to avoid hitting the non-existent table.
     */
    public function findBy(array $criteria, ?array $orderBy = null, $limit = null, $offset = null): array
    {
        return [];
    }

    /**
     * Override count to avoid hitting the non-existent table.
     */
    public function count(array $criteria = []): int
    {
        return 0;
    }
}
