<?php

namespace App\Repository;

use App\Entity\Service;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ServiceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Service::class);
    }

    public function findAllActive(): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('s.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByHost(User $host): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.host = :host')
            ->setParameter('host', $host)
            ->orderBy('s.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findAllForAdmin(): array
    {
        return $this->createQueryBuilder('s')
            ->orderBy('s.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Search and filter services with sorting
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
        $qb = $this->createQueryBuilder('s')
            ->where('s.isActive = :active')
            ->setParameter('active', true);

        // Search query (case-insensitive, partial match)
        if ($query) {
            $qb->andWhere('LOWER(s.name) LIKE LOWER(:query) OR LOWER(s.description) LIKE LOWER(:query)')
               ->setParameter('query', '%' . $query . '%');
        }

        // Category filter
        if ($category) {
            $qb->andWhere('s.category = :category')
               ->setParameter('category', $category);
        }

        // Location filter (case-insensitive, partial match)
        if ($location) {
            $qb->andWhere('LOWER(s.location) LIKE LOWER(:location)')
               ->setParameter('location', '%' . $location . '%');
        }

        // Price range
        if ($minPrice !== null) {
            $qb->andWhere('s.basePrice >= :minPrice')
               ->setParameter('minPrice', $minPrice);
        }

        if ($maxPrice !== null) {
            $qb->andWhere('s.basePrice <= :maxPrice')
               ->setParameter('maxPrice', $maxPrice);
        }

        // Sorting
        switch ($sortBy) {
            case 'date_asc':
                $qb->orderBy('s.createdAt', 'ASC');
                break;
            case 'price_asc':
                $qb->orderBy('s.basePrice', 'ASC');
                break;
            case 'price_desc':
                $qb->orderBy('s.basePrice', 'DESC');
                break;
            case 'name_asc':
                $qb->orderBy('s.name', 'ASC');
                break;
            case 'name_desc':
                $qb->orderBy('s.name', 'DESC');
                break;
            case 'date_desc':
            default:
                $qb->orderBy('s.createdAt', 'DESC');
                break;
        }

        return $qb;
    }

    /**
     * Find similar services using AI-powered recommendation algorithm
     * Scoring: Category match (50%) + Price range ±25% (30%) + Location match (20%)
     */
    public function findSimilar(Service $service, int $limit = 3): array
    {
        $categoryId = $service->getCategory()?->getId();
        $price = $service->getBasePrice();
        $location = $service->getLocation();
        
        // Calculate price range (±25%)
        $minPrice = $price * 0.75;
        $maxPrice = $price * 1.25;

        $qb = $this->createQueryBuilder('s')
            ->where('s.id != :currentId')
            ->andWhere('s.isActive = :active')
            ->setParameter('currentId', $service->getId())
            ->setParameter('active', true);

        // If category exists, prioritize same category
        if ($categoryId) {
            $qb->andWhere('s.category = :category')
               ->setParameter('category', $service->getCategory());
        }

        // Add scoring logic
        $qb->addSelect('
            (CASE WHEN s.category = :categoryParam THEN 50 ELSE 0 END) +
            (CASE WHEN s.basePrice BETWEEN :minPrice AND :maxPrice THEN 30 ELSE 0 END) +
            (CASE WHEN s.location = :location THEN 20 ELSE 0 END) as HIDDEN score
        ')
        ->setParameter('categoryParam', $service->getCategory())
        ->setParameter('minPrice', $minPrice)
        ->setParameter('maxPrice', $maxPrice)
        ->setParameter('location', $location)
        ->orderBy('score', 'DESC')
        ->addOrderBy('s.createdAt', 'DESC')
        ->setMaxResults($limit);

        return $qb->getQuery()->getResult();
    }

    /**
     * Get all prices for services in a specific category
     * Used for price suggestion algorithm
     */
    public function getPricesByCategory(\App\Entity\Category $category): array
    {
        $results = $this->createQueryBuilder('s')
            ->select('s.basePrice')
            ->where('s.category = :category')
            ->andWhere('s.isActive = :active')
            ->setParameter('category', $category)
            ->setParameter('active', true)
            ->getQuery()
            ->getResult();

        // Extract prices from result array
        return array_map(fn($item) => (float) $item['basePrice'], $results);
    }
}
