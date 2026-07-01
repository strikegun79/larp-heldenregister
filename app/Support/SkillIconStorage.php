<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * SKILL-08: Speichert ein Fertigkeits-Symbol als 100×100 px JPEG (Qualität 85).
 */
class SkillIconStorage
{
    private const SIZE = 100;

    public static function store(UploadedFile $file, string $dir): string
    {
        $src = match ($file->getMimeType()) {
            'image/png'  => imagecreatefrompng($file->getRealPath()),
            'image/webp' => imagecreatefromwebp($file->getRealPath()),
            default      => imagecreatefromjpeg($file->getRealPath()),
        };

        $w    = imagesx($src);
        $h    = imagesy($src);
        $side = min($w, $h);
        $sx   = (int) (($w - $side) / 2);
        $sy   = (int) (($h - $side) / 2);

        $dst = imagecreatetruecolor(self::SIZE, self::SIZE);
        imagecopyresampled($dst, $src, 0, 0, $sx, $sy, self::SIZE, self::SIZE, $side, $side);
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
