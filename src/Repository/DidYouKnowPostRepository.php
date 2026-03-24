<?php

namespace App\Repository;

use App\Entity\DidYouKnowPost;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DidYouKnowPost>
 */
class DidYouKnowPostRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DidYouKnowPost::class);
    }

    /**
     * @return list<DidYouKnowPost>
     */
    public function findPublished(): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.isPublished = true')
            ->orderBy('p.publishedAt', 'DESC')
            ->addOrderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<DidYouKnowPost>
     */
    public function findLatestPublished(int $limit): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.isPublished = true')
            ->orderBy('p.publishedAt', 'DESC')
            ->addOrderBy('p.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function countPublished(): int
    {
        return (int) $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->andWhere('p.isPublished = true')
            ->getQuery()
            ->getSingleScalarResult();
    }
}
