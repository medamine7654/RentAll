<?php

namespace App\Repository;

use App\Entity\Logement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Logement>
 */
class LogementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Logement::class);
    }

    /**
     * Trouve les logements disponibles
     */
    public function findAvailable()
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.disponible = :disponible')
            ->setParameter('disponible', true)
            ->orderBy('l.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les logements d'un propriétaire
     */
    public function findByProprietaire($proprietaire)
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.proprietaire = :proprietaire')
            ->setParameter('proprietaire', $proprietaire)
            ->orderBy('l.id', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
