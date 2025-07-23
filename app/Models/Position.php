<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Position extends Model
{
    protected $table = 'positionen';
    
    protected $fillable = [
        'customer_id',
        'name',
        'description',
        'quantity',
        'unit_name',
        'unit_price',
        'discount',
        'billed',
        'billed_at',
        'invoice_id',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'discount' => 'decimal:2',
        'billed' => 'boolean',
        'billed_at' => 'datetime',
    ];

    /**
     * Get the customer that owns the position.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the invoice this position was billed to.
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * Calculate the total amount for this position (with discount).
     */
    public function getTotalAmountAttribute(): float
    {
        $subtotal = $this->quantity * $this->unit_price;
        $discountAmount = $subtotal * ($this->discount / 100);
        return round($subtotal - $discountAmount, 2);
    }

    /**
     * Calculate the discount amount for this position.
     */
    public function getDiscountAmountAttribute(): float
    {
        $subtotal = $this->quantity * $this->unit_price;
        return round($subtotal * ($this->discount / 100), 2);
    }

    /**
     * Scope for unbilled positions.
     */
    public function scopeUnbilled($query)
    {
        return $query->where('billed', false);
    }

    /**
     * Scope for billed positions.
     */
    public function scopeBilled($query)
    {
        return $query->where('billed', true);
    }

    /**
     * Scope for positions by customer.
     */
    public function scopeForCustomer($query, $customerId)
    {
        return $query->where('customer_id', $customerId);
    }
}
