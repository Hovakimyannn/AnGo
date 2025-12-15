<?php

namespace App\Controller;

use App\Entity\ArtistProfile;
use App\Repository\ArtistProfileRepository;
use App\Repository\ArtistPostRepository;
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
        $categoryLabels = [
            'hair' => 'Վարսահարդարներ',
            'nails' => 'Մատնահարդարներ',
            'makeup' => 'Դիմահարդարներ',
        ];

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
            'category' => $categoryLabels[$category] ?? 'Մեր վարպետները',
            'services' => $this->serviceRepository->findAll(), // <--- FIX: Uxarkum enq carayutyunnery
        ]);
    }

    #[Route('/artist/{id}', name: 'app_artist_show')]
    public function show(ArtistProfile $artist, Request $request, ArtistPostRepository $postRepository): Response
    {
        $category = $request->query->get('category');
        $serviceId = $request->query->getInt('service') ?: null;

        $categoryLabels = [
            'hair' => 'Վարսահարդարում',
            'nails' => 'Մատնահարդարում',
            'makeup' => 'Դիմահարդարում',
        ];

        // Only show/allow categories that this artist actually provides (based on their services)
        $availableCategories = [];
        foreach ($artist->getServices() as $svc) {
            $cat = $svc->getCategory();
            if ($cat && !in_array($cat, $availableCategories, true)) {
                $availableCategories[] = $cat;
            }
        }
        $order = ['hair' => 1, 'makeup' => 2, 'nails' => 3];
        usort($availableCategories, static fn (string $a, string $b) => ($order[$a] ?? 99) <=> ($order[$b] ?? 99));

        if ($category && !in_array($category, $availableCategories, true)) {
            $category = null;
        }

        // Safety: only allow filtering by a service that belongs to this artist
        if ($serviceId) {
            $allowed = false;
            foreach ($artist->getServices() as $s) {
                if ($s->getId() === $serviceId) {
                    $allowed = true;
                    break;
                }
            }
            if (!$allowed) {
                $serviceId = null;
            }
        }

        $posts = $postRepository->findPublishedForArtist($artist, $category ?: null, $serviceId);

        return $this->render('artist/show.html.twig', [
            'artist' => $artist,
            'services' => $this->serviceRepository->findAll(), // <--- FIX: Aystex nuynpes
            'posts' => $posts,
            'selectedCategory' => $category,
            'selectedServiceId' => $serviceId,
            'availableCategories' => $availableCategories,
            'categoryLabels' => $categoryLabels,
        ]);
    }
}