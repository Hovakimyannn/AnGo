<?php

namespace App\Repository;

use App\Entity\ArtistProfile;
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
}