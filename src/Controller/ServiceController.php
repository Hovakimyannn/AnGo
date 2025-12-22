<?php

namespace App\Controller;

use App\Entity\Service;
use App\Repository\ArtistPostRepository;
use App\Repository\ServiceRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

final class ServiceController extends AbstractController
{
    #[Route('/services', name: 'app_service_index', defaults: ['category' => null], methods: ['GET'])]
    #[Route('/services/{category}', name: 'app_service_category', requirements: ['category' => 'hair|nails|makeup'], methods: ['GET'])]
    public function index(
        ?string $category = null,
        ServiceRepository $serviceRepository,
        SluggerInterface $slugger,
    ): Response {
        $categoryLabels = [
            'hair' => 'Վարսահարդարում',
            'nails' => 'Մատնահարդարում',
            'makeup' => 'Դիմահարդարում',
        ];

        $services = $category
            ? $serviceRepository->findBy(['category' => $category], ['name' => 'ASC'])
            : $serviceRepository->findBy([], ['category' => 'ASC', 'name' => 'ASC']);

        $serviceCards = [];
        foreach ($services as $s) {
            $serviceCards[] = [
                'service' => $s,
                'slug' => $this->slugForService($s, $slugger),
            ];
        }

        $grouped = [];
        foreach ($serviceCards as $card) {
            $k = (string) ($card['service']->getCategory() ?? '');
            $grouped[$k][] = $card;
        }

        $categoryOrder = ['hair' => 1, 'makeup' => 2, 'nails' => 3];
        uksort($grouped, static fn (string $a, string $b) => ($categoryOrder[$a] ?? 99) <=> ($categoryOrder[$b] ?? 99));

        return $this->render('service/index.html.twig', [
            'category' => $category,
            'categoryLabel' => $category ? ($categoryLabels[$category] ?? $category) : 'Բոլոր ծառայությունները',
            'categoryLabels' => $categoryLabels,
            'serviceCards' => $serviceCards,
            'groupedServices' => $grouped,
            'services' => $serviceRepository->findAll(),
        ]);
    }

    #[Route('/services/{id}', name: 'app_service_show_id', requirements: ['id' => '\d+'], methods: ['GET'])]
    #[Route('/services/{id}-{slug}', name: 'app_service_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(
        Service $service,
        ?string $slug = null,
        SluggerInterface $slugger,
        ArtistPostRepository $postRepository,
        ServiceRepository $serviceRepository,
    ): Response {
        $canonicalSlug = $this->slugForService($service, $slugger);
        if ($slug !== $canonicalSlug) {
            return $this->redirectToRoute('app_service_show', [
                'id' => $service->getId(),
                'slug' => $canonicalSlug,
            ], Response::HTTP_MOVED_PERMANENTLY);
        }

        $posts = [];
        try {
            $posts = $postRepository->findPublishedByCategory($service->getCategory(), $service->getId());
        } catch (\Throwable) {
            // If DB is temporarily unavailable, keep the page functional.
        }

        $related = [];
        try {
            $cat = $service->getCategory();
            if ($cat) {
                $related = $serviceRepository->createQueryBuilder('s')
                    ->andWhere('s.category = :cat')
                    ->andWhere('s.id != :id')
                    ->setParameter('cat', $cat)
                    ->setParameter('id', $service->getId())
                    ->orderBy('s.name', 'ASC')
                    ->setMaxResults(9)
                    ->getQuery()
                    ->getResult();
            }
        } catch (\Throwable) {
            // keep page functional
        }

        $relatedCards = [];
        foreach ($related as $s) {
            $relatedCards[] = [
                'service' => $s,
                'slug' => $this->slugForService($s, $slugger),
            ];
        }

        $categoryLabels = [
            'hair' => 'Վարսահարդարում',
            'nails' => 'Մատնահարդարում',
            'makeup' => 'Դիմահարդարում',
        ];

        $categoryKey = (string) ($service->getCategory() ?? '');

        return $this->render('service/show.html.twig', [
            'service' => $service,
            'categoryKey' => $categoryKey,
            'categoryLabel' => $categoryLabels[$categoryKey] ?? $categoryKey,
            'posts' => $posts,
            'canonicalSlug' => $canonicalSlug,
            'relatedServiceCards' => $relatedCards,
            'services' => $serviceRepository->findAll(),
        ]);
    }

    private function slugForService(Service $service, SluggerInterface $slugger): string
    {
        $name = trim((string) $service->getName());
        $slug = trim($slugger->slug($name)->lower()->toString(), '-');

        if ($slug === '') {
            $cat = trim((string) $service->getCategory());
            $slug = $cat !== '' ? $cat . '-service' : 'service';
        }

        return $slug;
    }
}


