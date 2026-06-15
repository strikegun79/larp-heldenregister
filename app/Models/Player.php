<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Player extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'lastname',
        'email',
        'dayofbirth',
        'gender',
        'image',
        'active',
        'active_hero_id',
        'legacy_id',
    ];

    protected $casts = [
        'dayofbirth' => 'date',
        'active' => 'boolean',
    ];

    /** Avatar-URL: hochgeladenes Bild oder Standardbild (PLAY-10). */
    public function getAvatarUrlAttribute(): string
    {
        return $this->image
            ? \Illuminate\Support\Facades\Storage::disk('public')->url($this->image)
            : '/images/player_default_avatar.jpg';
    }

    /** Teilnahmen (besuchte Events) des Spielers (PLAY-10). */
    public function visits(): HasMany
    {
        return $this->hasMany(EventVisit::class);
    }

    /**
     * Helden dieses Spielers.
     */
    public function heroes(): HasMany
    {
        return $this->hasMany(Hero::class);
    }

    /**
     * Anmeldungen/Buchungen des Spielers (für die Abenteuer-Übersicht).
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    /**
     * Der aktuell aktive Held (Legacy: player.hero_active).
     */
    public function activeHero(): BelongsTo
    {
        return $this->belongsTo(Hero::class, 'active_hero_id');
    }

    /**
     * Benutzer, denen dieser Spieler zugeordnet ist (Legacy: user2player).
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withPivot('self');
    }

    /**
     * Voller Name des Spielers.
     */
    public function getFullNameAttribute(): string
    {
        return trim("{$this->name} {$this->lastname}");
    }

    /**
     * Das Matrix-Konto dieses Spielers (Legacy: matrix_account.player_id).
     */
    public function matrixAccount(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(MatrixAccount::class);
    }

    /**
     * Leitet die Matrix-User-ID aus dem Namen ab (Legacy:
     * "@vorname.nachname:domain"). Wird nur bei der Erstanlage genutzt –
     * eine bestehende mxid bleibt als stabile Matrix-Identität erhalten.
     */
    public function deriveMatrixId(): string
    {
        $domain = config('matrix.domain');

        return '@'.mb_strtolower($this->name).'.'.mb_strtolower($this->lastname).':'.$domain;
    }
}
