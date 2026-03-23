<?php

namespace App\EventSubscriber;

use App\Entity\ArtistPost;
use App\Entity\ArtistProfile;
use App\Entity\DidYouKnowPost;
use App\Entity\HomePageSettings;
use App\Service\PhotoOptimizer;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Event\AfterEntityPersistedEvent;
use EasyCorp\Bundle\EasyAdminBundle\Event\AfterEntityUpdatedEvent;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * After EasyAdmin save: compress raster uploads and, for post entities, write a small list thumbnail.
 */
final class AdminUploadImageOptimizeSubscriber implements EventSubscriberInterface
{
    private const JPEG_QUALITY = 82;

    private const VIDEO_EXTENSIONS = ['mp4', 'webm', 'ogg'];

    /**
     * @var array<string, list<array<string, mixed>>>
     */
    private const FIELD_MAP = [
        ArtistProfile::class => [
            ['getter' => 'getPhotoUrl', 'subdir' => 'photos', 'maxWidth' => 1200],
            ['getter' => 'getCoverImageUrl', 'subdir' => 'photos', 'maxWidth' => 1200],
        ],
        ArtistPost::class => [
            [
                'getter' => 'getImageUrl',
                'subdir' => 'posts',
                'maxWidth' => 1600,
                'thumbMaxWidth' => 640,
                'thumbGetter' => 'getImageThumbnailUrl',
                'thumbSetter' => 'setImageThumbnailUrl',
            ],
        ],
        DidYouKnowPost::class => [
            [
                'getter' => 'getImageUrl',
                'subdir' => 'posts',
                'maxWidth' => 1600,
                'thumbMaxWidth' => 640,
                'thumbGetter' => 'getImageThumbnailUrl',
                'thumbSetter' => 'setImageThumbnailUrl',
            ],
        ],
        HomePageSettings::class => [
            ['getter' => 'getHeroImage', 'subdir' => 'photos', 'maxWidth' => 2048],
            ['getter' => 'getServiceHairImage', 'subdir' => 'photos', 'maxWidth' => 1600],
            ['getter' => 'getServiceMakeupImage', 'subdir' => 'photos', 'maxWidth' => 1600],
            ['getter' => 'getServiceNailsImage', 'subdir' => 'photos', 'maxWidth' => 1600],
        ],
    ];

    /** @var array<string, true> */
    private array $handledEntityKeys = [];

    public function __construct(
        private readonly PhotoOptimizer $photoOptimizer,
        private readonly EntityManagerInterface $entityManager,
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

        $id = method_exists($entity, 'getId') ? $entity->getId() : null;
        $dedupKey = $class . '#' . ($id ?? spl_object_id($entity));
        if (isset($this->handledEntityKeys[$dedupKey])) {
            return;
        }
        $this->handledEntityKeys[$dedupKey] = true;

        $base = rtrim($this->projectDir, '/') . '/public/uploads/';
        $needsFlush = false;

        foreach (self::FIELD_MAP[$class] as $spec) {
            $getter = $spec['getter'];
            if (!method_exists($entity, $getter)) {
                continue;
            }
            $filename = $entity->$getter();

            if (isset($spec['thumbSetter'], $spec['thumbGetter'], $spec['thumbMaxWidth'])) {
                $this->syncPostThumbnail($entity, $spec, $base, $needsFlush);
            }

            if (!is_string($filename) || $filename === '') {
                continue;
            }
            if (str_contains($filename, '://') || str_starts_with($filename, '/')) {
                continue;
            }

            $ext = strtolower((string) pathinfo($filename, PATHINFO_EXTENSION));
            if (in_array($ext, self::VIDEO_EXTENSIONS, true)) {
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

            $this->photoOptimizer->optimize($path, maxWidth: (int) $spec['maxWidth'], jpegQuality: self::JPEG_QUALITY);
        }

        if ($needsFlush) {
            $this->entityManager->flush();
        }
    }

    /**
     * @param array<string, mixed> $spec
     */
    private function syncPostThumbnail(object $entity, array $spec, string $base, bool &$needsFlush): void
    {
        $getter = $spec['getter'];
        $filename = $entity->$getter();
        $postsDir = $base . $spec['subdir'] . '/';
        $thumbGetter = (string) $spec['thumbGetter'];
        $thumbSetter = (string) $spec['thumbSetter'];
        $thumbMaxWidth = (int) $spec['thumbMaxWidth'];

        $removeStoredThumb = function () use ($entity, $postsDir, $thumbGetter, $thumbSetter, &$needsFlush): void {
            $old = $entity->$thumbGetter();
            if (!is_string($old) || $old === '') {
                return;
            }
            if (!str_contains($old, '://') && !str_starts_with($old, '/')) {
                $p = $postsDir . $old;
                if (is_file($p)) {
                    @unlink($p);
                }
            }
            $entity->$thumbSetter(null);
            $needsFlush = true;
        };

        if (!is_string($filename) || $filename === '') {
            $removeStoredThumb();

            return;
        }
        if (str_contains($filename, '://') || str_starts_with($filename, '/')) {
            return;
        }

        $ext = strtolower((string) pathinfo($filename, PATHINFO_EXTENSION));
        if (in_array($ext, self::VIDEO_EXTENSIONS, true)) {
            $removeStoredThumb();

            return;
        }

        $path = $postsDir . $filename;
        if (!is_file($path) || !is_writable($path)) {
            return;
        }

        $mtime = @filemtime($path);
        if (!is_int($mtime) || (time() - $mtime) > 600) {
            return;
        }

        $stem = (string) pathinfo($filename, PATHINFO_FILENAME);
        $thumbName = $stem . '-list.' . pathinfo($filename, PATHINFO_EXTENSION);
        $thumbPath = $postsDir . $thumbName;

        if (!$this->photoOptimizer->writeResizedCopy($path, $thumbPath, $thumbMaxWidth, 78)) {
            return;
        }

        $oldThumb = $entity->$thumbGetter();
        if (is_string($oldThumb) && $oldThumb !== '' && $oldThumb !== $thumbName && !str_contains($oldThumb, '://')) {
            $oldPath = $postsDir . $oldThumb;
            if (is_file($oldPath)) {
                @unlink($oldPath);
            }
        }

        $entity->$thumbSetter($thumbName);
        $needsFlush = true;
    }
}
