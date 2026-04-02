<?php

namespace App\Repository;

use App\Entity\DidYouKnowPost;
use App\Entity\DidYouKnowRating;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DidYouKnowRating>
 */
class DidYouKnowRatingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DidYouKnowRating::class);
    }

    /**
     * @return array{avg: float, count: int}
     */
    public function getStatsForPost(DidYouKnowPost $post): array
    {
        $row = $this->createQueryBuilder('r')
            ->select('COALESCE(AVG(r.value), 0) AS avgRating')
            ->addSelect('COUNT(r.id) AS ratingCount')
            ->andWhere('r.post = :post')
            ->setParameter('post', $post)
            ->getQuery()
            ->getOneOrNullResult();

        return [
            'avg' => isset($row['avgRating']) ? (float) $row['avgRating'] : 0.0,
            'count' => isset($row['ratingCount']) ? (int) $row['ratingCount'] : 0,
        ];
    }

    public function findOneByPostAndUser(DidYouKnowPost $post, User $user): ?DidYouKnowRating
    {
        return $this->findOneBy(['post' => $post, 'user' => $user]);
    }
}
