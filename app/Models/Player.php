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
        'address_same_as_guardian',
        'street',
        'house_number',
        'zip',
        'city',
        'active_hero_id',
        'legacy_id',
    ];

    protected $casts = [
        'dayofbirth' => 'date',
        'active' => 'boolean',
        'address_same_as_guardian' => 'boolean',
    ];

    /** Aktuelles Alter in Jahren bzw. null (PLAY-07). */
    public function getAgeAttribute(): ?int
    {
        return $this->dayofbirth?->age;
    }

    /** Avatar-URL: hochgeladenes Bild oder Standardbild (PLAY-10). */
    public function getAvatarUrlAttribute(): string
    {
        return $this->image
            ? '/storage/'.$this->image
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
     * DSGVO Art. 17: Spieler anonymisieren (Recht auf Löschung).
     * Überschreibt alle Klardaten, löscht das Profilfoto und soft-deleted.
     * Verknüpfte Helden bleiben erhalten (Charaktername ist kein Realname).
     */
    public function anonymize(): void
    {
        if ($this->image) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($this->image);
        }

        $this->forceFill([
            'name'                  => 'Gelöscht',
            'lastname'              => null,
            'email'                 => null,
            'dayofbirth'            => null,
            'gender'                => null,
            'image'                 => null,
            'street'                => null,
            'house_number'          => null,
            'zip'                   => null,
            'city'                  => null,
            'address_same_as_guardian' => false,
        ])->save();

        $this->delete();
    }

    /**
     * Leitet die Matrix-User-ID aus dem Namen ab (Legacy:
     * "@vorname.nachname:domain"). Wird nur bei der Erstanlage genutzt –
     * eine bestehende mxid bleibt als stabile Matrix-Identität erhalten.
     */
    public function deriveMatrixId(): string
    {
        $domain = config('matrix.domain');
        $local  = self::sanitizeMxidLocalpart($this->name)
                . '.'
                . self::sanitizeMxidLocalpart($this->lastname);

        return '@'.$local.':'.$domain;
    }

    /**
     * Wie deriveMatrixId(), aber prüft auf Kollisionen in matrix_accounts
     * und hängt eine Zahl an, falls der Localpart bereits an einen anderen
     * Spieler vergeben ist (@max.muster → @max.muster2 → @max.muster3 …).
     */
    public function uniqueMatrixId(): string
    {
        $domain = config('matrix.domain');
        $base   = self::sanitizeMxidLocalpart($this->name)
                . '.'
                . self::sanitizeMxidLocalpart($this->lastname);

        $candidate = '@'.$base.':'.$domain;
        $i = 2;

        while (MatrixAccount::where('mxid', $candidate)
                ->where('player_id', '!=', $this->id)
                ->exists()) {
            $candidate = '@'.$base.$i.':'.$domain;
            $i++;
        }

        return $candidate;
    }

    /**
     * Konvertiert einen Namensteil in einen gültigen Matrix-Localpart-Abschnitt.
     * Matrix-Spec erlaubt nur [a-z0-9._-] im Localpart.
     */
    private static function sanitizeMxidLocalpart(string $name): string
    {
        // Umlaute und häufige Akzente in ASCII-Äquivalente auflösen
        $name = strtr($name, [
            'ä' => 'ae', 'ö' => 'oe', 'ü' => 'ue', 'ß' => 'ss',
            'Ä' => 'ae', 'Ö' => 'oe', 'Ü' => 'ue',
            'à' => 'a',  'á' => 'a',  'â' => 'a',  'ã' => 'a',
            'è' => 'e',  'é' => 'e',  'ê' => 'e',  'ë' => 'e',
            'ì' => 'i',  'í' => 'i',  'î' => 'i',  'ï' => 'i',
            'ò' => 'o',  'ó' => 'o',  'ô' => 'o',  'õ' => 'o',
            'ù' => 'u',  'ú' => 'u',  'û' => 'u',
            'ý' => 'y',  'ÿ' => 'y',  'ç' => 'c',  'ñ' => 'n',
            'ø' => 'o',  'å' => 'a',
            'À' => 'a',  'Á' => 'a',  'Â' => 'a',  'Ã' => 'a',
            'È' => 'e',  'É' => 'e',  'Ê' => 'e',  'Ë' => 'e',
            'Ì' => 'i',  'Í' => 'i',  'Î' => 'i',  'Ï' => 'i',
            'Ò' => 'o',  'Ó' => 'o',  'Ô' => 'o',  'Õ' => 'o',
            'Ù' => 'u',  'Ú' => 'u',  'Û' => 'u',
            'Ý' => 'y',  'Ç' => 'c',  'Ñ' => 'n',
            'Ø' => 'o',  'Å' => 'a',
        ]);

        $name = mb_strtolower($name);
        $name = str_replace([' ', '-'], '_', $name);
        $name = preg_replace('/[^a-z0-9._-]/', '', $name);
        $name = trim($name, '._-');

        return $name !== '' ? $name : 'user';
    }
}
