<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class MatrixAccount extends Model
{
    use HasFactory, SoftDeletes;

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
