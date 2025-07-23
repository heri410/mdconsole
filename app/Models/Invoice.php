<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    protected $fillable = [
        'number',
        'customer_id',
        'date',
        'due_date',
        'total_amount',
        'open_amount',
        'status',
        'lexoffice_id',
        'created_at',
        'updated_at',
        'deleted_at',
        'lexoffice_data',
        'web_payment_id',
        'web_payment_status',
        'web_payment_date',
        'web_payment_amount',
    ];
    
    protected $casts = [
        'date' => 'datetime',
        'due_date' => 'datetime',
        'web_payment_date' => 'datetime',
        'total_amount' => 'decimal:2',
        'open_amount' => 'decimal:2',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public static function fromLexofficeInvoice($invoice, $customerId = null): Invoice
    {
        // totalAmount und openAmount sind bereits als Dezimalzahlen verfügbar
        $totalAmount = 0.00;
        $openAmount = 0.00;
        
        if (isset($invoice->totalAmount)) {
            $totalAmount = (float) $invoice->totalAmount;
        } elseif (isset($invoice->totalPrice->totalGrossAmount)) {
            $totalAmount = (float) $invoice->totalPrice->totalGrossAmount;
        }
        
        if (isset($invoice->openAmount)) {
            $openAmount = (float) $invoice->openAmount;
        } else {
            // Falls openAmount nicht verfügbar ist, verwende totalAmount als Fallback
            $openAmount = $totalAmount;
        }
        
        $data = [
            'number' => $invoice->voucherNumber ?? null,
            'customer_id' => $customerId,
            'date' => isset($invoice->voucherDate) ? date('Y-m-d', strtotime($invoice->voucherDate)) : null,
            'due_date' => isset($invoice->dueDate) ? date('Y-m-d', strtotime($invoice->dueDate)) : null,
            'total_amount' => $totalAmount,
            'open_amount' => $openAmount,
            'status' => $invoice->voucherStatus ?? null,
            'lexoffice_id' => $invoice->id ?? null,
            'lexoffice_data' => json_encode($invoice),
        ];
        
        // Optional: weitere Felder mappen (z.B. Web-Payment)
        if (isset($invoice->webPayment)) {
            $data['web_payment_id'] = $invoice->webPayment->id ?? null;
            $data['web_payment_status'] = $invoice->webPayment->status ?? null;
            $data['web_payment_date'] = isset($invoice->webPayment->date) ? date('Y-m-d', strtotime($invoice->webPayment->date)) : null;
            $data['web_payment_amount'] = $invoice->webPayment->amount ?? null;
        }
        
        $model = new Invoice($data);
        return $model;
    }
    
    /**
     * Get the total attribute (accessor for total_amount).
     */
    public function getTotalAttribute()
    {
        return $this->total_amount;
    }
}
