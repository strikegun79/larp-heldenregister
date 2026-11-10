<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HeroGalleryImage extends Model
{
    use HasFactory;

    protected $fillable = ['hero_id', 'path', 'sort_order'];

    public function hero(): BelongsTo
    {
        return $this->belongsTo(Hero::class);
    }

    public function getUrlAttribute(): string
    {
        return '/storage/'.$this->path;
    }
}
