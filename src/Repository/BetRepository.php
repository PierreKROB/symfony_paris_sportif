<?php

namespace App\Repository;

use App\Entity\Bet;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class BetRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Bet::class);
    }

    /**
     * Retourne une page de paris pour un utilisateur donné.
     */
    public function findPaginatedForUser(User $user, int $page, int $perPage = 10): array
    {
        $qb = $this->createQueryBuilder('b')
            ->where('b.user = :user')
            ->setParameter('user', $user)
            ->orderBy('b.createdAt', 'DESC');

        $total = (int) (clone $qb)
            ->select('COUNT(b.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $items = $qb
            ->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage)
            ->getQuery()
            ->getResult();

        return [
            'items'       => $items,
            'total'       => $total,
            'currentPage' => $page,
            'totalPages'  => max(1, (int) ceil($total / $perPage)),
            'perPage'     => $perPage,
        ];
    }

    /**
     * Retourne une page de tous les paris (vue admin).
     */
    public function findAllPaginated(int $page, int $perPage = 15): array
    {
        $qb = $this->createQueryBuilder('b')
            ->orderBy('b.createdAt', 'DESC');

        $total = (int) (clone $qb)
            ->select('COUNT(b.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $items = $qb
            ->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage)
            ->getQuery()
            ->getResult();

        return [
            'items'       => $items,
            'total'       => $total,
            'currentPage' => $page,
            'totalPages'  => max(1, (int) ceil($total / $perPage)),
            'perPage'     => $perPage,
        ];
    }

    public function getTotalBetForUserSince(User $user, \DateTime $since): float
    {
        $result = $this->createQueryBuilder('b')
            ->select('SUM(b.amount)')
            ->where('b.user = :user')
            ->andWhere('b.createdAt >= :since')
            ->andWhere('b.status != :cancelled')
            ->setParameter('user', $user)
            ->setParameter('since', $since)
            ->setParameter('cancelled', Bet::STATUS_CANCELLED)
            ->getQuery()
            ->getSingleScalarResult();

        return (float) ($result ?? 0);
    }
}
