<?php

namespace App\Controller\Api;

use App\Entity\Appointment;
use App\Repository\ArtistProfileRepository;
use App\Repository\ServiceRepository;
use App\Service\BookingService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/booking')]
class BookingController extends AbstractController
{
    public function __construct(
        private BookingService $bookingService,
        private ArtistProfileRepository $artistRepository,
        private ServiceRepository $serviceRepository,
        private EntityManagerInterface $em
    ) {}

    // 1. Get Artists by Service
    #[Route('/artists/{serviceId}', methods: ['GET'])]
    public function getArtists(int $serviceId): JsonResponse
    {
        // Gtnel ayn artistnerin, voronq matucum en ays carayutyuny
        // Ays query-n kareli e tanel repository, bayc aystex el kashxati
        $artists = $this->artistRepository->createQueryBuilder('a')
            ->join('a.services', 's')
            ->where('s.id = :serviceId')
            ->setParameter('serviceId', $serviceId)
            ->getQuery()
            ->getResult();

        $data = [];
        foreach ($artists as $artist) {
            $data[] = [
                'id' => $artist->getId(),
                'name' => (string)$artist->getUser(), // __toString() kashxati
                'photo' => $artist->getPhotoUrl()
            ];
        }

        return $this->json($data);
    }

    // 2. Get Available Slots
    #[Route('/slots', methods: ['GET'])]
    public function getSlots(Request $request): JsonResponse
    {
        $artistId = $request->query->get('artistId');
        $serviceId = $request->query->get('serviceId');
        $dateStr = $request->query->get('date'); // Y-m-d format

        if (!$artistId || !$serviceId || !$dateStr) {
            return $this->json(['error' => 'Missing parameters'], 400);
        }

        $artist = $this->artistRepository->find($artistId);
        $service = $this->serviceRepository->find($serviceId);
        $date = new \DateTime($dateStr);

        if (!$artist || !$service) {
            return $this->json(['error' => 'Not found'], 404);
        }

        $slots = $this->bookingService->getAvailableSlots($artist, $date, $service);

        return $this->json(['slots' => $slots]);
    }

    // 3. Create Booking
    #[Route('/book', methods: ['POST'])]
    public function createBooking(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $artist = $this->artistRepository->find($data['artistId']);
        $service = $this->serviceRepository->find($data['serviceId']);
        $date = new \DateTime($data['date'] . ' ' . $data['time']);

        if (!$artist || !$service) {
            return $this->json(['error' => 'Invalid data'], 400);
        }

        $appointment = new Appointment();
        $appointment->setArtist($artist);
        $appointment->setService($service);
        $appointment->setClientName($data['name']);
        $appointment->setClientPhone($data['phone']);
        $appointment->setClientEmail($data['email']);

        $appointment->setStartDatetime($date);

        // Hashvarkel avarty
        $endDate = (clone $date)->modify("+{$service->getDurationMinutes()} minutes");
        $appointment->setEndDatetime($endDate);

        $appointment->setStatus(Appointment::STATUS_PENDING);

        $this->em->persist($appointment);
        $this->em->flush();

        // Aystex kareli e avelacnel Email Notification logic

        return $this->json(['success' => true, 'id' => $appointment->getId()]);
    }
}