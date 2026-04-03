<?php

namespace App\Repository;

use App\Entity\ArtistProfile;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ArtistProfile>
 *
 * @method ArtistProfile|null find($id, $lockMode = null, $lockVersion = null)
 * @method ArtistProfile|null findOneBy(array $criteria, array $orderBy = null)
 * @method ArtistProfile[]    findAll()
 * @method ArtistProfile[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ArtistProfileRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ArtistProfile::class);
    }

    /**
     * Eager-loads services so the collection is usable outside a long-lived request (e.g. form PRE_SET_DATA).
     */
    public function findWithServicesById(int $id): ?ArtistProfile
    {
        return $this->createQueryBuilder('a')
            ->addSelect('s')
            ->leftJoin('a.services', 's')
            ->andWhere('a.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findWithServicesForUser(User $user): ?ArtistProfile
    {
        return $this->createQueryBuilder('a')
            ->addSelect('s')
            ->leftJoin('a.services', 's')
            ->andWhere('a.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getOneOrNullResult();
    }
}