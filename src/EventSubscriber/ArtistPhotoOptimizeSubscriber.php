<?php

namespace App\EventSubscriber;

use App\Entity\ArtistProfile;
use App\Service\PhotoOptimizer;
use EasyCorp\Bundle\EasyAdminBundle\Event\AfterEntityPersistedEvent;
use EasyCorp\Bundle\EasyAdminBundle\Event\AfterEntityUpdatedEvent;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class ArtistPhotoOptimizeSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly PhotoOptimizer $photoOptimizer,
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            AfterEntityPersistedEvent::class => 'optimize',
            AfterEntityUpdatedEvent::class => 'optimize',
        ];
    }

    public function optimize(object $event): void
    {
        $entity = $event->getEntityInstance();
        if (!$entity instanceof ArtistProfile) {
            return;
        }

        $filename = $entity->getPhotoUrl();
        if (!$filename) {
            return;
        }

        $path = rtrim($this->projectDir, '/') . '/public/uploads/photos/' . $filename;
        if (!is_file($path)) {
            return;
        }

        // Avoid recompressing the same image on every edit:
        // only optimize files that were modified recently (i.e. likely just uploaded).
        $mtime = @filemtime($path);
        if (is_int($mtime) && (time() - $mtime) > 600) {
            return;
        }

        // Downscale and recompress for typical UI usage (cards/avatars).
        $this->photoOptimizer->optimize($path, maxWidth: 1200, jpegQuality: 82);
    }
}


