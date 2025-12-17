<?php

namespace App\Repository;

use App\Entity\HomePageSettings;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<HomePageSettings>
 *
 * @method HomePageSettings|null find($id, $lockMode = null, $lockVersion = null)
 * @method HomePageSettings|null findOneBy(array $criteria, array $orderBy = null)
 * @method HomePageSettings[]    findAll()
 * @method HomePageSettings[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class HomePageSettingsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, HomePageSettings::class);
    }
}


