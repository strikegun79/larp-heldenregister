<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * HERO-24: Speichert ein Galerie-Bild mit freiem Seitenverhältnis
 * auf max. 1200 × 900 px herunterskaliert als JPEG (Qualität 85).
 */
class GalleryImageStorage
{
    private const MAX_W = 1200;
    private const MAX_H = 900;

    public static function store(UploadedFile $file, string $dir): string
    {
        $src = match ($file->getMimeType()) {
            'image/png'  => imagecreatefrompng($file->getRealPath()),
            'image/webp' => imagecreatefromwebp($file->getRealPath()),
            default      => imagecreatefromjpeg($file->getRealPath()),
        };

        $srcW = imagesx($src);
        $srcH = imagesy($src);

        // Skalierung berechnen: in MAX_W × MAX_H-Box einpassen.
        $scale = min(self::MAX_W / $srcW, self::MAX_H / $srcH, 1.0);
        $dstW  = (int) round($srcW * $scale);
        $dstH  = (int) round($srcH * $scale);

        $dst = imagecreatetruecolor($dstW, $dstH);
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $dstW, $dstH, $srcW, $srcH);
        imageresolution($dst, 72, 72);

        ob_start();
        imagejpeg($dst, null, 85);
        $binary = ob_get_clean();

        imagedestroy($src);
        imagedestroy($dst);

        $path = trim($dir, '/').'/'.Str::uuid()->toString().'.jpg';
        Storage::disk('public')->put($path, $binary);

        return $path;
    }
}
