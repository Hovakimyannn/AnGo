<?php

namespace App\Controller\Api;

use App\Entity\Appointment;
use App\Repository\ArtistProfileRepository;
use App\Repository\ServiceRepository;
use App\Service\BookingService;
use App\Service\AppointmentMailer;
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
        private EntityManagerInterface $em,
        private AppointmentMailer $appointmentMailer,
    ) {}

    // 0. Get All Artists
    #[Route('/artists', methods: ['GET'])]
    public function getAllArtists(): JsonResponse
    {
        $artists = $this->artistRepository->findAll();

        $data = [];
        foreach ($artists as $artist) {
            $data[] = [
                'id' => $artist->getId(),
                'name' => (string)$artist->getUser(),
                'photo' => $artist->getPhotoUrl(),
            ];
        }

        return $this->json($data);
    }

    // 0.05 Get All Services (for global booking flow)
    #[Route('/services', methods: ['GET'])]
    public function getAllServices(): JsonResponse
    {
        $services = $this->serviceRepository->findAll();
        usort($services, static function ($a, $b) {
            $ac = (string) $a->getCategory();
            $bc = (string) $b->getCategory();
            if ($ac === $bc) {
                return strcmp((string) $a->getName(), (string) $b->getName());
            }
            return strcmp($ac, $bc);
        });

        $data = [];
        foreach ($services as $service) {
            $data[] = [
                'id' => $service->getId(),
                'name' => $service->getName(),
                'category' => $service->getCategory(),
                'price' => $service->getPrice(),
                'durationMinutes' => $service->getDurationMinutes(),
            ];
        }

        return $this->json($data);
    }

    // 0.1 Get Services by Artist
    #[Route('/services/{artistId}', methods: ['GET'])]
    public function getServicesByArtist(int $artistId): JsonResponse
    {
        $artist = $this->artistRepository->find($artistId);
        if (!$artist) {
            return $this->json(['error' => 'Not found'], 404);
        }

        $services = $artist->getServices()->toArray();
        usort($services, static fn($a, $b) => strcmp((string)$a->getName(), (string)$b->getName()));

        $data = [];
        foreach ($services as $service) {
            $data[] = [
                'id' => $service->getId(),
                'name' => $service->getName(),
                'category' => $service->getCategory(),
                'price' => $service->getPrice(),
                'durationMinutes' => $service->getDurationMinutes(),
            ];
        }

        return $this->json($data);
    }

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

        // Email notification (best-effort; do not fail booking if email fails)
        $this->appointmentMailer->sendBookingCreated($appointment);

        return $this->json(['success' => true, 'id' => $appointment->getId()]);
    }
}