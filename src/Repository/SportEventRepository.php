<?php

namespace App\Repository;

use App\Entity\SportEvent;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SportEvent>
 */
class SportEventRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SportEvent::class);
    }

    /**
     * Retourne une page d'événements avec les infos nécessaires à la pagination.
     *
     * @param array<string,string> $criteria  Ex: ['status' => 'PUBLIE']
     * @param array<string,string> $orderBy   Ex: ['startsAt' => 'ASC']
     */
    public function findPaginated(int $page, int $perPage = 10, array $criteria = [], array $orderBy = ['createdAt' => 'DESC']): array
    {
        $qb = $this->createQueryBuilder('e');

        foreach ($criteria as $field => $value) {
            $qb->andWhere("e.{$field} = :{$field}")->setParameter($field, $value);
        }
        foreach ($orderBy as $field => $direction) {
            $qb->addOrderBy("e.{$field}", $direction);
        }

        $total = (int) (clone $qb)
            ->select('COUNT(e.id)')
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
}
