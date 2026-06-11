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
