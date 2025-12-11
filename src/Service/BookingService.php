<?php

namespace App\Service;

use App\Entity\Appointment;
use App\Entity\ArtistProfile;
use App\Entity\Service;
use App\Repository\AppointmentRepository;
use App\Repository\AvailabilityRepository;

class BookingService
{
    public function __construct(
        private AppointmentRepository $appointmentRepository,
        private AvailabilityRepository $availabilityRepository
    ) {}

    public function getAvailableSlots(ArtistProfile $artist, \DateTimeInterface $date, Service $service): array
    {
        // 1. Gtnel ashxatanqayin jamy ayd orva hamar
        // PHP-um 'N' formaty talis e 1 (Mon) - 7 (Sun)
        $dayOfWeek = $date->format('N');
        $availability = $this->availabilityRepository->findOneBy([
            'artist' => $artist,
            'dayOfWeek' => $dayOfWeek,
            'isDayOff' => false
        ]);

        // Ete ayd ory chi ashxatum, veradardzru datark
        if (!$availability) {
            return [];
        }

        $startWork = $availability->getStartTime(); // Orinak 10:00
        $endWork = $availability->getEndTime();     // Orinak 20:00
        $serviceDuration = $service->getDurationMinutes(); // Orinak 60 rope

        // 2. Gtnel ayd orva bolor grancvac patvernery
        // Menq petq e stexcenq Datetime objecner ayd orva skzbi ev verji hamar
        $startOfDay = (clone $date)->setTime(0, 0, 0);
        $endOfDay = (clone $date)->setTime(23, 59, 59);

        $takenAppointments = $this->appointmentRepository->findTakenSlots($artist, $startOfDay, $endOfDay);

        // 3. Hashvarkel azat slotery (Iteracia enq anum 30 ropen mek)
        $slots = [];
        // Sksum enq ashxatanqayin jamic (set date to today + work start time)
        $cursor = (clone $date)->setTime(
            (int)$startWork->format('H'),
            (int)$startWork->format('i')
        );

        // Verjy՝ ashxatanqi avart
        $limit = (clone $date)->setTime(
            (int)$endWork->format('H'),
            (int)$endWork->format('i')
        );

        while ($cursor < $limit) {
            // Hashvenq ays sloti avarty
            $slotEnd = (clone $cursor)->modify("+{$serviceDuration} minutes");

            // Ete sloti avarty ancnum e ashxatanqayin jamic, kangnir
            if ($slotEnd > $limit) {
                break;
            }

            // Stugenq՝ ardyoq ays jamy hatvum e voreve patveri het
            if (!$this->isOverlapping($cursor, $slotEnd, $takenAppointments)) {
                $slots[] = $cursor->format('H:i');
            }

            // Qayly: 30 rope (Karox eq poxel 15-i kam 60-i)
            $cursor->modify('+30 minutes');
        }

        return $slots;
    }

    private function isOverlapping(\DateTimeInterface $start, \DateTimeInterface $end, array $appointments): bool
    {
        foreach ($appointments as $appointment) {
            // Ete nor patveri skizby ynkac e hin patveri mej
            // KAM nor patveri verjy ynkac e hin patveri mej
            if (
                ($start >= $appointment->getStartDatetime() && $start < $appointment->getEndDatetime()) ||
                ($end > $appointment->getStartDatetime() && $end <= $appointment->getEndDatetime()) ||
                ($start <= $appointment->getStartDatetime() && $end >= $appointment->getEndDatetime())
            ) {
                return true;
            }
        }
        return false;
    }
}