<?php

namespace App\Service;

use App\Entity\Appointment;
use App\Entity\ArtistProfile;
use App\Entity\User;
use App\Repository\AppointmentRepository;
use App\Repository\ArtistPostRepository;
use App\Repository\ArtistProfileRepository;
use App\Repository\PostCommentRepository;
use App\Repository\PostRatingRepository;
use App\Repository\ServiceCategoryRepository;
use App\Repository\ServiceRepository;
use Doctrine\ORM\EntityManagerInterface;

class DashboardStatsService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly AppointmentRepository $appointmentRepository,
        private readonly ArtistProfileRepository $artistProfileRepository,
        private readonly ArtistPostRepository $artistPostRepository,
        private readonly PostCommentRepository $postCommentRepository,
        private readonly PostRatingRepository $postRatingRepository,
        private readonly ServiceRepository $serviceRepository,
        private readonly ServiceCategoryRepository $serviceCategoryRepository,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function getAdminStats(): array
    {
        $t = $this->timePeriods();

        $appointments = $this->appointmentRepository->getCountsByStatus();

        $appointmentsToday = $this->appointmentRepository->countByStartDatetimeRange(
            $t['todayStart'],
            $t['todayEnd'],
            null,
            [Appointment::STATUS_PENDING, Appointment::STATUS_CONFIRMED, Appointment::STATUS_COMPLETED],
        );

        $appointmentsUpcoming7 = $this->appointmentRepository->countByStartDatetimeRange(
            $t['now'],
            $t['next7End'],
            null,
            [Appointment::STATUS_PENDING, Appointment::STATUS_CONFIRMED],
        );

        $revenueThisMonthCompleted = $this->appointmentRepository->sumServicePriceByStartDatetimeRange(
            $t['monthStart'],
            $t['monthEnd'],
            null,
            [Appointment::STATUS_COMPLETED],
        );

        $revenueTodayCompleted = $this->appointmentRepository->sumServicePriceByStartDatetimeRange(
            $t['todayStart'],
            $t['todayEnd'],
            null,
            [Appointment::STATUS_COMPLETED],
        );

        return [
            'role' => 'admin',
            'time' => $t,
            'counts' => [
                'users' => (int) $this->em->getRepository(User::class)->count([]),
                'artists' => (int) $this->artistProfileRepository->count([]),
                'serviceCategories' => (int) $this->serviceCategoryRepository->count([]),
                'services' => (int) $this->serviceRepository->count([]),
            ],
            'appointments' => [
                'total' => (int) ($appointments['total'] ?? 0),
                'byStatus' => (array) ($appointments['byStatus'] ?? []),
                'today' => $appointmentsToday,
                'upcoming7' => $appointmentsUpcoming7,
                'upcomingList' => $this->appointmentRepository->findUpcoming($t['now'], null, 10),
            ],
            'revenue' => [
                'todayCompleted' => $revenueTodayCompleted,
                'thisMonthCompleted' => $revenueThisMonthCompleted,
            ],
            'blog' => [
                'publishedPosts' => (int) $this->artistPostRepository->count(['isPublished' => true]),
                'draftPosts' => (int) $this->artistPostRepository->count(['isPublished' => false]),
                'pendingComments' => (int) $this->postCommentRepository->count(['isApproved' => false]),
                'commentsLast7Days' => $this->postCommentRepository->countCreatedInRange($t['last7Start'], $t['now']),
                'ratingsLast7Days' => $this->postRatingRepository->countCreatedInRange($t['last7Start'], $t['now']),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getArtistStats(ArtistProfile $artist): array
    {
        $t = $this->timePeriods();

        $appointments = $this->appointmentRepository->getCountsByStatus($artist);

        $appointmentsToday = $this->appointmentRepository->countByStartDatetimeRange(
            $t['todayStart'],
            $t['todayEnd'],
            $artist,
            [Appointment::STATUS_PENDING, Appointment::STATUS_CONFIRMED, Appointment::STATUS_COMPLETED],
        );

        $appointmentsUpcoming7 = $this->appointmentRepository->countByStartDatetimeRange(
            $t['now'],
            $t['next7End'],
            $artist,
            [Appointment::STATUS_PENDING, Appointment::STATUS_CONFIRMED],
        );

        $revenueThisMonthCompleted = $this->appointmentRepository->sumServicePriceByStartDatetimeRange(
            $t['monthStart'],
            $t['monthEnd'],
            $artist,
            [Appointment::STATUS_COMPLETED],
        );

        $user = $artist->getUser();

        return [
            'role' => 'artist',
            'time' => $t,
            'artist' => [
                'id' => $artist->getId(),
                'name' => (string) $artist,
                'ratingAvg' => $user?->getArtistRatingAvg() ?? 0.0,
                'ratingCount' => $user?->getArtistRatingCount() ?? 0,
            ],
            'appointments' => [
                'total' => (int) ($appointments['total'] ?? 0),
                'byStatus' => (array) ($appointments['byStatus'] ?? []),
                'today' => $appointmentsToday,
                'upcoming7' => $appointmentsUpcoming7,
                'upcomingList' => $this->appointmentRepository->findUpcoming($t['now'], $artist, 10),
            ],
            'revenue' => [
                'thisMonthCompleted' => $revenueThisMonthCompleted,
            ],
            'blog' => [
                'publishedPosts' => (int) $this->artistPostRepository->count(['artist' => $artist, 'isPublished' => true]),
                'draftPosts' => (int) $this->artistPostRepository->count(['artist' => $artist, 'isPublished' => false]),
                'commentsLast7Days' => $this->postCommentRepository->countCreatedInRange($t['last7Start'], $t['now'], $artist),
                'ratingsLast7Days' => $this->postRatingRepository->countCreatedInRange($t['last7Start'], $t['now'], $artist),
                'topPosts' => $this->artistPostRepository->findPublishedWithRatingStatsForArtist($artist, 5),
            ],
        ];
    }

    /**
     * @return array{now: \DateTimeImmutable, todayStart: \DateTimeImmutable, todayEnd: \DateTimeImmutable, last7Start: \DateTimeImmutable, next7End: \DateTimeImmutable, monthStart: \DateTimeImmutable, monthEnd: \DateTimeImmutable}
     */
    private function timePeriods(): array
    {
        $now = new \DateTimeImmutable();
        $todayStart = (new \DateTimeImmutable('today'))->setTime(0, 0, 0);
        $todayEnd = $todayStart->modify('+1 day');

        $last7Start = $now->modify('-7 days');
        $next7End = $now->modify('+7 days');

        $monthStart = (new \DateTimeImmutable('first day of this month'))->setTime(0, 0, 0);
        $monthEnd = $monthStart->modify('+1 month');

        return [
            'now' => $now,
            'todayStart' => $todayStart,
            'todayEnd' => $todayEnd,
            'last7Start' => $last7Start,
            'next7End' => $next7End,
            'monthStart' => $monthStart,
            'monthEnd' => $monthEnd,
        ];
    }
}


