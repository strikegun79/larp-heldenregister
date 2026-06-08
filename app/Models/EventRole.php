<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EventRole extends Model
{
    /** IDs stammen aus dem Legacy-System (type_event_role). */
    public $incrementing = false;

    protected $fillable = [
        'id',
        'description',
    ];

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }
}
