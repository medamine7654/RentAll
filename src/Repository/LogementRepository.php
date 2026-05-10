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
     * Trouve les logements disponibles (isActive replaces old 'disponible' field)
     */
    public function findAvailable()
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('l.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find all logements for admin moderation panel
     */
    public function findAllForAdmin(): array
    {
        return $this->createQueryBuilder('l')
            ->leftJoin('l.proprietaire', 'p')
            ->addSelect('p')
            ->orderBy('l.isActive', 'ASC')
            ->addOrderBy('l.id', 'DESC')
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
