<?php

namespace App\Controller;

use App\Repository\ArtistPostRepository;
use App\Repository\ArtistProfileRepository;
use App\Repository\ServiceRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\String\Slugger\SluggerInterface;

final class SitemapController extends AbstractController
{
    #[Route('/sitemap.xml', name: 'app_sitemap', methods: ['GET'])]
    public function sitemap(
        ArtistProfileRepository $artistProfileRepository,
        ArtistPostRepository $artistPostRepository,
        ServiceRepository $serviceRepository,
        SluggerInterface $slugger,
    ): Response {
        $urls = [];

        $addUrl = function (string $loc, ?\DateTimeInterface $lastMod = null, ?string $changeFreq = null, ?float $priority = null) use (&$urls): void {
            $urls[$loc] = [
                'loc' => $loc,
                'lastmod' => $lastMod,
                'changefreq' => $changeFreq,
                'priority' => $priority,
            ];
        };

        // --- Static/public pages ---
        $addUrl($this->generateUrl('app_home', [], UrlGeneratorInterface::ABSOLUTE_URL), null, 'weekly', 1.0);

        // Artists list (with optional category filters)
        $addUrl($this->generateUrl('app_artists', [], UrlGeneratorInterface::ABSOLUTE_URL), null, 'weekly', 0.8);
        foreach (['hair', 'makeup', 'nails'] as $cat) {
            $addUrl($this->generateUrl('app_artists', ['category' => $cat], UrlGeneratorInterface::ABSOLUTE_URL), null, 'weekly', 0.7);
        }

        // Blog index + category pages
        $addUrl($this->generateUrl('app_blog_index', [], UrlGeneratorInterface::ABSOLUTE_URL), null, 'weekly', 0.8);
        foreach (['hair', 'makeup', 'nails'] as $cat) {
            $addUrl($this->generateUrl('app_blog_category', ['category' => $cat], UrlGeneratorInterface::ABSOLUTE_URL), null, 'weekly', 0.7);
        }

        // Services index + category pages
        $addUrl($this->generateUrl('app_service_index', [], UrlGeneratorInterface::ABSOLUTE_URL), null, 'weekly', 0.8);
        foreach (['hair', 'makeup', 'nails'] as $cat) {
            $addUrl($this->generateUrl('app_service_category', ['category' => $cat], UrlGeneratorInterface::ABSOLUTE_URL), null, 'weekly', 0.7);
        }

        // --- Dynamic pages: artists + published posts ---
        try {
            foreach ($artistProfileRepository->findAll() as $artist) {
                $id = $artist->getId();
                if (!$id) {
                    continue;
                }
                $addUrl($this->generateUrl('app_artist_show', ['id' => $id], UrlGeneratorInterface::ABSOLUTE_URL), null, 'weekly', 0.6);
            }
        } catch (\Throwable) {
            // If DB is temporarily unavailable, keep sitemap functional with static URLs.
        }

        try {
            foreach ($artistPostRepository->findPublishedByCategory(null, null) as $post) {
                $id = $post->getId();
                $slug = $post->getSlug();
                if (!$id || !$slug) {
                    continue;
                }

                $last = $post->getUpdatedAt() ?? $post->getPublishedAt() ?? $post->getCreatedAt();
                $addUrl(
                    $this->generateUrl('app_blog_show', ['id' => $id, 'slug' => $slug], UrlGeneratorInterface::ABSOLUTE_URL),
                    $last,
                    'monthly',
                    0.6
                );
            }
        } catch (\Throwable) {
            // If DB is temporarily unavailable, keep sitemap functional with static URLs.
        }

        // Services (dynamic)
        try {
            foreach ($serviceRepository->findAll() as $service) {
                $id = $service->getId();
                $name = trim((string) $service->getName());
                if (!$id || $name === '') {
                    continue;
                }

                $slug = trim($slugger->slug($name)->lower()->toString(), '-');
                if ($slug === '') {
                    $cat = trim((string) $service->getCategory());
                    $slug = $cat !== '' ? $cat . '-service' : 'service';
                }

                $addUrl(
                    $this->generateUrl('app_service_show', ['id' => $id, 'slug' => $slug], UrlGeneratorInterface::ABSOLUTE_URL),
                    null,
                    'monthly',
                    0.6
                );
            }
        } catch (\Throwable) {
            // If DB is temporarily unavailable, keep sitemap functional with static URLs.
        }

        $xml = $this->buildSitemapXml(array_values($urls));

        $response = new Response($xml);
        $response->headers->set('Content-Type', 'application/xml; charset=utf-8');
        $response->setPublic();
        $response->setMaxAge(3600);
        $response->setSharedMaxAge(3600);

        return $response;
    }

    /**
     * @param array<int, array{loc: string, lastmod: ?\DateTimeInterface, changefreq: ?string, priority: ?float}> $urls
     */
    private function buildSitemapXml(array $urls): string
    {
        $esc = static fn (string $s) => htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE | ENT_XML1, 'UTF-8');

        $out = [];
        $out[] = '<?xml version="1.0" encoding="UTF-8"?>';
        $out[] = '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

        foreach ($urls as $u) {
            $out[] = '  <url>';
            $out[] = '    <loc>'.$esc($u['loc']).'</loc>';

            if ($u['lastmod'] instanceof \DateTimeInterface) {
                // Sitemap supports full ISO-8601, but date-only is also widely accepted.
                $out[] = '    <lastmod>'.$u['lastmod']->format('Y-m-d').'</lastmod>';
            }
            if (is_string($u['changefreq']) && $u['changefreq'] !== '') {
                $out[] = '    <changefreq>'.$esc($u['changefreq']).'</changefreq>';
            }
            if (is_float($u['priority'])) {
                $out[] = '    <priority>'.number_format(max(0.0, min(1.0, $u['priority'])), 1, '.', '').'</priority>';
            }

            $out[] = '  </url>';
        }

        $out[] = '</urlset>';

        return implode("\n", $out)."\n";
    }
}


