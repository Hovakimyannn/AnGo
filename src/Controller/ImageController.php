<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;

final class ImageController extends AbstractController
{
    #[Route('/img/photos/{filename}', name: 'app_image_photo', requirements: ['filename' => '.+'])]
    public function photo(string $filename, Request $request): Response
    {
        // Basic allowlist to avoid path traversal.
        if (str_contains($filename, '..') || str_starts_with($filename, '/')) {
            return new Response('Not found', Response::HTTP_NOT_FOUND);
        }

        $projectDir = (string) $this->getParameter('kernel.project_dir');
        $sourcePath = $projectDir . '/public/uploads/photos/' . $filename;
        if (!is_file($sourcePath)) {
            return new Response('Not found', Response::HTTP_NOT_FOUND);
        }

        $w = (int) $request->query->get('w', 0);
        $w = max(0, min($w, 2048));
        $fmt = strtolower((string) $request->query->get('fmt', 'jpg'));
        if (!in_array($fmt, ['jpg', 'jpeg', 'webp', 'png'], true)) {
            $fmt = 'jpg';
        }

        // If no resize requested, just return the original file.
        if ($w <= 0) {
            return $this->binaryWithCache($sourcePath, $filename);
        }

        $cacheDir = $projectDir . '/public/uploads/cache/photos';
        if (!is_dir($cacheDir)) {
            @mkdir($cacheDir, 0775, true);
        }

        $safeName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename) ?? $filename;
        $cachePath = sprintf('%s/%s-w%d.%s', $cacheDir, $safeName, $w, $fmt === 'jpeg' ? 'jpg' : $fmt);

        if (!is_file($cachePath)) {
            $generated = $this->generateResized($sourcePath, $cachePath, $w, $fmt);
            if (!$generated) {
                // Fallback to original if GD/WebP isn't available.
                return $this->binaryWithCache($sourcePath, $filename);
            }
        }

        return $this->binaryWithCache($cachePath, basename($cachePath));
    }

    private function binaryWithCache(string $path, string $downloadName): BinaryFileResponse
    {
        $response = new BinaryFileResponse($path);
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_INLINE, $downloadName);
        $response->headers->set('Cache-Control', 'public, max-age=31536000, immutable');
        $response->setAutoEtag();
        $response->setAutoLastModified();

        return $response;
    }

    private function generateResized(string $sourcePath, string $targetPath, int $targetWidth, string $fmt): bool
    {
        if (!function_exists('getimagesize') || !function_exists('imagecreatetruecolor')) {
            return false;
        }

        $info = @getimagesize($sourcePath);
        if (!$info || empty($info[0]) || empty($info[1]) || empty($info['mime'])) {
            return false;
        }

        [$srcW, $srcH] = [$info[0], $info[1]];
        if ($srcW <= 0 || $srcH <= 0) {
            return false;
        }

        $scale = min(1.0, $targetWidth / $srcW);
        $dstW = (int) max(1, round($srcW * $scale));
        $dstH = (int) max(1, round($srcH * $scale));

        $mime = (string) $info['mime'];
        $src = match ($mime) {
            'image/jpeg' => function_exists('imagecreatefromjpeg') ? @imagecreatefromjpeg($sourcePath) : null,
            'image/png' => function_exists('imagecreatefrompng') ? @imagecreatefrompng($sourcePath) : null,
            'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($sourcePath) : null,
            default => null,
        };
        if (!$src) {
            return false;
        }

        $dst = imagecreatetruecolor($dstW, $dstH);
        if (!$dst) {
            return false;
        }

        // Preserve transparency for PNG/WebP sources.
        if (in_array($mime, ['image/png', 'image/webp'], true) && function_exists('imagealphablending')) {
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
        }

        if (!imagecopyresampled($dst, $src, 0, 0, 0, 0, $dstW, $dstH, $srcW, $srcH)) {
            return false;
        }

        $ok = match ($fmt) {
            'webp' => function_exists('imagewebp') ? @imagewebp($dst, $targetPath, 80) : false,
            'png' => function_exists('imagepng') ? @imagepng($dst, $targetPath, 6) : false,
            'jpeg', 'jpg' => function_exists('imagejpeg') ? @imagejpeg($dst, $targetPath, 82) : false,
            default => false,
        };

        imagedestroy($src);
        imagedestroy($dst);

        return (bool) $ok;
    }
}


