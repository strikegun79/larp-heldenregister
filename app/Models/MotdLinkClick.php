<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

/**
 * Aufsummierte Klicks auf einen Link einer Startseiten-Nachricht.
 */
class MotdLinkClick extends Model
{
    protected $fillable = ['motd_id', 'url', 'url_hash', 'clicks', 'last_clicked_at'];

    protected $casts = [
        'clicks'          => 'integer',
        'last_clicked_at' => 'datetime',
    ];

    public function motd(): BelongsTo
    {
        return $this->belongsTo(Motd::class);
    }

    /** Klick zählen (atomar, legt die Zeile bei Bedarf an). */
    public static function record(Motd $motd, string $url): void
    {
        $now = now();

        static::upsert(
            [[
                'motd_id' => $motd->id,
                'url' => $url,
                'url_hash' => hash('sha256', $url),
                'clicks' => 1,
                'last_clicked_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]],
            ['motd_id', 'url_hash'],
            [
                'clicks' => DB::raw('clicks + 1'),
                'last_clicked_at' => $now,
                'updated_at' => $now,
            ]
        );
    }
}
