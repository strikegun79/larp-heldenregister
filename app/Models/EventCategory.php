<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class EventCategory extends Model
{
    use SoftDeletes;

    /** IDs stammen aus dem Legacy-System (event_category). */
    public $incrementing = false;

    protected $fillable = [
        'id',
        'name',
        'description',
    ];

    public function adventures(): HasMany
    {
        return $this->hasMany(Adventure::class);
    }
}
