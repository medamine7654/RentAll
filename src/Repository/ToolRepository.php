<?php

namespace App\Repository;

use App\Entity\Tool;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ToolRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Tool::class);
    }

    public function findAllActive(): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('t.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByHost(User $host): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.host = :host')
            ->setParameter('host', $host)
            ->orderBy('t.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findAllForAdmin(): array
    {
        return $this->createQueryBuilder('t')
            ->orderBy('t.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Search and filter tools with sorting
     * Returns QueryBuilder for pagination support
     */
    public function findBySearchFiltersQuery(
        ?string $query,
        ?\App\Entity\Category $category,
        ?string $location,
        ?float $minPrice,
        ?float $maxPrice,
        ?string $sortBy = 'date_desc'
    ) {
        $qb = $this->createQueryBuilder('t')
            ->where('t.isActive = :active')
            ->setParameter('active', true);

        // Search query (case-insensitive, partial match)
        if ($query) {
            $qb->andWhere('LOWER(t.name) LIKE LOWER(:query) OR LOWER(t.description) LIKE LOWER(:query)')
               ->setParameter('query', '%' . $query . '%');
        }

        // Category filter
        if ($category) {
            $qb->andWhere('t.category = :category')
               ->setParameter('category', $category);
        }

        // Location filter (case-insensitive, partial match)
        if ($location) {
            $qb->andWhere('LOWER(t.location) LIKE LOWER(:location)')
               ->setParameter('location', '%' . $location . '%');
        }

        // Price range (pricePerDay for tools)
        if ($minPrice !== null) {
            $qb->andWhere('t.pricePerDay >= :minPrice')
               ->setParameter('minPrice', $minPrice);
        }

        if ($maxPrice !== null) {
            $qb->andWhere('t.pricePerDay <= :maxPrice')
               ->setParameter('maxPrice', $maxPrice);
        }

        // Sorting
        switch ($sortBy) {
            case 'date_asc':
                $qb->orderBy('t.createdAt', 'ASC');
                break;
            case 'price_asc':
                $qb->orderBy('t.pricePerDay', 'ASC');
                break;
            case 'price_desc':
                $qb->orderBy('t.pricePerDay', 'DESC');
                break;
            case 'name_asc':
                $qb->orderBy('t.name', 'ASC');
                break;
            case 'name_desc':
                $qb->orderBy('t.name', 'DESC');
                break;
            case 'date_desc':
            default:
                $qb->orderBy('t.createdAt', 'DESC');
                break;
        }

        return $qb;
    }

    /**
     * Find similar tools using AI-powered recommendation algorithm
     * Scoring: Category match (50%) + Price range ±25% (30%) + Location match (20%)
     */
    public function findSimilar(Tool $tool, int $limit = 3): array
    {
        $categoryId = $tool->getCategory()?->getId();
        $price = $tool->getPricePerDay();
        $location = $tool->getLocation();
        
        // Calculate price range (±25%)
        $minPrice = $price * 0.75;
        $maxPrice = $price * 1.25;

        $qb = $this->createQueryBuilder('t')
            ->where('t.id != :currentId')
            ->andWhere('t.isActive = :active')
            ->setParameter('currentId', $tool->getId())
            ->setParameter('active', true);

        // If category exists, prioritize same category
        if ($categoryId) {
            $qb->andWhere('t.category = :category')
               ->setParameter('category', $tool->getCategory());
        }

        // Add scoring logic
        $qb->addSelect('
            (CASE WHEN t.category = :categoryParam THEN 50 ELSE 0 END) +
            (CASE WHEN t.pricePerDay BETWEEN :minPrice AND :maxPrice THEN 30 ELSE 0 END) +
            (CASE WHEN t.location = :location THEN 20 ELSE 0 END) as HIDDEN score
        ')
        ->setParameter('categoryParam', $tool->getCategory())
        ->setParameter('minPrice', $minPrice)
        ->setParameter('maxPrice', $maxPrice)
        ->setParameter('location', $location)
        ->orderBy('score', 'DESC')
        ->addOrderBy('t.createdAt', 'DESC')
        ->setMaxResults($limit);

        return $qb->getQuery()->getResult();
    }

    /**
     * Get all prices for tools in a specific category
     * Used for price suggestion algorithm
     */
    public function getPricesByCategory(\App\Entity\Category $category): array
    {
        $results = $this->createQueryBuilder('t')
            ->select('t.pricePerDay')
            ->where('t.category = :category')
            ->andWhere('t.isActive = :active')
            ->setParameter('category', $category)
            ->setParameter('active', true)
            ->getQuery()
            ->getResult();

        // Extract prices from result array
        return array_map(fn($item) => (float) $item['pricePerDay'], $results);
    }
}
