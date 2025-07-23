<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Domain extends Model
{
    protected $fillable = [
        'customer_id',
        'tld',
        'fqdn',
        'register_date',
        'due_date',
        'provider_id',
        'status',
        'billing_interval',
    ];

    protected $casts = [
        'register_date' => 'date',
        'due_date' => 'date',
        'billing_interval' => 'integer',
    ];

    /**
     * Beziehung zum Kunden
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Beziehung zum Domain-Provider
     */
    public function provider(): BelongsTo
    {
        return $this->belongsTo(DomainProvider::class, 'provider_id');
    }

    /**
     * Scope für aktive Domains
     */
    public function scopeActive($query)
    {
        return $query->where('status', '!=', 'deleted');
    }

    /**
     * Scope für abgelaufene Domains
     */
    public function scopeExpired($query)
    {
        return $query->where('due_date', '<', now())->where('status', '!=', 'deleted');
    }

    /**
     * Scope für bald ablaufende Domains (nächste 30 Tage)
     */
    public function scopeExpiringSoon($query)
    {
        return $query->whereBetween('due_date', [now(), now()->addDays(30)])
                    ->where('status', '!=', 'deleted');
    }

    /**
     * Berechne die Tage bis zum Ablauf
     */
    public function getDaysUntilExpiryAttribute()
    {
        if (!$this->due_date) {
            return null;
        }
        
        $dueDate = new \DateTime((string) $this->due_date);
        $now = new \DateTime();
        $diff = $now->diff($dueDate);
        
        return $diff->invert ? -$diff->days : $diff->days;
    }

    /**
     * Prüfe ob Domain bald abläuft
     */
    public function getIsExpiringSoonAttribute()
    {
        $days = $this->getDaysUntilExpiryAttribute();
        return $days !== null && $days <= 30 && $days >= 0;
    }

    /**
     * Prüfe ob Domain abgelaufen ist
     */
    public function getIsExpiredAttribute()
    {
        $days = $this->getDaysUntilExpiryAttribute();
        return $days !== null && $days < 0;
    }
}
