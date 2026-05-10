<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    /** @return User[] */
    public function findForAdmin(?string $search, ?string $role, ?string $status, string $sort): array
    {
        $qb = $this->createQueryBuilder('u');

        if ($search) {
            $qb->andWhere('LOWER(u.email) LIKE :search')
               ->setParameter('search', '%' . mb_strtolower($search) . '%');
        }

        if ($role === 'admin') {
            $qb->andWhere('u.roles LIKE :adminRole')->setParameter('adminRole', '%ROLE_ADMIN%');
        } elseif ($role === 'host') {
            $qb->andWhere('u.roles LIKE :hostRole')
               ->andWhere('u.roles NOT LIKE :adminRole')
               ->setParameter('hostRole', '%ROLE_HOST%')
               ->setParameter('adminRole', '%ROLE_ADMIN%');
        } elseif ($role === 'guest') {
            $qb->andWhere('u.roles NOT LIKE :adminRole')
               ->andWhere('u.roles NOT LIKE :hostRole')
               ->setParameter('adminRole', '%ROLE_ADMIN%')
               ->setParameter('hostRole', '%ROLE_HOST%');
        }

        if ($status === 'active') {
            $qb->andWhere('u.accountStatus = :s')->setParameter('s', User::STATUS_ACTIVE);
        } elseif ($status === 'suspended') {
            $qb->andWhere('u.accountStatus = :s')->setParameter('s', User::STATUS_SUSPENDED);
        } elseif ($status === 'banned') {
            $qb->andWhere('u.accountStatus = :s')->setParameter('s', User::STATUS_BANNED);
        } elseif ($status === 'inactive') {
            $qb->andWhere('u.accountStatus != :s OR u.isVerified = false')
               ->setParameter('s', User::STATUS_ACTIVE);
        }

        $this->applySort($qb, $sort);

        return $qb->getQuery()->getResult();
    }

    public function getAdminStats(): array
    {
        $total = (int) $this->createQueryBuilder('u')->select('COUNT(u.id)')->getQuery()->getSingleScalarResult();

        $active = (int) $this->createQueryBuilder('u')->select('COUNT(u.id)')
            ->andWhere('u.accountStatus = :s')->setParameter('s', User::STATUS_ACTIVE)
            ->getQuery()->getSingleScalarResult();

        $inactive = (int) $this->createQueryBuilder('u')->select('COUNT(u.id)')
            ->andWhere('u.accountStatus != :s OR u.isVerified = false')->setParameter('s', User::STATUS_ACTIVE)
            ->getQuery()->getSingleScalarResult();

        $suspended = (int) $this->createQueryBuilder('u')->select('COUNT(u.id)')
            ->andWhere('u.accountStatus = :s')->setParameter('s', User::STATUS_SUSPENDED)
            ->getQuery()->getSingleScalarResult();

        $banned = (int) $this->createQueryBuilder('u')->select('COUNT(u.id)')
            ->andWhere('u.accountStatus = :s')->setParameter('s', User::STATUS_BANNED)
            ->getQuery()->getSingleScalarResult();

        $admins = (int) $this->createQueryBuilder('u')->select('COUNT(u.id)')
            ->andWhere('u.roles LIKE :r')->setParameter('r', '%ROLE_ADMIN%')
            ->getQuery()->getSingleScalarResult();

        $hosts = (int) $this->createQueryBuilder('u')->select('COUNT(u.id)')
            ->andWhere('u.roles LIKE :r')->andWhere('u.roles NOT LIKE :a')
            ->setParameter('r', '%ROLE_HOST%')->setParameter('a', '%ROLE_ADMIN%')
            ->getQuery()->getSingleScalarResult();

        $guests = max($total - $admins - $hosts, 0);

        return [
            'total'     => $total,
            'active'    => $active,
            'inactive'  => $inactive,
            'suspended' => $suspended,
            'banned'    => $banned,
            'flagged'   => $suspended, // no suspicious score — use suspended as proxy
            'admins'    => $admins,
            'hosts'     => $hosts,
            'guests'    => $guests,
        ];
    }

    /** @return User[] — returns suspended users as proxy for "suspicious" */
    public function findMostSuspiciousUsers(int $limit = 10): array
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.accountStatus = :s')
            ->setParameter('s', User::STATUS_SUSPENDED)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function countPendingReactivationRequests(): int
    {
        // No reactivation columns in remote DB — return 0
        return 0;
    }

    /** @return User[] */
    public function findPendingReactivationRequests(int $limit = 10): array
    {
        // No reactivation columns in remote DB — return empty
        return [];
    }

    /** @return User[] */
    public function findFaceLoginCandidates(): array
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.isVerified = :verified')
            ->andWhere('u.selfieImage IS NOT NULL')
            ->andWhere('u.accountStatus = :s')
            ->setParameter('verified', true)
            ->setParameter('s', User::STATUS_ACTIVE)
            ->getQuery()
            ->getResult();
    }

    private function applySort(\Doctrine\ORM\QueryBuilder $qb, string $sort): void
    {
        match ($sort) {
            'email_asc'  => $qb->orderBy('u.email', 'ASC'),
            'email_desc' => $qb->orderBy('u.email', 'DESC'),
            'status'     => $qb->orderBy('u.accountStatus', 'ASC')->addOrderBy('u.email', 'ASC'),
            'oldest'     => $qb->orderBy('u.id', 'ASC'),
            default      => $qb->orderBy('u.id', 'DESC'),
        };
    }
}
