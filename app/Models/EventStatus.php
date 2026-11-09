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

    /** Status „Anmeldung geschlossen" – ab hier ist Check-in möglich (ADV-14). */
    public const REGISTRATION_CLOSED = 40;

    /** Status „abgesagt" (ADV-14-Nummerierung). */
    public const CANCELLED = 70;

    /**
     * Erlaubte Status-Übergänge (ADV-05): von jedem Status zu welchen Zielen.
     * „abgesagt" (70) ist aus jedem aktiven Status erreichbar; „Abgeschlossen"
     * (60) ist terminal.
     */
    public const TRANSITIONS = [
        0 => [10, 20],
        10 => [20, 70],
        20 => [30, 70],
        30 => [40, 70],
        40 => [30, 50, 70],
        50 => [60, 70],
        60 => [],
        70 => [20],
    ];

    public function adventures(): HasMany
    {
        return $this->hasMany(Adventure::class);
    }
}
