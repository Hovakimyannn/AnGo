<?php

namespace App\Twig;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class AssetVersionExtension extends AbstractExtension
{
    public function __construct(
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
    ) {}

    public function getFunctions(): array
    {
        return [
            new TwigFunction('asset_version', [$this, 'getAssetVersion']),
        ];
    }

    public function getAssetVersion(string $publicPath): string
    {
        $publicPath = ltrim($publicPath, '/');

        // Basic safety: avoid path traversal.
        if (str_contains($publicPath, '..')) {
            return '0';
        }

        $absolutePath = $this->projectDir.'/public/'.$publicPath;
        if (!is_file($absolutePath)) {
            return '0';
        }

        $mtime = @filemtime($absolutePath);
        if (false === $mtime) {
            return '0';
        }

        return (string) $mtime;
    }
}


