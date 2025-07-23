<?php

use App\Models\Position;
use App\Models\Customer;
use App\Models\Invoice;

describe('Position Model', function () {
    it('can be created with valid attributes', function () {
        $position = Position::make([
            'customer_id' => 1,
            'name' => 'Web Development',
            'description' => 'Frontend development work',
            'quantity' => 10.00,
            'unit_name' => 'hours',
            'unit_price' => 85.50,
            'discount' => 10.00,
            'billed' => false,
        ]);

        expect($position->name)->toBe('Web Development');
        expect($position->description)->toBe('Frontend development work');
        expect($position->quantity)->toBe(10.00);
        expect($position->unit_name)->toBe('hours');
        expect($position->unit_price)->toBe(85.50);
        expect($position->discount)->toBe(10.00);
        expect($position->billed)->toBeFalse();
    });

    it('calculates total amount correctly without discount', function () {
        $position = Position::make([
            'quantity' => 5.00,
            'unit_price' => 100.00,
            'discount' => 0.00,
        ]);

        expect($position->total_amount)->toBe(500.00);
    });

    it('calculates total amount correctly with discount', function () {
        $position = Position::make([
            'quantity' => 10.00,
            'unit_price' => 100.00,
            'discount' => 10.00, // 10% discount
        ]);

        // 10 * 100 = 1000, 10% discount = 100, total = 900
        expect($position->total_amount)->toBe(900.00);
    });

    it('calculates discount amount correctly', function () {
        $position = Position::make([
            'quantity' => 8.00,
            'unit_price' => 50.00,
            'discount' => 15.00, // 15% discount
        ]);

        // 8 * 50 = 400, 15% discount = 60
        expect($position->discount_amount)->toBe(60.00);
    });

    it('handles zero discount correctly', function () {
        $position = Position::make([
            'quantity' => 5.00,
            'unit_price' => 75.00,
            'discount' => 0.00,
        ]);

        expect($position->discount_amount)->toBe(0.00);
        expect($position->total_amount)->toBe(375.00);
    });

    it('rounds calculations to 2 decimal places', function () {
        $position = Position::make([
            'quantity' => 3.33,
            'unit_price' => 33.33,
            'discount' => 33.33, // 33.33% discount
        ]);

        // 3.33 * 33.33 = 110.9889, discount = 36.99, total = 73.99 (rounded)
        expect($position->total_amount)->toBe(73.99);
        expect($position->discount_amount)->toBe(36.99);
    });

    it('has correct table name', function () {
        $position = new Position();
        expect($position->getTable())->toBe('positionen');
    });

    it('has correct fillable attributes', function () {
        $position = new Position();
        $expected = [
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
        
        expect($position->getFillable())->toBe($expected);
    });

    it('casts attributes correctly', function () {
        $position = new Position([
            'quantity' => '10.50',
            'unit_price' => '99.99',
            'discount' => '5.25',
            'billed' => '1',
            'billed_at' => '2024-01-01 12:00:00',
        ]);

        expect($position->quantity)->toBeFloat();
        expect($position->unit_price)->toBeFloat();
        expect($position->discount)->toBeFloat();
        expect($position->billed)->toBeBool();
    });
});

describe('Position Relationships', function () {
    it('belongs to a customer', function () {
        $position = new Position();
        $relation = $position->customer();
        
        expect($relation)->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class);
        expect($relation->getRelated())->toBeInstanceOf(Customer::class);
    });

    it('belongs to an invoice', function () {
        $position = new Position();
        $relation = $position->invoice();
        
        expect($relation)->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class);
        expect($relation->getRelated())->toBeInstanceOf(Invoice::class);
    });
});

describe('Position Scopes', function () {
    it('has unbilled scope', function () {
        $query = Position::unbilled();
        
        // Check that the scope adds the correct where clause
        expect($query->toSql())->toContain('where "billed" = ?');
        expect($query->getBindings())->toContain(false);
    });

    it('has billed scope', function () {
        $query = Position::billed();
        
        // Check that the scope adds the correct where clause
        expect($query->toSql())->toContain('where "billed" = ?');
        expect($query->getBindings())->toContain(true);
    });

    it('has for customer scope', function () {
        $customerId = 123;
        $query = Position::forCustomer($customerId);
        
        // Check that the scope adds the correct where clause
        expect($query->toSql())->toContain('where "customer_id" = ?');
        expect($query->getBindings())->toContain($customerId);
    });
});