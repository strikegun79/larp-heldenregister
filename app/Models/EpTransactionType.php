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
    ];

    protected $casts = [
        'is_credit' => 'boolean',
    ];

    /**
     * Buchungen dieser Art.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(EpTransaction::class);
    }
}
