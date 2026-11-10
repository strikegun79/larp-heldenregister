<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DataBreachLog extends Model
{
    protected $fillable = [
        'discovered_at',
        'description',
        'affected_data_categories',
        'affected_persons_count',
        'likely_consequences',
        'measures_taken',
        'reportable',
        'reported_to_authority',
        'reported_at',
        'authority_reference',
        'internal_notes',
        'created_by_user_id',
    ];

    protected $casts = [
        'discovered_at'            => 'datetime',
        'reported_at'              => 'datetime',
        'affected_data_categories' => 'array',
        'reportable'               => 'boolean',
        'reported_to_authority'    => 'boolean',
    ];

    public static array $dataCategoryLabels = [
        'kontaktdaten'   => 'Kontaktdaten (Name, Adresse, E-Mail, Telefon)',
        'gesundheit'     => 'Gesundheitsdaten (Allergien, Medikamente)',
        'biometrisch'    => 'Biometrische Daten (Unterschriften)',
        'zugangsdaten'   => 'Zugangsdaten (Passwörter, Tokens)',
        'finanzdaten'    => 'Finanzdaten (Zahlungsstatus, Ermäßigung)',
        'minderjährige'  => 'Daten Minderjähriger',
        'sonstige'       => 'Sonstige personenbezogene Daten',
    ];

    /** Meldepflicht besteht und Frist von 72 h seit Entdeckung läuft noch. */
    public function isWithin72Hours(): bool
    {
        return $this->reportable
            && ! $this->reported_to_authority
            && $this->discovered_at->diffInHours(now()) < 72;
    }

    /** Meldepflicht besteht, nicht gemeldet, 72-h-Frist abgelaufen → überfällig. */
    public function isOverdue(): bool
    {
        return $this->reportable
            && ! $this->reported_to_authority
            && $this->discovered_at->diffInHours(now()) >= 72;
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}
