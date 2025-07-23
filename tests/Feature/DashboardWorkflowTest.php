<?php

use App\Models\User;
use App\Models\Customer;
use App\Models\Position;
use App\Models\Invoice;

describe('Dashboard Workflow', function () {
    it('redirects unauthenticated users to login', function () {
        $response = $this->get('/');
        
        $response->assertRedirect(route('login'));
    });

    it('redirects authenticated users to dashboard', function () {
        $user = User::factory()->create();
        
        $response = $this->actingAs($user)->get('/');
        
        $response->assertRedirect(route('dashboard'));
    });

    it('shows dashboard for authenticated admin users', function () {
        $admin = User::factory()->create(['role' => 'admin']);
        
        $response = $this->actingAs($admin)->get(route('dashboard'));
        
        $response->assertStatus(200);
        $response->assertViewIs('dashboard');
    });

    it('shows dashboard for authenticated customer users', function () {
        $customer = Customer::factory()->create();
        $user = User::factory()->create([
            'role' => 'customer',
            'customer_id' => $customer->id,
        ]);
        
        $response = $this->actingAs($user)->get(route('dashboard'));
        
        $response->assertStatus(200);
        $response->assertViewIs('dashboard');
    });
});

describe('Position Management Workflow', function () {
    it('allows admin to create, view, edit and delete positions', function () {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = Customer::factory()->create();
        
        // Create position
        $positionData = [
            'customer_id' => $customer->id,
            'name' => 'Web Development',
            'description' => 'Frontend development work',
            'quantity' => 10.00,
            'unit_name' => 'hours',
            'unit_price' => 85.50,
            'discount' => 5.00,
        ];
        
        $createResponse = $this->actingAs($admin)
            ->post(route('positions.store'), $positionData);
        
        $createResponse->assertRedirect(route('positions.index'));
        $createResponse->assertSessionHas('success');
        
        $position = Position::where('name', 'Web Development')->first();
        expect($position)->not->toBeNull();
        
        // View position
        $showResponse = $this->actingAs($admin)
            ->get(route('positions.show', $position));
        
        $showResponse->assertStatus(200);
        $showResponse->assertSee('Web Development');
        $showResponse->assertSee('Frontend development work');
        
        // Edit position
        $updateData = [
            'customer_id' => $customer->id,
            'name' => 'Updated Web Development',
            'description' => 'Updated description',
            'quantity' => 12.00,
            'unit_name' => 'hours',
            'unit_price' => 90.00,
            'discount' => 0.00,
        ];
        
        $updateResponse = $this->actingAs($admin)
            ->put(route('positions.update', $position), $updateData);
        
        $updateResponse->assertRedirect(route('positions.index'));
        $updateResponse->assertSessionHas('success');
        
        $position->refresh();
        expect($position->name)->toBe('Updated Web Development');
        expect($position->quantity)->toBe(12.00);
        
        // Delete position (only if not billed)
        expect($position->billed)->toBeFalse();
        
        $deleteResponse = $this->actingAs($admin)
            ->delete(route('positions.destroy', $position));
        
        $deleteResponse->assertRedirect(route('positions.index'));
        $deleteResponse->assertSessionHas('success');
        
        $this->assertDatabaseMissing('positionen', ['id' => $position->id]);
    });

    it('prevents non-admin users from managing positions', function () {
        $customer = Customer::factory()->create();
        $user = User::factory()->create([
            'role' => 'customer',
            'customer_id' => $customer->id,
        ]);
        
        // Try to access positions index
        $indexResponse = $this->actingAs($user)->get(route('positions.index'));
        $indexResponse->assertStatus(403);
        
        // Try to create position
        $createResponse = $this->actingAs($user)->get(route('positions.create'));
        $createResponse->assertStatus(403);
        
        // Try to store position
        $storeResponse = $this->actingAs($user)
            ->post(route('positions.store'), [
                'customer_id' => $customer->id,
                'name' => 'Unauthorized Position',
                'quantity' => 1.00,
                'unit_name' => 'item',
                'unit_price' => 100.00,
            ]);
        $storeResponse->assertStatus(403);
    });

    it('protects billed positions from modification', function () {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = Customer::factory()->create();
        $position = Position::factory()->create([
            'customer_id' => $customer->id,
            'name' => 'Billed Position',
            'billed' => true,
            'billed_at' => now(),
        ]);
        
        // Try to update billed position
        $updateResponse = $this->actingAs($admin)
            ->put(route('positions.update', $position), [
                'customer_id' => $customer->id,
                'name' => 'Attempted Update',
                'quantity' => 1.00,
                'unit_name' => 'item',
                'unit_price' => 100.00,
            ]);
        
        $updateResponse->assertRedirect(route('positions.index'));
        $updateResponse->assertSessionHas('error', 'Abgerechnete Positionen können nicht bearbeitet werden.');
        
        // Try to delete billed position
        $deleteResponse = $this->actingAs($admin)
            ->delete(route('positions.destroy', $position));
        
        $deleteResponse->assertRedirect(route('positions.index'));
        $deleteResponse->assertSessionHas('error', 'Abgerechnete Positionen können nicht gelöscht werden.');
        
        // Position should still exist
        $this->assertDatabaseHas('positionen', ['id' => $position->id]);
    });
});

