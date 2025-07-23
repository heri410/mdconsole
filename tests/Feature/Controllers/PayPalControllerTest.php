<?php

use App\Http\Controllers\PayPalController;
use App\Models\User;
use App\Models\Customer;
use App\Models\Invoice;
use App\Services\PayPalService;
use Illuminate\Foundation\Testing\RefreshDatabase;

describe('PayPalController', function () {
    beforeEach(function () {
        $this->paypalService = Mockery::mock(PayPalService::class);
        $this->app->instance(PayPalService::class, $this->paypalService);
    });

    describe('Bulk Payment Initialization', function () {
        it('initiates bulk payment for customer with open invoices', function () {
            $customer = Customer::factory()->create();
            $user = User::factory()->create([
                'role' => 'customer',
                'customer_id' => $customer->id,
            ]);
            
            $invoice1 = Invoice::factory()->create([
                'customer_id' => $customer->id,
                'status' => 'open',
                'total_amount' => 1000.00,
                'open_amount' => 1000.00,
            ]);
            
            $invoice2 = Invoice::factory()->create([
                'customer_id' => $customer->id,
                'status' => 'open',
                'total_amount' => 500.00,
                'open_amount' => 500.00,
            ]);
            
            // Mock PayPal service
            $this->paypalService->shouldReceive('testConnection')
                ->once()
                ->andReturn(['success' => true]);
                
            $this->paypalService->shouldReceive('createBulkPayment')
                ->once()
                ->with(Mockery::type('Illuminate\Database\Eloquent\Collection'))
                ->andReturn([
                    'success' => true,
                    'order_id' => 'paypal-order-123',
                    'approval_url' => 'https://www.paypal.com/checkoutnow?token=ABC123',
                    'total_amount' => 1500.00,
                ]);
            
            $response = $this->actingAs($user)
                ->post(route('paypal.bulk.pay'));
            
            $response->assertRedirect('https://www.paypal.com/checkoutnow?token=ABC123');
            
            // Check session data
            expect(session('paypal_order_id'))->toBe('paypal-order-123');
            expect(session('paypal_invoices'))->toBe([$invoice1->id, $invoice2->id]);
            expect(session('paypal_total_amount'))->toBe(1500.00);
        });

        it('rejects non-customer users', function () {
            $user = User::factory()->create(['role' => 'admin', 'customer_id' => null]);
            
            $response = $this->actingAs($user)
                ->post(route('paypal.bulk.pay'));
            
            $response->assertRedirect();
            $response->assertSessionHas('error', 'Sie sind kein registrierter Kunde.');
        });

        it('handles customers with no open invoices', function () {
            $customer = Customer::factory()->create();
            $user = User::factory()->create([
                'role' => 'customer',
                'customer_id' => $customer->id,
            ]);
            
            // Create only paid invoices
            Invoice::factory()->paid()->create(['customer_id' => $customer->id]);
            
            $response = $this->actingAs($user)
                ->post(route('paypal.bulk.pay'));
            
            $response->assertRedirect();
            $response->assertSessionHas('error', 'Keine offenen Rechnungen gefunden.');
        });

        it('handles PayPal connection failure', function () {
            $customer = Customer::factory()->create();
            $user = User::factory()->create([
                'role' => 'customer',
                'customer_id' => $customer->id,
            ]);
            
            Invoice::factory()->open()->create(['customer_id' => $customer->id]);
            
            $this->paypalService->shouldReceive('testConnection')
                ->once()
                ->andReturn(['success' => false, 'error' => 'Connection failed']);
            
            $response = $this->actingAs($user)
                ->post(route('paypal.bulk.pay'));
            
            $response->assertRedirect();
            $response->assertSessionHas('error', 'PayPal-Verbindung fehlgeschlagen. Bitte kontaktieren Sie den Support.');
        });

        it('handles PayPal API errors', function () {
            $customer = Customer::factory()->create();
            $user = User::factory()->create([
                'role' => 'customer',
                'customer_id' => $customer->id,
            ]);
            
            Invoice::factory()->open()->create(['customer_id' => $customer->id]);
            
            $this->paypalService->shouldReceive('testConnection')
                ->once()
                ->andReturn(['success' => true]);
                
            $this->paypalService->shouldReceive('createBulkPayment')
                ->once()
                ->andReturn([
                    'error' => [
                        'message' => 'Invalid payment data',
                    ],
                ]);
            
            $response = $this->actingAs($user)
                ->post(route('paypal.bulk.pay'));
            
            $response->assertRedirect();
            $response->assertSessionHas('error', 'PayPal-Fehler: Invalid payment data');
        });

        it('handles unsuccessful payment creation', function () {
            $customer = Customer::factory()->create();
            $user = User::factory()->create([
                'role' => 'customer',
                'customer_id' => $customer->id,
            ]);
            
            Invoice::factory()->open()->create(['customer_id' => $customer->id]);
            
            $this->paypalService->shouldReceive('testConnection')
                ->once()
                ->andReturn(['success' => true]);
                
            $this->paypalService->shouldReceive('createBulkPayment')
                ->once()
                ->andReturn([
                    'success' => false,
                    'approval_url' => null,
                ]);
            
            $response = $this->actingAs($user)
                ->post(route('paypal.bulk.pay'));
            
            $response->assertRedirect();
            $response->assertSessionHas('error', 'Die Zahlung konnte nicht erstellt werden.');
        });
    });

    describe('Bulk Payment Success', function () {
        it('completes successful bulk payment', function () {
            $customer = Customer::factory()->create();
            $user = User::factory()->create([
                'role' => 'customer',
                'customer_id' => $customer->id,
            ]);
            
            $invoice1 = Invoice::factory()->open()->create([
                'customer_id' => $customer->id,
                'total_amount' => 1000.00,
                'open_amount' => 1000.00,
            ]);
            
            $invoice2 = Invoice::factory()->open()->create([
                'customer_id' => $customer->id,
                'total_amount' => 500.00,
                'open_amount' => 500.00,
            ]);
            
            // Set up session data
            session([
                'paypal_order_id' => 'order-123',
                'paypal_invoices' => [$invoice1->id, $invoice2->id],
                'paypal_total_amount' => 1500.00,
            ]);
            
            $this->paypalService->shouldReceive('capturePayment')
                ->once()
                ->with('order-123')
                ->andReturn([
                    'status' => 'COMPLETED',
                    'purchase_units' => [[
                        'payments' => [
                            'captures' => [[
                                'amount' => ['value' => '1500.00'],
                            ]],
                        ],
                    ]],
                ]);
            
            $response = $this->actingAs($user)
                ->get(route('paypal.bulk.success', ['token' => 'order-123']));
            
            $response->assertRedirect(route('dashboard'));
            $response->assertSessionHas('success');
            
            // Check invoices are marked as paid
            $invoice1->refresh();
            $invoice2->refresh();
            
            expect($invoice1->status)->toBe('paid');
            expect($invoice1->web_payment_status)->toBe('completed');
            expect($invoice1->web_payment_id)->toBe('order-123');
            expect($invoice1->web_payment_amount)->toBe('1500.00');
            
            expect($invoice2->status)->toBe('paid');
            expect($invoice2->web_payment_status)->toBe('completed');
            
            // Check session is cleaned
            expect(session('paypal_invoices'))->toBeNull();
            expect(session('paypal_order_id'))->toBeNull();
            expect(session('paypal_total_amount'))->toBeNull();
        });

        it('handles missing session data', function () {
            $user = User::factory()->create();
            
            $response = $this->actingAs($user)
                ->get(route('paypal.bulk.success', ['token' => 'order-123']));
            
            $response->assertRedirect(route('dashboard'));
            $response->assertSessionHas('error', 'Zahlungsdaten nicht gefunden.');
        });

        it('handles incomplete payment status', function () {
            $customer = Customer::factory()->create();
            $user = User::factory()->create([
                'role' => 'customer',
                'customer_id' => $customer->id,
            ]);
            
            $invoice = Invoice::factory()->open()->create(['customer_id' => $customer->id]);
            
            session([
                'paypal_order_id' => 'order-123',
                'paypal_invoices' => [$invoice->id],
                'paypal_total_amount' => 1000.00,
            ]);
            
            $this->paypalService->shouldReceive('capturePayment')
                ->once()
                ->with('order-123')
                ->andReturn([
                    'status' => 'PENDING',
                ]);
            
            $response = $this->actingAs($user)
                ->get(route('paypal.bulk.success', ['token' => 'order-123']));
            
            $response->assertRedirect(route('dashboard'));
            $response->assertSessionHas('error', 'Die Zahlung konnte nicht durchgeführt werden. Bitte versuchen Sie es später erneut.');
            
            // Invoice should remain unpaid
            $invoice->refresh();
            expect($invoice->status)->not->toBe('paid');
        });

        it('handles payment capture exceptions', function () {
            $customer = Customer::factory()->create();
            $user = User::factory()->create([
                'role' => 'customer',
                'customer_id' => $customer->id,
            ]);
            
            $invoice = Invoice::factory()->open()->create(['customer_id' => $customer->id]);
            
            session([
                'paypal_order_id' => 'order-123',
                'paypal_invoices' => [$invoice->id],
                'paypal_total_amount' => 1000.00,
            ]);
            
            $this->paypalService->shouldReceive('capturePayment')
                ->once()
                ->with('order-123')
                ->andThrow(new \Exception('PayPal service error'));
            
            $response = $this->actingAs($user)
                ->get(route('paypal.bulk.success', ['token' => 'order-123']));
            
            $response->assertRedirect(route('dashboard'));
            $response->assertSessionHas('error', 'Die Zahlung konnte nicht durchgeführt werden. Bitte versuchen Sie es später erneut.');
        });
    });

    describe('Bulk Payment Cancellation', function () {
        it('handles payment cancellation', function () {
            $user = User::factory()->create();
            
            // Set up session data that should be cleared
            session([
                'paypal_order_id' => 'order-123',
                'paypal_invoices' => [1, 2, 3],
                'paypal_total_amount' => 1500.00,
            ]);
            
            $response = $this->actingAs($user)
                ->get(route('paypal.bulk.cancel'));
            
            $response->assertRedirect(route('dashboard'));
            $response->assertSessionHas('error', 'Zahlung abgebrochen.');
            
            // Check session is cleaned
            expect(session('paypal_invoices'))->toBeNull();
            expect(session('paypal_order_id'))->toBeNull();
            expect(session('paypal_total_amount'))->toBeNull();
        });
    });

    describe('Payment Flow Integration', function () {
        it('handles complete payment workflow', function () {
            $customer = Customer::factory()->create();
            $user = User::factory()->create([
                'role' => 'customer',
                'customer_id' => $customer->id,
            ]);
            
            $invoice = Invoice::factory()->open()->create([
                'customer_id' => $customer->id,
                'total_amount' => 999.99,
                'open_amount' => 999.99,
            ]);
            
            // Step 1: Initiate payment
            $this->paypalService->shouldReceive('testConnection')
                ->once()
                ->andReturn(['success' => true]);
                
            $this->paypalService->shouldReceive('createBulkPayment')
                ->once()
                ->andReturn([
                    'success' => true,
                    'order_id' => 'order-workflow-test',
                    'approval_url' => 'https://paypal.com/approve',
                    'total_amount' => 999.99,
                ]);
            
            $initResponse = $this->actingAs($user)
                ->post(route('paypal.bulk.pay'));
            
            $initResponse->assertRedirect('https://paypal.com/approve');
            
            // Step 2: Complete payment
            $this->paypalService->shouldReceive('capturePayment')
                ->once()
                ->with('order-workflow-test')
                ->andReturn([
                    'status' => 'COMPLETED',
                    'purchase_units' => [[
                        'payments' => [
                            'captures' => [[
                                'amount' => ['value' => '999.99'],
                            ]],
                        ],
                    ]],
                ]);
            
            $successResponse = $this->actingAs($user)
                ->get(route('paypal.bulk.success', ['token' => 'order-workflow-test']));
            
            $successResponse->assertRedirect(route('dashboard'));
            $successResponse->assertSessionHas('success');
            
            // Verify final state
            $invoice->refresh();
            expect($invoice->status)->toBe('paid');
            expect($invoice->web_payment_status)->toBe('completed');
            expect($invoice->web_payment_id)->toBe('order-workflow-test');
            expect($invoice->web_payment_amount)->toBe('999.99');
        });
    });
});