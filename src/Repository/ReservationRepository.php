<?php

namespace App\Repository;

use App\Entity\Reservation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Reservation>
 */
class ReservationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Reservation::class);
    }

    /**
     * Trouve les réservations d'un utilisateur
     */
    public function findByUser($user)
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.locataire = :user')
            ->setParameter('user', $user)
            ->orderBy('r.dateDebut', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les réservations d'un logement
     */
    public function findByLogement($logement)
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.logement = :logement')
            ->setParameter('logement', $logement)
            ->orderBy('r.dateDebut', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les réservations confirmées
     */
    public function findConfirmed()
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.statut = :statut')
            ->setParameter('statut', 'confirmee')
            ->orderBy('r.dateDebut', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
