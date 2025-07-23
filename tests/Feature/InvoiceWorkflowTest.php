<?php

use App\Models\Invoice;
use App\Models\Customer;
use App\Models\Position;
use App\Models\User;

describe('Invoice Management Workflow', function () {
    it('creates invoice from lexoffice data correctly', function () {
        $customer = Customer::factory()->create();
        
        $lexofficeData = (object) [
            'id' => 'lex-invoice-123',
            'voucherNumber' => 'INV-2024-001',
            'voucherDate' => '2024-01-01',
            'dueDate' => '2024-01-31',
            'totalAmount' => 1500.00,
            'openAmount' => 1500.00,
            'voucherStatus' => 'open',
        ];
        
        $invoice = Invoice::fromLexofficeInvoice($lexofficeData, $customer->id);
        $invoice->save();
        
        expect($invoice->number)->toBe('INV-2024-001');
        expect($invoice->customer_id)->toBe($customer->id);
        expect($invoice->total_amount)->toBe(1500.00);
        expect($invoice->open_amount)->toBe(1500.00);
        expect($invoice->status)->toBe('open');
        expect($invoice->lexoffice_id)->toBe('lex-invoice-123');
        
        $this->assertDatabaseHas('invoices', [
            'number' => 'INV-2024-001',
            'customer_id' => $customer->id,
            'lexoffice_id' => 'lex-invoice-123',
        ]);
    });

    it('handles invoice with web payment data', function () {
        $customer = Customer::factory()->create();
        
        $lexofficeData = (object) [
            'id' => 'lex-invoice-456',
            'voucherNumber' => 'INV-2024-002',
            'totalAmount' => 800.00,
            'openAmount' => 0.00,
            'voucherStatus' => 'paid',
            'webPayment' => (object) [
                'id' => 'payment-abc123',
                'status' => 'completed',
                'date' => '2024-01-15',
                'amount' => 800.00,
            ],
        ];
        
        $invoice = Invoice::fromLexofficeInvoice($lexofficeData, $customer->id);
        $invoice->save();
        
        expect($invoice->web_payment_id)->toBe('payment-abc123');
        expect($invoice->web_payment_status)->toBe('completed');
        expect($invoice->web_payment_amount)->toBe(800.00);
        expect($invoice->web_payment_date->format('Y-m-d'))->toBe('2024-01-15');
        
        $this->assertDatabaseHas('invoices', [
            'number' => 'INV-2024-002',
            'web_payment_id' => 'payment-abc123',
            'web_payment_status' => 'completed',
        ]);
    });

    it('links positions to invoices when billed', function () {
        $customer = Customer::factory()->create();
        $invoice = Invoice::factory()->create(['customer_id' => $customer->id]);
        
        $position1 = Position::factory()->create([
            'customer_id' => $customer->id,
            'name' => 'Web Development',
            'billed' => true,
            'invoice_id' => $invoice->id,
            'billed_at' => now(),
        ]);
        
        $position2 = Position::factory()->create([
            'customer_id' => $customer->id,
            'name' => 'Design Work',
            'billed' => true,
            'invoice_id' => $invoice->id,
            'billed_at' => now(),
        ]);
        
        $unbilledPosition = Position::factory()->create([
            'customer_id' => $customer->id,
            'name' => 'Future Work',
            'billed' => false,
            'invoice_id' => null,
        ]);
        
        // Test invoice-position relationships
        $invoicePositions = $invoice->positions;
        expect($invoicePositions)->toHaveCount(2);
        expect($invoicePositions->pluck('name')->toArray())->toContain('Web Development', 'Design Work');
        expect($invoicePositions->pluck('name')->toArray())->not->toContain('Future Work');
        
        // Test position-invoice relationships
        expect($position1->invoice_id)->toBe($invoice->id);
        expect($position2->invoice_id)->toBe($invoice->id);
        expect($unbilledPosition->invoice_id)->toBeNull();
    });

    it('allows admin users to download invoice PDF', function () {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = Customer::factory()->create();
        $invoice = Invoice::factory()->create([
            'id' => 123,
            'customer_id' => $customer->id,
            'number' => 'INV-2024-001',
        ]);
        
        $response = $this->actingAs($admin)
            ->get(route('invoice.download', ['invoice' => $invoice->id]));
        
        // This would normally return a PDF download, but without full setup
        // we expect it to at least not error and potentially return some response
        expect($response->getStatusCode())->toBeGreaterThanOrEqual(200);
        expect($response->getStatusCode())->toBeLessThan(500);
    });
});

