<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Location extends Model
{
    use HasFactory;

    protected $fillable = [
        'titel',
        'gps',
        'plz',
        'city',
        'address',
        'image',
        'legacy_id',
    ];

    public function adventures(): HasMany
    {
        return $this->hasMany(Adventure::class);
    }
}
