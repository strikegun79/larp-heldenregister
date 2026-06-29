<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;

class MatrixAccount extends Model
{
    use HasFactory, SoftDeletes;

    /** Cache-Schlüssel der corporal-Policy (MTX-08). */
    const CORPORAL_CACHE_KEY = 'matrix.corporal.policy';

    protected $primaryKey = 'mxid';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'mxid',
        'player_id',
        'display_name',
        'avatar_uri',
        'auth_credential',
        'active',
        'forbid_room_creation',
        'forbid_encrypted_room_creation',
    ];

    protected $casts = [
        'active' => 'boolean',
        'forbid_room_creation' => 'boolean',
        'forbid_encrypted_room_creation' => 'boolean',
    ];

    protected static function booted(): void
    {
        // Cache der corporal-Policy bei jeder Kontoänderung invalidieren (MTX-08).
        $flush = fn () => Cache::forget(self::CORPORAL_CACHE_KEY);
        static::saved($flush);
        static::deleted($flush);
        static::restored($flush);
    }

    /**
     * Der Spieler hinter dem Matrix-Konto.
     */
    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    /**
     * Räume, denen dieses Konto beigetreten ist (Legacy: matrix_joinedRoomIds).
     */
    public function rooms(): BelongsToMany
    {
        return $this->belongsToMany(
            MatrixManagedRoom::class,
            'matrix_room_memberships',
            'mxid',
            'roomid',
            'mxid',
            'roomid',
        );
    }
}
