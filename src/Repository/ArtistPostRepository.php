<?php

namespace App\Repository;

use App\Entity\ArtistPost;
use App\Entity\ArtistProfile;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ArtistPost>
 */
class ArtistPostRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ArtistPost::class);
    }

    /**
     * Returns rows like: ['post' => ArtistPost, 'avgRating' => string|float|null, 'ratingCount' => string|int]
     *
     * @return array<int, array{post: ArtistPost, avgRating: mixed, ratingCount: mixed}>
     */
    public function findPublishedWithRatingStatsForArtist(ArtistProfile $artist, int $limit = 5): array
    {
        return $this->createQueryBuilder('p')
            ->select('p AS post')
            ->addSelect('COALESCE(AVG(r.value), 0) AS avgRating')
            ->addSelect('COUNT(r.id) AS ratingCount')
            ->leftJoin('p.ratings', 'r')
            ->andWhere('p.artist = :artist')
            ->andWhere('p.isPublished = true')
            ->setParameter('artist', $artist)
            ->groupBy('p.id')
            ->orderBy('p.publishedAt', 'DESC')
            ->addOrderBy('p.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<ArtistPost>
     */
    public function findPublishedForArtist(ArtistProfile $artist, ?string $category = null, ?int $serviceId = null): array
    {
        $qb = $this->createQueryBuilder('p')
            ->andWhere('p.artist = :artist')
            ->andWhere('p.isPublished = true')
            ->setParameter('artist', $artist)
            ->orderBy('p.publishedAt', 'DESC')
            ->addOrderBy('p.createdAt', 'DESC');

        if ($category || $serviceId) {
            $qb->leftJoin('p.services', 's');
        }
        if ($category) {
            $qb->andWhere('s.category = :cat')
                ->setParameter('cat', $category);
        }
        if ($serviceId) {
            $qb->andWhere('s.id = :sid')
                ->setParameter('sid', $serviceId);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @return list<ArtistPost>
     */
    public function findPublishedByCategory(?string $category = null, ?int $serviceId = null): array
    {
        $qb = $this->createQueryBuilder('p')
            ->andWhere('p.isPublished = true')
            ->orderBy('p.publishedAt', 'DESC')
            ->addOrderBy('p.createdAt', 'DESC');

        if ($category || $serviceId) {
            $qb->join('p.services', 's');
        }
        if ($category) {
            $qb->andWhere('s.category = :cat')->setParameter('cat', $category);
        }
        if ($serviceId) {
            $qb->andWhere('s.id = :sid')->setParameter('sid', $serviceId);
        }

        return $qb->getQuery()->getResult();
    }
}


