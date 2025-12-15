<?php

namespace App\EventSubscriber;

use App\Entity\ArtistPost;
use App\Service\PhotoOptimizer;
use EasyCorp\Bundle\EasyAdminBundle\Event\AfterEntityPersistedEvent;
use EasyCorp\Bundle\EasyAdminBundle\Event\AfterEntityUpdatedEvent;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class ArtistPostPhotoOptimizeSubscriber implements EventSubscriberInterface
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
        if (!$entity instanceof ArtistPost) {
            return;
        }

        $filename = $entity->getImageUrl();
        if (!$filename) {
            return;
        }

        $path = rtrim($this->projectDir, '/') . '/public/uploads/posts/' . $filename;
        if (!is_file($path)) {
            return;
        }

        // Only optimize fresh uploads; avoid recompressing on every edit.
        $mtime = @filemtime($path);
        if (is_int($mtime) && (time() - $mtime) > 600) {
            return;
        }

        $this->photoOptimizer->optimize($path, maxWidth: 1600, jpegQuality: 82);
    }
}


