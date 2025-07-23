<?php

use App\Models\Invoice;
use App\Models\Customer;
use App\Models\Position;

describe('Invoice Model', function () {
    it('can be created with valid attributes', function () {
        $invoice = Invoice::make([
            'number' => 'INV-2024-001',
            'customer_id' => 1,
            'date' => '2024-01-01',
            'due_date' => '2024-01-31',
            'total_amount' => 1000.00,
            'open_amount' => 1000.00,
            'status' => 'open',
            'lexoffice_id' => 'lex-123',
        ]);

        expect($invoice->number)->toBe('INV-2024-001');
        expect($invoice->customer_id)->toBe(1);
        expect($invoice->total_amount)->toBe(1000.00);
        expect($invoice->open_amount)->toBe(1000.00);
        expect($invoice->status)->toBe('open');
        expect($invoice->lexoffice_id)->toBe('lex-123');
    });

    it('has correct fillable attributes', function () {
        $invoice = new Invoice();
        $expected = [
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
        
        expect($invoice->getFillable())->toBe($expected);
    });

    it('casts date attributes correctly', function () {
        $invoice = Invoice::make([
            'date' => '2024-01-01',
            'due_date' => '2024-01-31',
            'web_payment_date' => '2024-01-15',
            'total_amount' => '1500.50',
            'open_amount' => '750.25',
        ]);

        expect($invoice->date)->toBeInstanceOf(\Carbon\Carbon::class);
        expect($invoice->due_date)->toBeInstanceOf(\Carbon\Carbon::class);
        expect($invoice->web_payment_date)->toBeInstanceOf(\Carbon\Carbon::class);
        expect($invoice->total_amount)->toBeFloat();
        expect($invoice->open_amount)->toBeFloat();
    });

    it('has total accessor for total_amount', function () {
        $invoice = Invoice::make([
            'total_amount' => 999.99,
        ]);

        expect($invoice->total)->toBe(999.99);
    });
});

describe('Invoice Relationships', function () {
    it('belongs to a customer', function () {
        $invoice = new Invoice();
        $relation = $invoice->customer();
        
        expect($relation)->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class);
        expect($relation->getRelated())->toBeInstanceOf(Customer::class);
    });

    it('has many positions', function () {
        $invoice = new Invoice();
        $relation = $invoice->positions();
        
        expect($relation)->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class);
        expect($relation->getRelated())->toBeInstanceOf(Position::class);
    });
});

describe('Invoice Lexoffice Integration', function () {
    it('creates invoice from lexoffice data with basic fields', function () {
        $lexofficeData = (object) [
            'id' => 'lex-invoice-123',
            'voucherNumber' => 'INV-001',
            'voucherDate' => '2024-01-01',
            'dueDate' => '2024-01-31',
            'totalAmount' => 1500.00,
            'openAmount' => 1500.00,
            'voucherStatus' => 'open',
        ];

        $invoice = Invoice::fromLexofficeInvoice($lexofficeData, 1);

        expect($invoice->number)->toBe('INV-001');
        expect($invoice->customer_id)->toBe(1);
        expect($invoice->date->format('Y-m-d'))->toBe('2024-01-01');
        expect($invoice->due_date->format('Y-m-d'))->toBe('2024-01-31');
        expect($invoice->total_amount)->toBe(1500.00);
        expect($invoice->open_amount)->toBe(1500.00);
        expect($invoice->status)->toBe('open');
        expect($invoice->lexoffice_id)->toBe('lex-invoice-123');
        expect($invoice->lexoffice_data)->toBeString();
    });

    it('creates invoice from lexoffice data with totalPrice structure', function () {
        $lexofficeData = (object) [
            'id' => 'lex-invoice-456',
            'voucherNumber' => 'INV-002',
            'totalPrice' => (object) [
                'totalGrossAmount' => 2000.00,
            ],
            'voucherStatus' => 'paid',
        ];

        $invoice = Invoice::fromLexofficeInvoice($lexofficeData);

        expect($invoice->total_amount)->toBe(2000.00);
        expect($invoice->open_amount)->toBe(2000.00); // Falls back to totalAmount
        expect($invoice->status)->toBe('paid');
    });

    it('creates invoice with web payment data', function () {
        $lexofficeData = (object) [
            'id' => 'lex-invoice-789',
            'voucherNumber' => 'INV-003',
            'totalAmount' => 500.00,
            'openAmount' => 0.00,
            'webPayment' => (object) [
                'id' => 'payment-123',
                'status' => 'completed',
                'date' => '2024-01-15',
                'amount' => 500.00,
            ],
        ];

        $invoice = Invoice::fromLexofficeInvoice($lexofficeData);

        expect($invoice->web_payment_id)->toBe('payment-123');
        expect($invoice->web_payment_status)->toBe('completed');
        expect($invoice->web_payment_date->format('Y-m-d'))->toBe('2024-01-15');
        expect($invoice->web_payment_amount)->toBe(500.00);
    });

    it('handles missing optional fields gracefully', function () {
        $lexofficeData = (object) [
            'id' => 'lex-invoice-minimal',
        ];

        $invoice = Invoice::fromLexofficeInvoice($lexofficeData);

        expect($invoice->number)->toBeNull();
        expect($invoice->date)->toBeNull();
        expect($invoice->due_date)->toBeNull();
        expect($invoice->total_amount)->toBe(0.00);
        expect($invoice->open_amount)->toBe(0.00);
        expect($invoice->status)->toBeNull();
        expect($invoice->lexoffice_id)->toBe('lex-invoice-minimal');
    });

    it('stores lexoffice data as JSON', function () {
        $lexofficeData = (object) [
            'id' => 'test-123',
            'customField' => 'test-value',
            'nested' => (object) [
                'field' => 'nested-value',
            ],
        ];

        $invoice = Invoice::fromLexofficeInvoice($lexofficeData);
        $decodedData = json_decode($invoice->lexoffice_data);

        expect($decodedData->id)->toBe('test-123');
        expect($decodedData->customField)->toBe('test-value');
        expect($decodedData->nested->field)->toBe('nested-value');
    });
});