<?php

use App\Http\Controllers\PositionController;
use App\Models\Position;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

describe('PositionController', function () {
    beforeEach(function () {
        $this->controller = new PositionController();
    });

    describe('Authorization', function () {
        it('requires manage-positions permission for index', function () {
            // Create a user without admin privileges
            $user = User::factory()->create(['role' => 'customer']);
            
            $response = $this->actingAs($user)->get(route('positions.index'));
            
            $response->assertStatus(403); // Forbidden
        });

        it('allows admin users to access positions index', function () {
            // Create an admin user
            $admin = User::factory()->create(['role' => 'admin']);
            
            $response = $this->actingAs($admin)->get(route('positions.index'));
            
            $response->assertStatus(200);
        });
    });

    describe('Index Method', function () {
        it('displays positions list for admin users', function () {
            $admin = User::factory()->create(['role' => 'admin']);
            $customer = Customer::factory()->create();
            $position = Position::factory()->create(['customer_id' => $customer->id]);
            
            $response = $this->actingAs($admin)->get(route('positions.index'));
            
            $response->assertStatus(200);
            $response->assertViewIs('positions.index');
            $response->assertViewHas('positions');
            $response->assertViewHas('customers');
        });

        it('filters positions by customer', function () {
            $admin = User::factory()->create(['role' => 'admin']);
            $customer1 = Customer::factory()->create();
            $customer2 = Customer::factory()->create();
            
            Position::factory()->create(['customer_id' => $customer1->id, 'name' => 'Position 1']);
            Position::factory()->create(['customer_id' => $customer2->id, 'name' => 'Position 2']);
            
            $response = $this->actingAs($admin)
                ->get(route('positions.index', ['customer_id' => $customer1->id]));
            
            $response->assertStatus(200);
            // The response should only contain positions for customer1
        });

        it('filters positions by billed status', function () {
            $admin = User::factory()->create(['role' => 'admin']);
            $customer = Customer::factory()->create();
            
            Position::factory()->create(['customer_id' => $customer->id, 'billed' => true]);
            Position::factory()->create(['customer_id' => $customer->id, 'billed' => false]);
            
            $response = $this->actingAs($admin)
                ->get(route('positions.index', ['billed' => '1']));
            
            $response->assertStatus(200);
            // Should only show billed positions
        });

        it('searches positions by name', function () {
            $admin = User::factory()->create(['role' => 'admin']);
            $customer = Customer::factory()->create();
            
            Position::factory()->create(['customer_id' => $customer->id, 'name' => 'Web Development']);
            Position::factory()->create(['customer_id' => $customer->id, 'name' => 'Database Design']);
            
            $response = $this->actingAs($admin)
                ->get(route('positions.index', ['search' => 'Web']));
            
            $response->assertStatus(200);
            // Should only show positions matching "Web"
        });
    });

    describe('Create Method', function () {
        it('shows create form for admin users', function () {
            $admin = User::factory()->create(['role' => 'admin']);
            Customer::factory()->create(); // Create at least one customer
            
            $response = $this->actingAs($admin)->get(route('positions.create'));
            
            $response->assertStatus(200);
            $response->assertViewIs('positions.create');
            $response->assertViewHas('customers');
        });
    });

    describe('Store Method', function () {
        it('creates new position with valid data', function () {
            $admin = User::factory()->create(['role' => 'admin']);
            $customer = Customer::factory()->create();
            
            $positionData = [
                'customer_id' => $customer->id,
                'name' => 'Web Development',
                'description' => 'Frontend work',
                'quantity' => 10.00,
                'unit_name' => 'hours',
                'unit_price' => 85.50,
                'discount' => 5.00,
            ];
            
            $response = $this->actingAs($admin)
                ->post(route('positions.store'), $positionData);
            
            $response->assertRedirect(route('positions.index'));
            $response->assertSessionHas('success');
            
            $this->assertDatabaseHas('positionen', [
                'customer_id' => $customer->id,
                'name' => 'Web Development',
                'quantity' => 10.00,
                'unit_price' => 85.50,
                'discount' => 5.00,
            ]);
        });

        it('sets default discount to 0 when not provided', function () {
            $admin = User::factory()->create(['role' => 'admin']);
            $customer = Customer::factory()->create();
            
            $positionData = [
                'customer_id' => $customer->id,
                'name' => 'Consulting',
                'quantity' => 5.00,
                'unit_name' => 'hours',
                'unit_price' => 100.00,
                // No discount provided
            ];
            
            $response = $this->actingAs($admin)
                ->post(route('positions.store'), $positionData);
            
            $response->assertRedirect(route('positions.index'));
            
            $this->assertDatabaseHas('positionen', [
                'name' => 'Consulting',
                'discount' => 0,
            ]);
        });

        it('validates required fields', function () {
            $admin = User::factory()->create(['role' => 'admin']);
            
            $response = $this->actingAs($admin)
                ->post(route('positions.store'), []);
            
            $response->assertSessionHasErrors([
                'customer_id',
                'name',
                'quantity',
                'unit_name',
                'unit_price',
            ]);
        });

        it('validates customer exists', function () {
            $admin = User::factory()->create(['role' => 'admin']);
            
            $positionData = [
                'customer_id' => 999, // Non-existent customer
                'name' => 'Test Position',
                'quantity' => 1.00,
                'unit_name' => 'item',
                'unit_price' => 100.00,
            ];
            
            $response = $this->actingAs($admin)
                ->post(route('positions.store'), $positionData);
            
            $response->assertSessionHasErrors(['customer_id']);
        });

        it('validates numeric fields are positive', function () {
            $admin = User::factory()->create(['role' => 'admin']);
            $customer = Customer::factory()->create();
            
            $positionData = [
                'customer_id' => $customer->id,
                'name' => 'Test Position',
                'quantity' => -1.00, // Invalid negative quantity
                'unit_name' => 'hours',
                'unit_price' => -50.00, // Invalid negative price
                'discount' => 150.00, // Invalid discount > 100%
            ];
            
            $response = $this->actingAs($admin)
                ->post(route('positions.store'), $positionData);
            
            $response->assertSessionHasErrors(['quantity', 'unit_price', 'discount']);
        });
    });

    describe('Show Method', function () {
        it('displays position details', function () {
            $admin = User::factory()->create(['role' => 'admin']);
            $customer = Customer::factory()->create();
            $position = Position::factory()->create(['customer_id' => $customer->id]);
            
            $response = $this->actingAs($admin)
                ->get(route('positions.show', $position));
            
            $response->assertStatus(200);
            $response->assertViewIs('positions.show');
            $response->assertViewHas('position');
        });
    });

    describe('Edit Method', function () {
        it('shows edit form for unbilled positions', function () {
            $admin = User::factory()->create(['role' => 'admin']);
            $customer = Customer::factory()->create();
            $position = Position::factory()->create([
                'customer_id' => $customer->id,
                'billed' => false,
            ]);
            
            $response = $this->actingAs($admin)
                ->get(route('positions.edit', $position));
            
            $response->assertStatus(200);
            $response->assertViewIs('positions.edit');
            $response->assertViewHas('position');
            $response->assertViewHas('customers');
        });
    });

    describe('Update Method', function () {
        it('updates unbilled position successfully', function () {
            $admin = User::factory()->create(['role' => 'admin']);
            $customer = Customer::factory()->create();
            $position = Position::factory()->create([
                'customer_id' => $customer->id,
                'name' => 'Original Name',
                'billed' => false,
            ]);
            
            $updateData = [
                'customer_id' => $customer->id,
                'name' => 'Updated Name',
                'description' => 'Updated description',
                'quantity' => 15.00,
                'unit_name' => 'hours',
                'unit_price' => 90.00,
                'discount' => 10.00,
            ];
            
            $response = $this->actingAs($admin)
                ->put(route('positions.update', $position), $updateData);
            
            $response->assertRedirect(route('positions.index'));
            $response->assertSessionHas('success');
            
            $position->refresh();
            expect($position->name)->toBe('Updated Name');
            expect($position->quantity)->toBe(15.00);
            expect($position->unit_price)->toBe(90.00);
        });

        it('prevents updating billed positions', function () {
            $admin = User::factory()->create(['role' => 'admin']);
            $customer = Customer::factory()->create();
            $position = Position::factory()->create([
                'customer_id' => $customer->id,
                'name' => 'Billed Position',
                'billed' => true,
            ]);
            
            $updateData = [
                'customer_id' => $customer->id,
                'name' => 'Attempted Update',
                'quantity' => 1.00,
                'unit_name' => 'item',
                'unit_price' => 100.00,
            ];
            
            $response = $this->actingAs($admin)
                ->put(route('positions.update', $position), $updateData);
            
            $response->assertRedirect(route('positions.index'));
            $response->assertSessionHas('error', 'Abgerechnete Positionen können nicht bearbeitet werden.');
            
            $position->refresh();
            expect($position->name)->toBe('Billed Position'); // Should remain unchanged
        });
    });

    describe('Destroy Method', function () {
        it('deletes unbilled position successfully', function () {
            $admin = User::factory()->create(['role' => 'admin']);
            $customer = Customer::factory()->create();
            $position = Position::factory()->create([
                'customer_id' => $customer->id,
                'billed' => false,
            ]);
            
            $response = $this->actingAs($admin)
                ->delete(route('positions.destroy', $position));
            
            $response->assertRedirect(route('positions.index'));
            $response->assertSessionHas('success');
            
            $this->assertDatabaseMissing('positionen', ['id' => $position->id]);
        });

        it('prevents deleting billed positions', function () {
            $admin = User::factory()->create(['role' => 'admin']);
            $customer = Customer::factory()->create();
            $position = Position::factory()->create([
                'customer_id' => $customer->id,
                'billed' => true,
            ]);
            
            $response = $this->actingAs($admin)
                ->delete(route('positions.destroy', $position));
            
            $response->assertRedirect(route('positions.index'));
            $response->assertSessionHas('error', 'Abgerechnete Positionen können nicht gelöscht werden.');
            
            $this->assertDatabaseHas('positionen', ['id' => $position->id]);
        });
    });
});