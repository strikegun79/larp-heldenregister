<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Issue #30: Bekannter Plot-Charakter (z. B. Babayaga, Bran), der im öffentlichen
 * Heldenverzeichnis (/h) gesucht und unter /h/plot/{slug} angesehen werden kann.
 */
class PlotCharacter extends Model
{
    protected $fillable = [
        'name',
        'title',
        'description',
        'image_path',
        'public_visible',
    ];

    protected function casts(): array
    {
        return [
            'public_visible' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        // Slug beim Anlegen automatisch und eindeutig aus dem Namen erzeugen.
        static::creating(function (PlotCharacter $character) {
            if (blank($character->slug)) {
                $character->slug = static::uniqueSlug($character->name);
            }
        });
    }

    /**
     * Routen-Bindung über den Slug (öffentliche URL /h/plot/{slug}).
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Nur öffentlich sichtbare Plot-Charaktere.
     */
    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('public_visible', true);
    }

    /**
     * URL des Bildes oder null, wenn keines hochgeladen wurde.
     */
    public function imageUrl(): ?string
    {
        return $this->image_path ? Storage::disk('public')->url($this->image_path) : null;
    }

    /**
     * Eindeutigen Slug erzeugen (bei Kollision mit Zähler-Suffix).
     */
    public static function uniqueSlug(string $name): string
    {
        $base = Str::slug($name, '-', 'de') ?: 'plot-charakter';
        $slug = $base;
        $i = 2;

        while (static::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
