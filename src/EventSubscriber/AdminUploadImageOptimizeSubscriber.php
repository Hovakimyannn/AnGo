<?php

namespace App\EventSubscriber;

use App\Entity\ArtistPost;
use App\Entity\ArtistProfile;
use App\Entity\DidYouKnowPost;
use App\Entity\HomePageSettings;
use App\Service\PhotoOptimizer;
use EasyCorp\Bundle\EasyAdminBundle\Event\AfterEntityPersistedEvent;
use EasyCorp\Bundle\EasyAdminBundle\Event\AfterEntityUpdatedEvent;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Single place: after EasyAdmin save, compress recently uploaded raster images
 * for all entities that store filenames under public/uploads/{photos|posts}/.
 */
final class AdminUploadImageOptimizeSubscriber implements EventSubscriberInterface
{
    private const JPEG_QUALITY = 82;

    /**
     * @var array<string, list<array{getter: string, subdir: 'photos'|'posts', maxWidth: int}>>
     */
    private const FIELD_MAP = [
        ArtistProfile::class => [
            ['getter' => 'getPhotoUrl', 'subdir' => 'photos', 'maxWidth' => 1200],
            ['getter' => 'getCoverImageUrl', 'subdir' => 'photos', 'maxWidth' => 1200],
        ],
        ArtistPost::class => [
            ['getter' => 'getImageUrl', 'subdir' => 'posts', 'maxWidth' => 1600],
        ],
        DidYouKnowPost::class => [
            ['getter' => 'getImageUrl', 'subdir' => 'posts', 'maxWidth' => 1600],
        ],
        HomePageSettings::class => [
            ['getter' => 'getHeroImage', 'subdir' => 'photos', 'maxWidth' => 2048],
            ['getter' => 'getServiceHairImage', 'subdir' => 'photos', 'maxWidth' => 1600],
            ['getter' => 'getServiceMakeupImage', 'subdir' => 'photos', 'maxWidth' => 1600],
            ['getter' => 'getServiceNailsImage', 'subdir' => 'photos', 'maxWidth' => 1600],
        ],
    ];

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

    public function optimize(AfterEntityPersistedEvent|AfterEntityUpdatedEvent $event): void
    {
        $entity = $event->getEntityInstance();
        $class = $entity::class;

        if (!isset(self::FIELD_MAP[$class])) {
            return;
        }

        $base = rtrim($this->projectDir, '/') . '/public/uploads/';

        foreach (self::FIELD_MAP[$class] as $spec) {
            $getter = $spec['getter'];
            if (!method_exists($entity, $getter)) {
                continue;
            }
            $filename = $entity->$getter();
            if (!is_string($filename) || $filename === '') {
                continue;
            }
            // Skip external URLs / absolute paths stored by mistake.
            if (str_contains($filename, '://') || str_starts_with($filename, '/')) {
                continue;
            }

            $path = $base . $spec['subdir'] . '/' . $filename;
            if (!is_file($path) || !is_writable($path)) {
                continue;
            }

            $mtime = @filemtime($path);
            if (!is_int($mtime) || (time() - $mtime) > 600) {
                continue;
            }

            $this->photoOptimizer->optimize($path, maxWidth: $spec['maxWidth'], jpegQuality: self::JPEG_QUALITY);
        }
    }
}
