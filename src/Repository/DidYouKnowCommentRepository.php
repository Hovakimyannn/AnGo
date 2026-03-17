<?php

namespace App\Repository;

use App\Entity\DidYouKnowPost;
use App\Entity\DidYouKnowComment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DidYouKnowComment>
 */
class DidYouKnowCommentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DidYouKnowComment::class);
    }

    /**
     * @return list<DidYouKnowComment>
     */
    public function findApprovedForPost(DidYouKnowPost $post, int $limit = 100): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.post = :post')
            ->andWhere('c.isApproved = true')
            ->setParameter('post', $post)
            ->orderBy('c.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
