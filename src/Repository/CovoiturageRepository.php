<?php

namespace App\Repository;

use App\Entity\Covoiturage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Covoiturage>
 */
class CovoiturageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Covoiturage::class);
    }

    //    /**
    //     * @return Covoiturage[] Returns an array of Covoiturage objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('c.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Covoiturage
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }

    /**
     * @return Covoiturage[]
     */
    public function search(?string $depart, ?string $destination): array
    {
        $qb = $this->createQueryBuilder('c')
            ->orderBy('c.dateDepart', 'ASC');

        if ($depart !== null && $depart !== '') {
            $qb->andWhere('LOWER(c.depart) LIKE :depart')
                ->setParameter('depart', '%' . mb_strtolower(trim($depart)) . '%');
        }

        if ($destination !== null && $destination !== '') {
            $qb->andWhere('LOWER(c.destination) LIKE :destination')
                ->setParameter('destination', '%' . mb_strtolower(trim($destination)) . '%');
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @return Covoiturage[]
     */
    public function searchWithSort(?string $search, ?string $depart, ?string $destination, string $sort): array
    {
        $qb = $this->createQueryBuilder('c');

        if ($search !== null && $search !== '') {
            $normalized = '%' . mb_strtolower(trim($search)) . '%';
            $qb->andWhere('LOWER(c.depart) LIKE :search OR LOWER(c.destination) LIKE :search')
                ->setParameter('search', $normalized);
        }

        if ($depart !== null && $depart !== '') {
            $qb->andWhere('LOWER(c.depart) LIKE :depart')
                ->setParameter('depart', '%' . mb_strtolower(trim($depart)) . '%');
        }

        if ($destination !== null && $destination !== '') {
            $qb->andWhere('LOWER(c.destination) LIKE :destination')
                ->setParameter('destination', '%' . mb_strtolower(trim($destination)) . '%');
        }

        switch ($sort) {
            case 'date_asc':
                $qb->orderBy('c.dateDepart', 'ASC');
                break;
            case 'places_desc':
                $qb->orderBy('c.places', 'DESC');
                break;
            case 'places_asc':
                $qb->orderBy('c.places', 'ASC');
                break;
            case 'depart_asc':
                $qb->orderBy('c.depart', 'ASC');
                break;
            case 'depart_desc':
                $qb->orderBy('c.depart', 'DESC');
                break;
            case 'destination_asc':
                $qb->orderBy('c.destination', 'ASC');
                break;
            case 'destination_desc':
                $qb->orderBy('c.destination', 'DESC');
                break;
            case 'date_desc':
            default:
                $qb->orderBy('c.dateDepart', 'DESC');
                break;
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @return Covoiturage[]
     */
    public function findForAdmin(?string $search, ?string $depart, ?string $destination, string $sort): array
    {
        $qb = $this->createQueryBuilder('c')
            ->leftJoin('c.conducteur', 'u')
            ->addSelect('u');

        if ($search !== null && $search !== '') {
            $normalized = '%' . mb_strtolower(trim($search)) . '%';
            $qb->andWhere('LOWER(c.depart) LIKE :search OR LOWER(c.destination) LIKE :search OR LOWER(u.email) LIKE :search')
                ->setParameter('search', $normalized);
        }

        if ($depart !== null && $depart !== '') {
            $qb->andWhere('LOWER(c.depart) LIKE :depart')
                ->setParameter('depart', '%' . mb_strtolower(trim($depart)) . '%');
        }

        if ($destination !== null && $destination !== '') {
            $qb->andWhere('LOWER(c.destination) LIKE :destination')
                ->setParameter('destination', '%' . mb_strtolower(trim($destination)) . '%');
        }

        switch ($sort) {
            case 'date_asc':
                $qb->orderBy('c.dateDepart', 'ASC');
                break;
            case 'places_desc':
                $qb->orderBy('c.places', 'DESC');
                break;
            case 'places_asc':
                $qb->orderBy('c.places', 'ASC');
                break;
            case 'depart_asc':
                $qb->orderBy('c.depart', 'ASC');
                break;
            case 'depart_desc':
                $qb->orderBy('c.depart', 'DESC');
                break;
            case 'destination_asc':
                $qb->orderBy('c.destination', 'ASC');
                break;
            case 'destination_desc':
                $qb->orderBy('c.destination', 'DESC');
                break;
            case 'date_desc':
            default:
                $qb->orderBy('c.dateDepart', 'DESC');
                break;
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @return array{
     *   total:int,
     *   by_depart: array<int, array{city:string,total:int}>,
     *   by_destination: array<int, array{city:string,total:int}>
     * }
     */
    public function getStatsByCity(): array
    {
        $departRows = $this->createQueryBuilder('c')
            ->select('c.depart AS city, COUNT(c.id) AS total')
            ->groupBy('c.depart')
            ->orderBy('total', 'DESC')
            ->getQuery()
            ->getArrayResult();

        $destinationRows = $this->createQueryBuilder('c')
            ->select('c.destination AS city, COUNT(c.id) AS total')
            ->groupBy('c.destination')
            ->orderBy('total', 'DESC')
            ->getQuery()
            ->getArrayResult();

        return [
            'total' => $this->count([]),
            'by_depart' => array_map(
                static fn (array $row): array => ['city' => (string) $row['city'], 'total' => (int) $row['total']],
                $departRows
            ),
            'by_destination' => array_map(
                static fn (array $row): array => ['city' => (string) $row['city'], 'total' => (int) $row['total']],
                $destinationRows
            ),
        ];
    }
}
