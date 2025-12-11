<?php

namespace App\Repository;

use App\Entity\Appointment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Appointment>
 */
class AppointmentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Appointment::class);
    }

    // Ays fukcian mez petq kga hajord qaylum՝ Book anelu jamanak,
    // vorpeszi gtnenq tvyal orva bolor zbaxvac jamery
    public function findTakenSlots($artist, \DateTimeInterface $startOfDay, \DateTimeInterface $endOfDay)
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.artist = :artist')
            ->andWhere('a.status != :canceled') // Hashvi chenq arnum chexarkvacnery
            ->andWhere('a.startDatetime >= :start')
            ->andWhere('a.startDatetime < :end')
            ->setParameter('artist', $artist)
            ->setParameter('canceled', Appointment::STATUS_CANCELED)
            ->setParameter('start', $startOfDay)
            ->setParameter('end', $endOfDay)
            ->orderBy('a.startDatetime', 'ASC')
            ->getQuery()
            ->getResult();
    }
}