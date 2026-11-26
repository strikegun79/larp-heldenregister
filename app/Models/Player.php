<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Player extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * DSGVO: Profilfoto beim Hard-Delete aus dem Storage entfernen.
     * Soft-Delete (wiederherstellbar) lässt das Bild erhalten; anonymize() löscht es separat.
     */
    protected static function booted(): void
    {
        static::forceDeleting(function (Player $player) {
            if ($player->image) {
                Storage::disk('public')->delete($player->image);
            }
        });
    }

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
        'allergien',
        'medikamente',
        'health_data_consent_at',
        'parental_consent_at',
        'active_hero_id',
        'legacy_id',
    ];

    protected $casts = [
        'dayofbirth' => 'date',
        'active' => 'boolean',
        'address_same_as_guardian' => 'boolean',
        'health_data_consent_at' => 'datetime',
        'parental_consent_at' => 'datetime',
        // Issue #31: Talk-Freigabe (nicht massenzuweisbar, wird gezielt gesetzt).
        'talk_approved_at' => 'datetime',
        'talk_consent_at' => 'datetime',
        'talk_account_ready_at' => 'datetime',
        'nextcloud_account_created' => 'boolean',
        // Issue #42: Zeitpunkt der DSGVO-Anonymisierung nach dem Löschen.
        'anonymized_at' => 'datetime',
    ];

    /** Issue #42: Frist zwischen Löschen und Anonymisierung (Wiederherstellung möglich). */
    public const ANONYMIZE_AFTER_DAYS = 30;

    /** Issue #31: Mindestalter für Nextcloud Talk. */
    public const TALK_MIN_AGE = 14;

    /** Aktuelles Alter in Jahren bzw. null (PLAY-07). */
    public function getAgeAttribute(): ?int
    {
        return $this->dayofbirth?->age;
    }

    /** Issue #31: Unter 18 → Eltern müssen der Talk-Nutzung zustimmen. Ohne Geburtsdatum vorsichtshalber ja. */
    public function isTalkMinor(): bool
    {
        return $this->age === null || $this->age < 18;
    }

    /** Issue #31: Warum Talk (noch) nicht freigegeben werden kann – null = möglich. */
    public function talkApprovalBlocker(): ?string
    {
        if ($this->age === null) {
            return 'Kein Geburtsdatum hinterlegt.';
        }
        if ($this->age < self::TALK_MIN_AGE) {
            return 'Talk ist erst ab '.self::TALK_MIN_AGE.' Jahren möglich.';
        }

        return null;
    }

    /** Issue #31: Freigabe und (bei U18) Elternerlaubnis liegen vor → Konto kann eingerichtet werden. */
    public function talkReadyForAccount(): bool
    {
        return $this->talk_approved_at !== null && (! $this->isTalkMinor() || $this->talk_consent_at !== null);
    }

    /**
     * Issue #31: Talk-Status für Anzeige.
     *
     * @return 'none'|'consent'|'pending'|'ready'|'error'
     */
    public function talkStatus(): string
    {
        return match (true) {
            $this->talk_approved_at === null => 'none',
            ! $this->talkReadyForAccount() => 'consent',
            $this->talk_account_ready_at !== null => 'ready',
            filled($this->talk_error) => 'error',
            default => 'pending',
        };
    }

    public function talkStatusLabel(): string
    {
        return match ($this->talkStatus()) {
            'none' => 'nicht freigegeben',
            'consent' => 'wartet auf Erlaubnis der Eltern',
            'pending' => 'Konto wird eingerichtet',
            'ready' => 'Konto eingerichtet',
            'error' => 'Fehler',
        };
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
     *
     * @return BelongsToMany<User, $this>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withPivot('self');
    }

    /**
     * Prüft ob der verknüpfte Nutzer eine bestimmte E-Mail-Benachrichtigung aktiviert hat.
     * Gibt true zurück wenn kein Nutzerkonto verknüpft ist (Versand als Fallback).
     */
    public function notificationEnabled(string $column): bool
    {
        $user = $this->relationLoaded('users')
            ? $this->users->first()
            : $this->users()->first();

        return $user?->$column ?? true;
    }

    /**
     * Voller Name des Spielers.
     */
    public function getFullNameAttribute(): string
    {
        return trim("{$this->name} {$this->lastname}");
    }

    public function auditLabel(): string
    {
        return $this->full_name;
    }

    /**
     * Talk-Protokoll dieses Spielers (Konto anlegen, Gruppe, Deaktivieren).
     *
     * @return HasMany<TalkLog, $this>
     */
    public function talkLogs(): HasMany
    {
        return $this->hasMany(TalkLog::class);
    }

    /**
     * DSGVO Art. 17: Spieler anonymisieren (Recht auf Löschung).
     * Überschreibt alle Klardaten, löscht das Profilfoto und soft-deleted.
     * Verknüpfte Helden bleiben erhalten (Charaktername ist kein Realname).
     */
    public function anonymize(): void
    {
        if ($this->image) {
            Storage::disk('public')->delete($this->image);
        }

        $this->forceFill([
            'name' => 'Gelöscht',
            'lastname' => null,
            'email' => null,
            'dayofbirth' => null,
            'gender' => null,
            'image' => null,
            'street' => null,
            'house_number' => null,
            'zip' => null,
            'city' => null,
            'allergien' => null,
            'medikamente' => null,
            'health_data_consent_at' => null,
            'parental_consent_at' => null,
            'address_same_as_guardian' => false,
            // Issue #42: Talk-Daten (Konto wird beim Löschen deaktiviert).
            'talk_approved_at' => null,
            'talk_approved_by' => null,
            'talk_consent_at' => null,
            'talk_consent_by' => null,
            'talk_consent_version' => null,
            'talk_account_ready_at' => null,
            'talk_error' => null,
            'nextcloud_user_id' => null,
            'nextcloud_account_created' => false,
            'nextcloud_group' => null,
            'anonymized_at' => now(),
        ])->save();

        if (! $this->trashed()) {
            $this->delete();
        }
    }

    /**
     * Konvertiert einen Namensteil in einen Benutzernamen-Abschnitt (nur [a-z0-9._-]),
     * z. B. für Nextcloud-Talk-Konten (vorname.nachname).
     */
    public static function sanitizeUsernamePart(string $name): string
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
