<?php

namespace App\Command;

use App\Entity\ArtistPost;
use App\Entity\DidYouKnowPost;
use App\Service\PhotoOptimizer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsCommand(
    name: 'app:generate-post-list-thumbnails',
    description: 'Create -list.* thumbnails for Did You Know and artist posts (for existing uploads).',
)]
final class GeneratePostListThumbnailsCommand extends Command
{
    private const VIDEO_EXT = ['mp4', 'webm', 'ogg'];

    private const THUMB_MAX_WIDTH = 640;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly PhotoOptimizer $photoOptimizer,
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('force', null, InputOption::VALUE_NONE, 'Regenerate even when image_thumbnail_url is already set');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $force = (bool) $input->getOption('force');
        $postsDir = rtrim($this->projectDir, '/') . '/public/uploads/posts/';
        $updated = 0;

        foreach ([DidYouKnowPost::class, ArtistPost::class] as $class) {
            $repo = $this->entityManager->getRepository($class);
            $posts = $repo->findAll();
            foreach ($posts as $post) {
                $main = $post->getImageUrl();
                if (!is_string($main) || $main === '' || str_contains($main, '://')) {
                    continue;
                }
                $ext = strtolower((string) pathinfo($main, PATHINFO_EXTENSION));
                if (in_array($ext, self::VIDEO_EXT, true)) {
                    continue;
                }
                $thumbStored = $post->getImageThumbnailUrl();
                if (!$force && is_string($thumbStored) && $thumbStored !== '') {
                    continue;
                }

                $srcPath = $postsDir . $main;
                if (!is_file($srcPath) || !is_readable($srcPath)) {
                    continue;
                }

                $stem = (string) pathinfo($main, PATHINFO_FILENAME);
                $thumbName = $stem . '-list.' . pathinfo($main, PATHINFO_EXTENSION);
                $thumbPath = $postsDir . $thumbName;

                if (!$this->photoOptimizer->writeResizedCopy($srcPath, $thumbPath, self::THUMB_MAX_WIDTH, 78)) {
                    continue;
                }

                if (is_string($thumbStored) && $thumbStored !== '' && $thumbStored !== $thumbName) {
                    $oldPath = $postsDir . $thumbStored;
                    if (is_file($oldPath)) {
                        @unlink($oldPath);
                    }
                }

                $post->setImageThumbnailUrl($thumbName);
                ++$updated;
            }
        }

        if ($updated > 0) {
            $this->entityManager->flush();
        }

        $io->success(sprintf('Updated %d post(s).', $updated));

        return Command::SUCCESS;
    }
}
