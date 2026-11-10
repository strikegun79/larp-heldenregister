<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** PUB-10: Vorgedruckter Heldenausweis-Code im Pool. */
class IdCardCode extends Model
{
    protected $fillable = ['code', 'hero_id', 'assigned_at', 'created_by'];

    protected $casts = ['assigned_at' => 'datetime'];

    public function hero(): BelongsTo
    {
        return $this->belongsTo(Hero::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isAssigned(): bool
    {
        return $this->hero_id !== null;
    }
}
