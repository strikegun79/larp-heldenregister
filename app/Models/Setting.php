<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Portal-Konfiguration als Key/Value-Tabelle (ADM-09).
 * Laufzeit-Einstellungen, die Admins im Portal editieren können.
 */
class Setting extends Model
{
    protected $primaryKey = 'key';

    protected $keyType = 'string';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = ['key', 'value'];

    /**
     * Gibt den Wert eines Settings zurück, oder $default wenn nicht gesetzt.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        return static::find($key)?->value ?? $default;
    }

    /**
     * Setzt einen Setting-Wert (upsert).
     */
    public static function set(string $key, mixed $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
    }
}
