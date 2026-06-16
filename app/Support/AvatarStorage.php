<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Speichert ein hochgeladenes Bild als zentriert zugeschnittenes 1:1-Quadrat
 * (JPEG) auf der „public"-Disk (PLAY-11). Nutzt GD.
 */
class AvatarStorage
{
    public static function storeSquare(UploadedFile $file, string $dir, int $size = 400): string
    {
        $src = match ($file->getMimeType()) {
            'image/png' => imagecreatefrompng($file->getRealPath()),
            'image/webp' => imagecreatefromwebp($file->getRealPath()),
            default => imagecreatefromjpeg($file->getRealPath()),
        };

        $w = imagesx($src);
        $h = imagesy($src);
        $side = min($w, $h);
        $sx = (int) (($w - $side) / 2);
        $sy = (int) (($h - $side) / 2);

        $dst = imagecreatetruecolor($size, $size);
        imagecopyresampled($dst, $src, 0, 0, $sx, $sy, $size, $size, $side, $side);
        // 72 DPI für Browser-Anzeige setzen.
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