describe('Customer-Position Relationship Workflow', function () {
    it('shows positions only for the related customer', function () {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer1 = Customer::factory()->create(['company_name' => 'Company A']);
        $customer2 = Customer::factory()->create(['company_name' => 'Company B']);
        
        $position1 = Position::factory()->create([
            'customer_id' => $customer1->id,
            'name' => 'Position for Company A',
        ]);
        
        $position2 = Position::factory()->create([
            'customer_id' => $customer2->id,
            'name' => 'Position for Company B',
        ]);
        
        // Filter by customer1
        $response = $this->actingAs($admin)
            ->get(route('positions.index', ['customer_id' => $customer1->id]));
        
        $response->assertStatus(200);
        $response->assertSee('Position for Company A');
        $response->assertDontSee('Position for Company B');
    });

    it('calculates position totals correctly', function () {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = Customer::factory()->create();
        
        $position = Position::factory()->create([
            'customer_id' => $customer->id,
            'name' => 'Test Position',
            'quantity' => 10.00,
            'unit_price' => 100.00,
            'discount' => 10.00, // 10% discount
        ]);
        
        // 10 * 100 = 1000, 10% discount = 100, total = 900
        expect($position->total_amount)->toBe(900.00);
        expect($position->discount_amount)->toBe(100.00);
        
        $response = $this->actingAs($admin)
            ->get(route('positions.show', $position));
        
        $response->assertStatus(200);
        // Should display calculated amounts
        $response->assertSee('900.00'); // Total amount
    });

    it('filters positions by billing status', function () {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = Customer::factory()->create();
        
        $billedPosition = Position::factory()->create([
            'customer_id' => $customer->id,
            'name' => 'Billed Position',
            'billed' => true,
        ]);
        
        $unbilledPosition = Position::factory()->create([
            'customer_id' => $customer->id,
            'name' => 'Unbilled Position',
            'billed' => false,
        ]);
        
        // Show only billed positions
        $billedResponse = $this->actingAs($admin)
            ->get(route('positions.index', ['billed' => '1']));
        
        $billedResponse->assertStatus(200);
        $billedResponse->assertSee('Billed Position');
        $billedResponse->assertDontSee('Unbilled Position');
        
        // Show only unbilled positions
        $unbilledResponse = $this->actingAs($admin)
            ->get(route('positions.index', ['billed' => '0']));
        
        $unbilledResponse->assertStatus(200);
        $unbilledResponse->assertSee('Unbilled Position');
        $unbilledResponse->assertDontSee('Billed Position');
    });
});

describe('User Permission Workflow', function () {
    it('enforces different access levels for different user roles', function () {
        $customer = Customer::factory()->create();
        
        // Admin user
        $admin = User::factory()->create(['role' => 'admin']);
        
        // Customer user
        $customerUser = User::factory()->create([
            'role' => 'customer',
            'customer_id' => $customer->id,
        ]);
        
        // Regular user (no specific role)
        $regularUser = User::factory()->create(['role' => null]);
        
        // Test dashboard access (all should have access)
        $this->actingAs($admin)->get(route('dashboard'))->assertStatus(200);
        $this->actingAs($customerUser)->get(route('dashboard'))->assertStatus(200);
        $this->actingAs($regularUser)->get(route('dashboard'))->assertStatus(200);
        
        // Test positions access (only admin should have access)
        $this->actingAs($admin)->get(route('positions.index'))->assertStatus(200);
        $this->actingAs($customerUser)->get(route('positions.index'))->assertStatus(403);
        $this->actingAs($regularUser)->get(route('positions.index'))->assertStatus(403);
        
        // Test profile access (all should have access)
        $this->actingAs($admin)->get(route('profile.edit'))->assertStatus(200);
        $this->actingAs($customerUser)->get(route('profile.edit'))->assertStatus(200);
        $this->actingAs($regularUser)->get(route('profile.edit'))->assertStatus(200);
    });

    it('correctly identifies user roles', function () {
        $customer = Customer::factory()->create();
        
        $admin = User::factory()->create(['role' => 'admin']);
        $customerUser = User::factory()->create([
            'role' => 'customer',
            'customer_id' => $customer->id,
        ]);
        $regularUser = User::factory()->create(['role' => null]);
        
        expect($admin->isAdmin())->toBeTrue();
        expect($admin->isCustomer())->toBeFalse();
        
        expect($customerUser->isAdmin())->toBeFalse();
        expect($customerUser->isCustomer())->toBeTrue();
        
        expect($regularUser->isAdmin())->toBeFalse();
        expect($regularUser->isCustomer())->toBeFalse();
    });
});