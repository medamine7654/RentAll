<?php

namespace App\Repository;

use App\Entity\Category;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Category>
 */
class CategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Category::class);
    }

    /**
     * Find categories by type (service or tool)
     */
    public function findByType(string $type): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.type = :type')
            ->setParameter('type', $type)
            ->orderBy('c.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get category statistics.
     * Note: description and icon columns don't exist in the remote DB.
     * They are injected as null into each row to keep templates working.
     */
    public function getCategoryStats(): array
    {
        $rows = $this->createQueryBuilder('c')
            ->select('c.id, c.name, c.type, COUNT(DISTINCT s.id) as serviceCount, COUNT(DISTINCT t.id) as toolCount')
            ->leftJoin('c.services', 's')
            ->leftJoin('c.tools', 't')
            ->groupBy('c.id')
            ->orderBy('c.type', 'ASC')
            ->addOrderBy('c.name', 'ASC')
            ->getQuery()
            ->getResult();

        // Inject missing columns so templates don't crash
        return array_map(static function (array $row): array {
            $row['description'] = null;
            $row['icon']        = null;
            return $row;
        }, $rows);
    }
}
