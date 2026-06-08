<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EventStatus extends Model
{
    /** IDs stammen aus dem Legacy-System (type_eventStatus). */
    public $incrementing = false;

    protected $fillable = [
        'id',
        'description',
        'color',
    ];

    /** Status, ab dem eine Anmeldung möglich ist. */
    public const REGISTRATION_OPEN = 30;

    public function adventures(): HasMany
    {
        return $this->hasMany(Adventure::class);
    }
}
