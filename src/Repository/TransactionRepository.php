<?php

namespace App\Repository;

use App\Entity\Transaction;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class TransactionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Transaction::class);
    }

    public function getTotalDepositForUserSince(User $user, \DateTime $since): float
    {
        return (float) ($this->createQueryBuilder('t')
            ->select('SUM(t.amount)')
            ->where('t.user = :user')
            ->andWhere('t.type = :type')
            ->andWhere('t.createdAt >= :since')
            ->setParameter('user', $user)
            ->setParameter('type', Transaction::TYPE_DEPOSIT)
            ->setParameter('since', $since)
            ->getQuery()
            ->getSingleScalarResult() ?? 0);
    }
}
