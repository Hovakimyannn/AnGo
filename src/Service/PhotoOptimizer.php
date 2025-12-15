<?php

namespace App\Service;

final class PhotoOptimizer
{
    /**
     * Resize (downscale only) and recompress image in-place to reduce bytes.
     *
     * - Keeps the same file extension/format.
     * - Strips metadata by re-encoding.
     * - Best-effort: if GD (or format support) is missing, returns false.
     */
    public function optimize(string $absolutePath, int $maxWidth = 1200, int $jpegQuality = 82): bool
    {
        if (!is_file($absolutePath) || !is_readable($absolutePath) || !is_writable($absolutePath)) {
            return false;
        }

        if (!function_exists('getimagesize') || !function_exists('imagecreatetruecolor')) {
            return false;
        }

        $info = @getimagesize($absolutePath);
        if (!$info || empty($info[0]) || empty($info[1]) || empty($info['mime'])) {
            return false;
        }

        $srcW = (int) $info[0];
        $srcH = (int) $info[1];
        $mime = (string) $info['mime'];

        // Only downscale; never upscale.
        $scale = 1.0;
        if ($maxWidth > 0 && $srcW > $maxWidth) {
            $scale = $maxWidth / $srcW;
        }
        $dstW = (int) max(1, round($srcW * $scale));
        $dstH = (int) max(1, round($srcH * $scale));

        $src = match ($mime) {
            'image/jpeg' => function_exists('imagecreatefromjpeg') ? @imagecreatefromjpeg($absolutePath) : null,
            'image/png' => function_exists('imagecreatefrompng') ? @imagecreatefrompng($absolutePath) : null,
            'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($absolutePath) : null,
            default => null,
        };
        if (!$src) {
            return false;
        }

        $dst = imagecreatetruecolor($dstW, $dstH);
        if (!$dst) {
            imagedestroy($src);
            return false;
        }

        // Preserve transparency for PNG/WebP.
        if (in_array($mime, ['image/png', 'image/webp'], true) && function_exists('imagealphablending')) {
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
        }

        if (!imagecopyresampled($dst, $src, 0, 0, 0, 0, $dstW, $dstH, $srcW, $srcH)) {
            imagedestroy($src);
            imagedestroy($dst);
            return false;
        }

        // Encode into a temp file then atomically replace (avoid corrupting on failure).
        $tmp = $absolutePath . '.tmp';

        $ok = match ($mime) {
            'image/jpeg' => function_exists('imagejpeg') ? @imagejpeg($dst, $tmp, $jpegQuality) : false,
            'image/png' => function_exists('imagepng') ? @imagepng($dst, $tmp, 6) : false,
            'image/webp' => function_exists('imagewebp') ? @imagewebp($dst, $tmp, 80) : false,
            default => false,
        };

        imagedestroy($src);
        imagedestroy($dst);

        if (!$ok || !is_file($tmp)) {
            @unlink($tmp);
            return false;
        }

        // Replace original.
        if (!@rename($tmp, $absolutePath)) {
            @unlink($tmp);
            return false;
        }

        return true;
    }
}


