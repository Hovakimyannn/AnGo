<?php

namespace App\Repository;

use App\Entity\Appointment;
use App\Entity\ArtistProfile;
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

    /**
     * Returns appointment counts grouped by status.
     *
     * @return array{total: int, byStatus: array<string, int>}
     */
    public function getCountsByStatus(?ArtistProfile $artist = null): array
    {
        $qb = $this->createQueryBuilder('a')
            ->select('a.status AS status')
            ->addSelect('COUNT(a.id) AS cnt')
            ->groupBy('a.status');

        if ($artist) {
            $qb->andWhere('a.artist = :artist')
                ->setParameter('artist', $artist);
        }

        $rows = $qb->getQuery()->getArrayResult();

        // Ensure known statuses exist with 0 values so Twig can safely render them.
        $byStatus = [
            Appointment::STATUS_PENDING => 0,
            Appointment::STATUS_CONFIRMED => 0,
            Appointment::STATUS_COMPLETED => 0,
            Appointment::STATUS_CANCELED => 0,
        ];

        foreach ($rows as $row) {
            $status = (string) ($row['status'] ?? '');
            $cnt = (int) ($row['cnt'] ?? 0);
            if ($status === '') {
                continue;
            }
            $byStatus[$status] = $cnt;
        }

        return [
            'total' => array_sum($byStatus),
            'byStatus' => $byStatus,
        ];
    }

    public function countByStartDatetimeRange(
        \DateTimeInterface $from,
        \DateTimeInterface $to,
        ?ArtistProfile $artist = null,
        ?array $statuses = null,
    ): int {
        if (is_array($statuses) && $statuses === []) {
            return 0;
        }

        $qb = $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->andWhere('a.startDatetime >= :from')
            ->andWhere('a.startDatetime < :to')
            ->setParameter('from', $from)
            ->setParameter('to', $to);

        if ($artist) {
            $qb->andWhere('a.artist = :artist')
                ->setParameter('artist', $artist);
        }

        if (is_array($statuses)) {
            $qb->andWhere('a.status IN (:statuses)')
                ->setParameter('statuses', $statuses);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    public function sumServicePriceByStartDatetimeRange(
        \DateTimeInterface $from,
        \DateTimeInterface $to,
        ?ArtistProfile $artist = null,
        ?array $statuses = null,
    ): float {
        if (is_array($statuses) && $statuses === []) {
            return 0.0;
        }

        $qb = $this->createQueryBuilder('a')
            ->select('COALESCE(SUM(s.price), 0) AS total')
            ->join('a.service', 's')
            ->andWhere('a.startDatetime >= :from')
            ->andWhere('a.startDatetime < :to')
            ->setParameter('from', $from)
            ->setParameter('to', $to);

        if ($artist) {
            $qb->andWhere('a.artist = :artist')
                ->setParameter('artist', $artist);
        }

        if (is_array($statuses)) {
            $qb->andWhere('a.status IN (:statuses)')
                ->setParameter('statuses', $statuses);
        }

        return (float) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @return list<Appointment>
     */
    public function findUpcoming(
        \DateTimeInterface $from,
        ?ArtistProfile $artist = null,
        int $limit = 10,
        array $statuses = [Appointment::STATUS_PENDING, Appointment::STATUS_CONFIRMED],
    ): array {
        $qb = $this->createQueryBuilder('a')
            ->addSelect('s', 'ap', 'u')
            ->join('a.service', 's')
            ->join('a.artist', 'ap')
            ->join('ap.user', 'u')
            ->andWhere('a.startDatetime >= :from')
            ->setParameter('from', $from)
            ->orderBy('a.startDatetime', 'ASC')
            ->setMaxResults($limit);

        if ($artist) {
            $qb->andWhere('a.artist = :artist')
                ->setParameter('artist', $artist);
        }

        if ($statuses !== []) {
            $qb->andWhere('a.status IN (:statuses)')
                ->setParameter('statuses', $statuses);
        }

        return $qb->getQuery()->getResult();
    }
}