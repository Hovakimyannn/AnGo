<?php

namespace App\Controller;

use App\Entity\ArtistProfile;
use App\Repository\ArtistProfileRepository;
use App\Repository\ServiceRepository; // <--- Avelacvac e
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ArtistController extends AbstractController
{
    // Avelacnum enq ServiceRepository-n
    public function __construct(
        private ServiceRepository $serviceRepository
    ) {}

    #[Route('/artists', name: 'app_artists')]
    public function index(Request $request, ArtistProfileRepository $artistRepository): Response
    {
        $category = $request->query->get('category');

        if ($category) {
            $artists = $artistRepository->createQueryBuilder('a')
                ->join('a.services', 's')
                ->where('s.category = :category')
                ->setParameter('category', $category)
                ->getQuery()
                ->getResult();
        } else {
            $artists = $artistRepository->findAll();
        }

        return $this->render('artist/index.html.twig', [
            'artists' => $artists,
            'category' => $category ? ucfirst($category) : 'Mer Varpetnery',
            'services' => $this->serviceRepository->findAll(), // <--- FIX: Uxarkum enq carayutyunnery
        ]);
    }

    #[Route('/artist/{id}', name: 'app_artist_show')]
    public function show(ArtistProfile $artist): Response
    {
        return $this->render('artist/show.html.twig', [
            'artist' => $artist,
            'services' => $this->serviceRepository->findAll(), // <--- FIX: Aystex nuynpes
        ]);
    }
}