<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EpTransactionType extends Model
{
    /** IDs stammen aus dem Legacy-System (type_transEP). */
    public $incrementing = false;

    protected $fillable = [
        'id',
        'description',
        'is_credit',
        'default_ep', // Issue #28: Standardwert beim EP-Buchen
    ];

    protected $casts = [
        'is_credit' => 'boolean',
        'default_ep' => 'integer',
    ];

    /**
     * Buchungen dieser Art.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(EpTransaction::class);
    }
}
