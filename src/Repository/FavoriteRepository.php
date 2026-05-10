<?php

namespace App\Repository;

use App\Entity\Favorite;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class FavoriteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Favorite::class);
    }

    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('f')
            ->where('f.user = :user')
            ->setParameter('user', $user)
            ->orderBy('f.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByUserAndType(User $user, string $type): array
    {
        return $this->createQueryBuilder('f')
            ->where('f.user = :user')
            ->andWhere('f.itemType = :type')
            ->setParameter('user', $user)
            ->setParameter('type', $type)
            ->orderBy('f.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function isFavorite(User $user, string $itemType, int $itemId): bool
    {
        $count = $this->createQueryBuilder('f')
            ->select('COUNT(f.id)')
            ->where('f.user = :user')
            ->andWhere('f.itemType = :type')
            ->andWhere('f.itemId = :itemId')
            ->setParameter('user', $user)
            ->setParameter('type', $itemType)
            ->setParameter('itemId', $itemId)
            ->getQuery()
            ->getSingleScalarResult();

        return $count > 0;
    }

    public function countByUser(User $user): int
    {
        return $this->createQueryBuilder('f')
            ->select('COUNT(f.id)')
            ->where('f.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findFavorite(User $user, string $itemType, int $itemId): ?Favorite
    {
        return $this->createQueryBuilder('f')
            ->where('f.user = :user')
            ->andWhere('f.itemType = :type')
            ->andWhere('f.itemId = :itemId')
            ->setParameter('user', $user)
            ->setParameter('type', $itemType)
            ->setParameter('itemId', $itemId)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