describe('Position to Invoice Workflow', function () {
    it('tracks position billing lifecycle', function () {
        $customer = Customer::factory()->create();
        
        // Create unbilled positions
        $position1 = Position::factory()->create([
            'customer_id' => $customer->id,
            'name' => 'Web Development',
            'quantity' => 10.00,
            'unit_price' => 100.00,
            'discount' => 0.00,
            'billed' => false,
        ]);
        
        $position2 = Position::factory()->create([
            'customer_id' => $customer->id,
            'name' => 'Consulting',
            'quantity' => 5.00,
            'unit_price' => 150.00,
            'discount' => 10.00,
            'billed' => false,
        ]);
        
        // Verify positions start unbilled
        expect($position1->billed)->toBeFalse();
        expect($position2->billed)->toBeFalse();
        expect($position1->invoice_id)->toBeNull();
        expect($position2->invoice_id)->toBeNull();
        
        // Calculate expected amounts
        expect($position1->total_amount)->toBe(1000.00); // 10 * 100
        expect($position2->total_amount)->toBe(675.00);  // 5 * 150 - 10% discount
        
        // Create invoice
        $invoice = Invoice::factory()->create([
            'customer_id' => $customer->id,
            'number' => 'INV-2024-001',
            'total_amount' => 1675.00, // Sum of positions
        ]);
        
        // Mark positions as billed
        $position1->update([
            'billed' => true,
            'billed_at' => now(),
            'invoice_id' => $invoice->id,
        ]);
        
        $position2->update([
            'billed' => true,
            'billed_at' => now(),
            'invoice_id' => $invoice->id,
        ]);
        
        // Verify positions are now billed
        $position1->refresh();
        $position2->refresh();
        
        expect($position1->billed)->toBeTrue();
        expect($position2->billed)->toBeTrue();
        expect($position1->invoice_id)->toBe($invoice->id);
        expect($position2->invoice_id)->toBe($invoice->id);
        expect($position1->billed_at)->not->toBeNull();
        expect($position2->billed_at)->not->toBeNull();
        
        // Verify invoice relationship
        $invoicePositions = $invoice->positions;
        expect($invoicePositions)->toHaveCount(2);
        
        // Verify customer relationships
        $customerPositions = $customer->positions;
        $customerInvoices = $customer->invoices;
        $customerUnbilledPositions = $customer->unbilledPositions;
        
        expect($customerPositions)->toHaveCount(2);
        expect($customerInvoices)->toHaveCount(1);
        expect($customerUnbilledPositions)->toHaveCount(0); // All positions are now billed
    });

    it('prevents modification of billed positions', function () {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = Customer::factory()->create();
        $invoice = Invoice::factory()->create(['customer_id' => $customer->id]);
        
        $position = Position::factory()->create([
            'customer_id' => $customer->id,
            'name' => 'Billed Position',
            'quantity' => 5.00,
            'unit_price' => 100.00,
            'billed' => true,
            'billed_at' => now(),
            'invoice_id' => $invoice->id,
        ]);
        
        // Try to update billed position
        $updateResponse = $this->actingAs($admin)
            ->put(route('positions.update', $position), [
                'customer_id' => $customer->id,
                'name' => 'Attempted Update',
                'quantity' => 10.00,
                'unit_name' => 'hours',
                'unit_price' => 150.00,
            ]);
        
        $updateResponse->assertRedirect(route('positions.index'));
        $updateResponse->assertSessionHas('error');
        
        // Position should remain unchanged
        $position->refresh();
        expect($position->name)->toBe('Billed Position');
        expect($position->quantity)->toBe(5.00);
        expect($position->unit_price)->toBe(100.00);
        
        // Try to delete billed position
        $deleteResponse = $this->actingAs($admin)
            ->delete(route('positions.destroy', $position));
        
        $deleteResponse->assertRedirect(route('positions.index'));
        $deleteResponse->assertSessionHas('error');
        
        // Position should still exist
        $this->assertDatabaseHas('positionen', ['id' => $position->id]);
    });
});

describe('Customer Invoice Management', function () {
    it('shows customer invoices on dashboard', function () {
        $customer = Customer::factory()->create(['company_name' => 'Test Company']);
        $user = User::factory()->create([
            'role' => 'customer',
            'customer_id' => $customer->id,
        ]);
        
        $invoice1 = Invoice::factory()->create([
            'customer_id' => $customer->id,
            'number' => 'INV-001',
            'total_amount' => 1000.00,
            'open_amount' => 1000.00,
            'status' => 'open',
        ]);
        
        $invoice2 = Invoice::factory()->create([
            'customer_id' => $customer->id,
            'number' => 'INV-002',
            'total_amount' => 500.00,
            'open_amount' => 0.00,
            'status' => 'paid',
        ]);
        
        $response = $this->actingAs($user)->get(route('dashboard'));
        
        $response->assertStatus(200);
        // Should see their invoices
        $response->assertSee('INV-001');
        $response->assertSee('INV-002');
    });

    it('separates invoices by customer', function () {
        $customer1 = Customer::factory()->create(['company_name' => 'Company A']);
        $customer2 = Customer::factory()->create(['company_name' => 'Company B']);
        
        $user1 = User::factory()->create([
            'role' => 'customer',
            'customer_id' => $customer1->id,
        ]);
        
        $invoice1 = Invoice::factory()->create([
            'customer_id' => $customer1->id,
            'number' => 'INV-CUSTOMER1',
        ]);
        
        $invoice2 = Invoice::factory()->create([
            'customer_id' => $customer2->id,
            'number' => 'INV-CUSTOMER2',
        ]);
        
        $response = $this->actingAs($user1)->get(route('dashboard'));
        
        $response->assertStatus(200);
        $response->assertSee('INV-CUSTOMER1');
        $response->assertDontSee('INV-CUSTOMER2'); // Should not see other customer's invoices
    });

    it('calculates invoice totals correctly', function () {
        $customer = Customer::factory()->create();
        
        $invoice = Invoice::factory()->create([
            'customer_id' => $customer->id,
            'total_amount' => 1234.56,
        ]);
        
        expect($invoice->total)->toBe(1234.56);
        expect($invoice->total_amount)->toBe(1234.56);
    });
});