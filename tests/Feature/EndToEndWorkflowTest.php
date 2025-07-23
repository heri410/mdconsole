<?php

use App\Models\User;
use App\Models\Customer;
use App\Models\Position;
use App\Models\Invoice;

describe('End-to-End Application Workflow', function () {
    describe('Complete Business Process Flow', function () {
        it('handles full customer lifecycle from registration to payment', function () {
            // Step 1: Create customer from Lexoffice data
            $lexofficeCustomerData = (object) [
                'id' => 'lex-customer-e2e',
                'organizationId' => 'org-e2e-test',
                'company' => (object) [
                    'name' => 'E2E Test Company Ltd',
                    'contactPersons' => [(object) [
                        'firstName' => 'Test',
                        'lastName' => 'Manager',
                    ]],
                ],
                'addresses' => (object) [
                    'billing' => [(object) [
                        'street' => '123 Test Street',
                        'zip' => '12345',
                        'city' => 'Test City',
                        'countryCode' => 'DE',
                    ]],
                ],
                'emailAddresses' => (object) [
                    'business' => ['test@e2ecompany.com'],
                ],
                'phoneNumbers' => (object) [
                    'business' => ['+49123456789'],
                ],
                'roles' => (object) [
                    'customer' => (object) [
                        'number' => 'CUST-E2E-001',
                    ],
                ],
            ];
            
            $customer = Customer::fromLexofficeContact($lexofficeCustomerData);
            $customer->save();
            
            expect($customer->company_name)->toBe('E2E Test Company Ltd');
            expect($customer->email)->toBe('test@e2ecompany.com');
            expect($customer->customer_number)->toBe('CUST-E2E-001');
            
            // Step 2: Create user account for customer
            $user = $customer->createUserAccount('test-password');
            
            expect($user->email)->toBe('test@e2ecompany.com');
            expect($user->role)->toBe('customer');
            expect($user->customer_id)->toBe($customer->id);
            expect($user->isCustomer())->toBeTrue();
            
            // Step 3: Admin creates positions for customer
            $admin = User::factory()->create(['role' => 'admin']);
            
            $position1Data = [
                'customer_id' => $customer->id,
                'name' => 'Website Development',
                'description' => 'Complete website redesign',
                'quantity' => 40.00,
                'unit_name' => 'hours',
                'unit_price' => 85.00,
                'discount' => 5.00,
            ];
            
            $createResponse1 = $this->actingAs($admin)
                ->post(route('positions.store'), $position1Data);
            
            $createResponse1->assertRedirect(route('positions.index'));
            $createResponse1->assertSessionHas('success');
            
            $position2Data = [
                'customer_id' => $customer->id,
                'name' => 'SEO Optimization',
                'description' => 'Search engine optimization',
                'quantity' => 20.00,
                'unit_name' => 'hours',
                'unit_price' => 95.00,
                'discount' => 0.00,
            ];
            
            $createResponse2 = $this->actingAs($admin)
                ->post(route('positions.store'), $position2Data);
            
            $createResponse2->assertRedirect(route('positions.index'));
            
            // Verify positions were created
            $positions = Position::where('customer_id', $customer->id)->get();
            expect($positions)->toHaveCount(2);
            
            $websitePosition = $positions->where('name', 'Website Development')->first();
            $seoPosition = $positions->where('name', 'SEO Optimization')->first();
            
            expect($websitePosition->total_amount)->toBe(3230.00); // 40 * 85 * 0.95
            expect($seoPosition->total_amount)->toBe(1900.00); // 20 * 95
            
            // Step 4: Customer views dashboard and sees positions
            $dashboardResponse = $this->actingAs($user)
                ->get(route('dashboard'));
            
            $dashboardResponse->assertStatus(200);
            $dashboardResponse->assertViewIs('dashboard');
            
            // Step 5: Generate invoices from positions (simulate external process)
            $invoice = Invoice::create([
                'number' => 'INV-E2E-001',
                'customer_id' => $customer->id,
                'date' => now(),
                'due_date' => now()->addDays(30),
                'total_amount' => 5130.00, // Sum of both positions
                'open_amount' => 5130.00,
                'status' => 'open',
            ]);
            
            // Mark positions as billed
            $websitePosition->update([
                'billed' => true,
                'billed_at' => now(),
                'invoice_id' => $invoice->id,
            ]);
            
            $seoPosition->update([
                'billed' => true,
                'billed_at' => now(),
                'invoice_id' => $invoice->id,
            ]);
            
            // Step 6: Verify positions cannot be modified after billing
            $updateResponse = $this->actingAs($admin)
                ->put(route('positions.update', $websitePosition), [
                    'customer_id' => $customer->id,
                    'name' => 'Attempted Update',
                    'quantity' => 1.00,
                    'unit_name' => 'item',
                    'unit_price' => 100.00,
                ]);
            
            $updateResponse->assertRedirect(route('positions.index'));
            $updateResponse->assertSessionHas('error');
            
            $websitePosition->refresh();
            expect($websitePosition->name)->toBe('Website Development'); // Unchanged
            
            // Step 7: Customer sees invoice on dashboard
            $dashboardWithInvoiceResponse = $this->actingAs($user)
                ->get(route('dashboard'));
            
            $dashboardWithInvoiceResponse->assertStatus(200);
            $dashboardWithInvoiceResponse->assertSee('INV-E2E-001');
            $dashboardWithInvoiceResponse->assertSee('5130.00');
            
            // Step 8: Verify customer relationships
            $customer->refresh();
            
            expect($customer->positions)->toHaveCount(2);
            expect($customer->invoices)->toHaveCount(1);
            expect($customer->unbilledPositions)->toHaveCount(0);
            
            $customerInvoice = $customer->invoices->first();
            expect($customerInvoice->positions)->toHaveCount(2);
            expect($customerInvoice->total_amount)->toBe(5130.00);
        });

        it('enforces proper access control throughout workflow', function () {
            // Create different types of users
            $admin = User::factory()->create(['role' => 'admin']);
            
            $customer1 = Customer::factory()->create();
            $customer1User = User::factory()->create([
                'role' => 'customer',
                'customer_id' => $customer1->id,
            ]);
            
            $customer2 = Customer::factory()->create();
            $customer2User = User::factory()->create([
                'role' => 'customer',
                'customer_id' => $customer2->id,
            ]);
            
            $regularUser = User::factory()->create(['role' => null]);
            
            // Create test data
            $position1 = Position::factory()->create(['customer_id' => $customer1->id]);
            $position2 = Position::factory()->create(['customer_id' => $customer2->id]);
            
            $invoice1 = Invoice::factory()->create(['customer_id' => $customer1->id]);
            $invoice2 = Invoice::factory()->create(['customer_id' => $customer2->id]);
            
            // Test position access control
            
            // Admin can access all positions
            $this->actingAs($admin)
                ->get(route('positions.index'))
                ->assertStatus(200);
            
            $this->actingAs($admin)
                ->get(route('positions.show', $position1))
                ->assertStatus(200);
            
            // Customers cannot access positions management
            $this->actingAs($customer1User)
                ->get(route('positions.index'))
                ->assertStatus(403);
            
            $this->actingAs($customer2User)
                ->get(route('positions.create'))
                ->assertStatus(403);
            
            $this->actingAs($regularUser)
                ->post(route('positions.store'), [])
                ->assertStatus(403);
            
            // Test dashboard access control
            
            // All authenticated users can access dashboard
            $this->actingAs($admin)
                ->get(route('dashboard'))
                ->assertStatus(200);
            
            $this->actingAs($customer1User)
                ->get(route('dashboard'))
                ->assertStatus(200);
            
            $this->actingAs($customer2User)
                ->get(route('dashboard'))
                ->assertStatus(200);
            
            $this->actingAs($regularUser)
                ->get(route('dashboard'))
                ->assertStatus(200);
            
            // Test profile access control
            
            // All authenticated users can access their profile
            $this->actingAs($admin)
                ->get(route('profile.edit'))
                ->assertStatus(200);
            
            $this->actingAs($customer1User)
                ->get(route('profile.edit'))
                ->assertStatus(200);
            
            // Test unauthenticated access
            $this->get(route('dashboard'))
                ->assertRedirect(route('login'));
            
            $this->get(route('positions.index'))
                ->assertRedirect(route('login'));
            
            $this->get(route('profile.edit'))
                ->assertRedirect(route('login'));
        });

        it('handles data integrity throughout the workflow', function () {
            $customer = Customer::factory()->create();
            $admin = User::factory()->create(['role' => 'admin']);
            
            // Create positions with specific calculations
            $position1 = Position::factory()->create([
                'customer_id' => $customer->id,
                'name' => 'Development Work',
                'quantity' => 10.00,
                'unit_price' => 100.00,
                'discount' => 10.00, // 10%
            ]);
            
            $position2 = Position::factory()->create([
                'customer_id' => $customer->id,
                'name' => 'Testing Work',
                'quantity' => 5.00,
                'unit_price' => 80.00,
                'discount' => 0.00,
            ]);
            
            // Verify calculations
            expect($position1->total_amount)->toBe(900.00); // 10 * 100 * 0.9
            expect($position1->discount_amount)->toBe(100.00); // 10 * 100 * 0.1
            
            expect($position2->total_amount)->toBe(400.00); // 5 * 80
            expect($position2->discount_amount)->toBe(0.00);
            
            // Create invoice
            $totalExpected = $position1->total_amount + $position2->total_amount; // 1300.00
            
            $invoice = Invoice::factory()->create([
                'customer_id' => $customer->id,
                'total_amount' => $totalExpected,
                'open_amount' => $totalExpected,
                'status' => 'open',
            ]);
            
            // Bill positions
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
            
            // Verify relationships and data integrity
            $customer->refresh();
            $invoice->refresh();
            
            expect($customer->positions)->toHaveCount(2);
            expect($customer->invoices)->toHaveCount(1);
            expect($customer->unbilledPositions)->toHaveCount(0);
            
            expect($invoice->positions)->toHaveCount(2);
            expect($invoice->total_amount)->toBe($totalExpected);
            
            $invoicePositionsTotal = $invoice->positions->sum('total_amount');
            expect($invoicePositionsTotal)->toBe($totalExpected);
            
            // Test position scopes
            $allPositions = Position::where('customer_id', $customer->id)->get();
            $billedPositions = Position::where('customer_id', $customer->id)->billed()->get();
            $unbilledPositions = Position::where('customer_id', $customer->id)->unbilled()->get();
            
            expect($allPositions)->toHaveCount(2);
            expect($billedPositions)->toHaveCount(2);
            expect($unbilledPositions)->toHaveCount(0);
            
            // Test customer-specific queries
            $customerPositions = Position::forCustomer($customer->id)->get();
            expect($customerPositions)->toHaveCount(2);
            
            // Verify that positions from other customers are not included
            $otherCustomer = Customer::factory()->create();
            Position::factory()->create(['customer_id' => $otherCustomer->id]);
            
            $customer1Positions = Position::forCustomer($customer->id)->get();
            $customer2Positions = Position::forCustomer($otherCustomer->id)->get();
            
            expect($customer1Positions)->toHaveCount(2);
            expect($customer2Positions)->toHaveCount(1);
        });
    });
});