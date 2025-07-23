<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DomainPrice extends Model
{
    protected $fillable = [
        'provider_id',
        'tld',
        'price_renew',
        'price_transfer',
        'price_create',
        'price_restore',
        'price_change_owner',
        'price_update',
    ];

    protected $casts = [
        'price_renew' => 'decimal:2',
        'price_transfer' => 'decimal:2',
        'price_create' => 'decimal:2',
        'price_restore' => 'decimal:2',
        'price_change_owner' => 'decimal:2',
        'price_update' => 'decimal:2',
    ];

    /**
     * Beziehung zum Provider
     */
    public function provider(): BelongsTo
    {
        return $this->belongsTo(DomainProvider::class, 'provider_id');
    }

    /**
     * Scope für eine bestimmte TLD
     */
    public function scopeForTld($query, $tld)
    {
        return $query->where('tld', $tld);
    }

    /**
     * Scope für einen bestimmten Provider
     */
    public function scopeForProvider($query, $providerId)
    {
        return $query->where('provider_id', $providerId);
    }
}
