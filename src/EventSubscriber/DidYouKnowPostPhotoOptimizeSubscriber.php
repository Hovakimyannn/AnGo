<?php

namespace App\EventSubscriber;

use App\Entity\DidYouKnowPost;
use App\Service\PhotoOptimizer;
use EasyCorp\Bundle\EasyAdminBundle\Event\AfterEntityPersistedEvent;
use EasyCorp\Bundle\EasyAdminBundle\Event\AfterEntityUpdatedEvent;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class DidYouKnowPostPhotoOptimizeSubscriber implements EventSubscriberInterface
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
        if (!$entity instanceof DidYouKnowPost) {
            return;
        }

        $filename = $entity->getImageUrl();
        if (!$filename) {
            return;
        }

        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if (in_array($ext, ['mp4', 'webm', 'ogg'], true)) {
            return;
        }

        $path = rtrim($this->projectDir, '/') . '/public/uploads/posts/' . $filename;
        if (!is_file($path)) {
            return;
        }

        $mtime = @filemtime($path);
        if (is_int($mtime) && (time() - $mtime) > 600) {
            return;
        }

        $this->photoOptimizer->optimize($path, maxWidth: 1600, jpegQuality: 82);
        $this->photoOptimizer->writeWebpSibling($path);
    }
}
