<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Group extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'image',
    ];

    /**
     * Helden, die dieser Gruppe angehören (GRP-01).
     * Pivot: role (Anführer/Mitglied), joined_at.
     */
    public function heroes(): BelongsToMany
    {
        return $this->belongsToMany(Hero::class)
            ->withPivot('role', 'joined_at');
    }
}
