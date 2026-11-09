<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EventClient extends Model
{
    /** IDs stammen aus dem Legacy-System (event_auftraggeber). */
    public $incrementing = false;

    protected $fillable = [
        'id',
        'name',
    ];

    public function adventures(): HasMany
    {
        return $this->hasMany(Adventure::class);
    }
}
