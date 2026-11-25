<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Eintrag im Talk-Protokoll: eine Anfrage an Nextcloud (Änderung oder Fehler).
 * Enthält nie Zugangsdaten. Aufbewahrung: RETENTION_DAYS (dsgvo:prune).
 */
class TalkLog extends Model
{
    public const UPDATED_AT = null;

    public const RETENTION_DAYS = 90;

    protected $fillable = [
        'adventure_id',
        'player_id',
        'user_id',
        'action',
        'method',
        'endpoint',
        'status',
        'success',
        'message',
    ];

    protected $casts = [
        'success' => 'boolean',
        'created_at' => 'datetime',
    ];

    /** @return BelongsTo<Adventure, $this> */
    public function adventure(): BelongsTo
    {
        return $this->belongsTo(Adventure::class);
    }

    /** @return BelongsTo<Player, $this> */
    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class)->withTrashed();
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }
}
