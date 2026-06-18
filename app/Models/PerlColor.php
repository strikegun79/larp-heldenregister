<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PerlColor extends Model
{
    protected $fillable = [
        'code',
        'name',
    ];

    /**
     * Fertigkeiten mit dieser Perlenfarbe.
     */
    public function skills(): HasMany
    {
        return $this->hasMany(Skill::class);
    }
}
