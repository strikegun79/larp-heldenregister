<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

/**
 * Startseiten-Nachricht (MOTD) mit Verlauf. Höchstens eine Nachricht ist
 * aktiv; angezeigt wird sie nur innerhalb ihres (optionalen) Zeitraums.
 */
class Motd extends Model
{
    /** Farbvarianten → CSS-Klasse der Fomantic-Message und Anzeige-Label. */
    public const VARIANTS = [
        // Neutral: Optik der Standard-Segmente (beige Hintergrund, brauner Rahmen)
        'neutral' => ['class' => 'motd-neutral', 'label' => 'Neutral (braun/beige)'],
        'info'    => ['class' => 'info',         'label' => 'Info (blau)'],
        'warning' => ['class' => 'warning',      'label' => 'Hinweis (gelb)'],
        'success' => ['class' => 'positive',     'label' => 'Erfolg (grün)'],
        'error'   => ['class' => 'negative',     'label' => 'Warnung (rot)'],
    ];

    /** Status → Anzeige-Label und Farbe in der Verwaltungsliste. */
    public const STATUS_LABELS = [
        'active'   => ['label' => 'aktiv',      'color' => 'text-green-700'],
        'planned'  => ['label' => 'geplant',    'color' => 'text-amber-600'],
        'expired'  => ['label' => 'abgelaufen', 'color' => 'text-stone-500'],
        'inactive' => ['label' => 'inaktiv',    'color' => 'text-stone-500'],
    ];

    protected $fillable = [
        'title',
        'html',
        'image',
        'image_link',
        'variant',
        'dismissible',
        'start_at',
        'end_at',
        'is_active',
        'content_updated_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'dismissible'        => 'boolean',
        'is_active'          => 'boolean',
        'start_at'           => 'datetime',
        'end_at'             => 'datetime',
        'content_updated_at' => 'datetime',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /** Aktiv und innerhalb des Zeitraums. */
    public function scopeVisibleNow(Builder $query): Builder
    {
        $now = now();

        return $query->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('start_at')->orWhere('start_at', '<=', $now))
            ->where(fn ($q) => $q->whereNull('end_at')->orWhere('end_at', '>=', $now));
    }

    /** Aktuell auf der Startseite anzuzeigende Nachricht (mit Inhalt) oder null. */
    public static function current(): ?self
    {
        $motd = static::visibleNow()->latest('updated_at')->first();

        return $motd && (filled($motd->html) || filled($motd->image)) ? $motd : null;
    }

    /** Diese Nachricht aktivieren, alle anderen deaktivieren. */
    public function activate(): void
    {
        DB::transaction(function () {
            static::whereKeyNot($this->getKey())->where('is_active', true)->update(['is_active' => false]);
            $this->update(['is_active' => true]);
        });
    }

    /** Status für die Verwaltungsliste: active, planned, expired oder inactive. */
    public function getStatusAttribute(): string
    {
        if ($this->end_at && $this->end_at->isPast()) {
            return 'expired';
        }
        if (! $this->is_active) {
            return 'inactive';
        }
        if ($this->start_at && $this->start_at->isFuture()) {
            return 'planned';
        }

        return 'active';
    }

    public function getCssClassAttribute(): string
    {
        return self::VARIANTS[$this->variant]['class'] ?? 'info';
    }

    public function getVariantLabelAttribute(): string
    {
        return self::VARIANTS[$this->variant]['label'] ?? $this->variant;
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->image ? '/storage/'.$this->image : null;
    }

    /** Externe Links (anderer Host als das Portal) öffnen in neuem Tab. */
    public function getImageLinkExternalAttribute(): bool
    {
        return $this->image_link
            && parse_url($this->image_link, PHP_URL_HOST) !== request()->getHost();
    }

    /** Schlüssel fürs Wegklicken: neue Inhalte erscheinen erneut. */
    public function getDismissKeyAttribute(): string
    {
        return 'motd_dismissed_'.$this->id.'_'.($this->content_updated_at?->timestamp ?? 0);
    }
}
