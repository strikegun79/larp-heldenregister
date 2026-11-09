<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class MatrixManagedRoom extends Model
{
    protected $primaryKey = 'roomid';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'roomid',
        'roomname',
        'roomtype',
        'default_allow',
        'default_deny',
    ];

    protected $casts = [
        'default_allow' => 'boolean',
        'default_deny' => 'boolean',
    ];

    /**
     * Mitglieder dieses Raums.
     */
    public function accounts(): BelongsToMany
    {
        return $this->belongsToMany(
            MatrixAccount::class,
            'matrix_room_memberships',
            'roomid',
            'mxid',
            'roomid',
            'mxid',
        );
    }
}
