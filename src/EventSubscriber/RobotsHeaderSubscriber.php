<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Ensure public pages are indexable (fixes Lighthouse "Page is blocked from indexing").
 *
 * Note: If your reverse proxy / CDN is adding `X-Robots-Tag: noindex` AFTER PHP,
 * you must also remove it there. This subscriber covers the common case where the
 * application (or upstream config) sets the header and Symfony can override it.
 */
final class RobotsHeaderSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => 'onKernelResponse',
        ];
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $response = $event->getResponse();
        $request = $event->getRequest();

        // Only touch successful HTML responses.
        if ($response->getStatusCode() !== Response::HTTP_OK) {
            return;
        }
        $contentType = (string) $response->headers->get('Content-Type', '');
        if ($contentType !== '' && !str_starts_with($contentType, 'text/html')) {
            return;
        }

        $path = $request->getPathInfo();
        $isAdminOrAuth = str_starts_with($path, '/admin')
            || str_starts_with($path, '/login')
            || str_starts_with($path, '/reset-password')
            || str_starts_with($path, '/forgot-password');

        // Public pages should be indexable.
        $response->headers->set('X-Robots-Tag', $isAdminOrAuth ? 'noindex, nofollow' : 'index, follow');
    }
}


