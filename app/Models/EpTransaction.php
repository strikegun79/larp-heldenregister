<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EpTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'hero_id',
        'adventure_id',
        'ep_transaction_type_id',
        'ep_count',
        'transacted_at',
        'legacy_id',
    ];

    protected $casts = [
        'ep_count' => 'decimal:2',
        'transacted_at' => 'datetime',
    ];

    public function hero(): BelongsTo
    {
        return $this->belongsTo(Hero::class);
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(EpTransactionType::class, 'ep_transaction_type_id');
    }

    /**
     * Das Abenteuer, aus dem die EP stammen (nur bei „Abenteuer bestritten").
     */
    public function adventure(): BelongsTo
    {
        return $this->belongsTo(Adventure::class);
    }

    /**
     * Vorzeichenbehafteter EP-Betrag (Gutschrift positiv, Kosten negativ).
     */
    public function signedAmount(): float
    {
        $credit = $this->type?->is_credit ?? true;

        return (float) $this->ep_count * ($credit ? 1 : -1);
    }
}
