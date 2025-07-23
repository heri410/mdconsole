<?php

use App\Models\User;
use Illuminate\Support\Facades\Gate;

describe('Authorization System', function () {
    describe('manage-positions Gate', function () {
        it('allows admin users to manage positions', function () {
            $admin = User::factory()->create(['role' => 'admin']);
            
            $this->actingAs($admin);
            
            expect(Gate::allows('manage-positions'))->toBeTrue();
            expect(Gate::denies('manage-positions'))->toBeFalse();
        });

        it('denies customer users from managing positions', function () {
            $customer = \App\Models\Customer::factory()->create();
            $customerUser = User::factory()->create([
                'role' => 'customer',
                'customer_id' => $customer->id,
            ]);
            
            $this->actingAs($customerUser);
            
            expect(Gate::allows('manage-positions'))->toBeFalse();
            expect(Gate::denies('manage-positions'))->toBeTrue();
        });

        it('denies users with no role from managing positions', function () {
            $user = User::factory()->create(['role' => null]);
            
            $this->actingAs($user);
            
            expect(Gate::allows('manage-positions'))->toBeFalse();
            expect(Gate::denies('manage-positions'))->toBeTrue();
        });

        it('denies users with custom roles from managing positions', function () {
            $user = User::factory()->create(['role' => 'moderator']);
            
            $this->actingAs($user);
            
            expect(Gate::allows('manage-positions'))->toBeFalse();
            expect(Gate::denies('manage-positions'))->toBeTrue();
        });

        it('correctly evaluates gate with user parameter', function () {
            $admin = User::factory()->create(['role' => 'admin']);
            $customer = \App\Models\Customer::factory()->create();
            $customerUser = User::factory()->create([
                'role' => 'customer', 
                'customer_id' => $customer->id
            ]);
            
            expect(Gate::forUser($admin)->allows('manage-positions'))->toBeTrue();
            expect(Gate::forUser($customerUser)->allows('manage-positions'))->toBeFalse();
        });
    });

    describe('Route Protection', function () {
        it('protects position routes with manage-positions middleware', function () {
            $customer = \App\Models\Customer::factory()->create();
            $customerUser = User::factory()->create([
                'role' => 'customer',
                'customer_id' => $customer->id,
            ]);
            
            // Test all position routes are protected
            $protectedRoutes = [
                ['GET', 'positions.index'],
                ['GET', 'positions.create'],
                ['GET', 'positions.show', ['position' => 1]],
                ['GET', 'positions.edit', ['position' => 1]],
            ];
            
            foreach ($protectedRoutes as $routeInfo) {
                $method = $routeInfo[0];
                $routeName = $routeInfo[1];
                $params = $routeInfo[2] ?? [];
                
                $response = $this->actingAs($customerUser)
                    ->call($method, route($routeName, $params));
                
                $response->assertStatus(403);
            }
        });

        it('allows admin access to all position routes', function () {
            $admin = User::factory()->create(['role' => 'admin']);
            $customer = \App\Models\Customer::factory()->create();
            $position = \App\Models\Position::factory()->create(['customer_id' => $customer->id]);
            
            // Test admin can access position routes
            $this->actingAs($admin)
                ->get(route('positions.index'))
                ->assertStatus(200);
                
            $this->actingAs($admin)
                ->get(route('positions.create'))
                ->assertStatus(200);
                
            $this->actingAs($admin)
                ->get(route('positions.show', $position))
                ->assertStatus(200);
                
            $this->actingAs($admin)
                ->get(route('positions.edit', $position))
                ->assertStatus(200);
        });
    });

    describe('User Role Methods Integration', function () {
        it('integrates user role methods with authorization gates', function () {
            $admin = User::factory()->create(['role' => 'admin']);
            $customer = \App\Models\Customer::factory()->create();
            $customerUser = User::factory()->create([
                'role' => 'customer',
                'customer_id' => $customer->id,
            ]);
            
            // Test admin user
            expect($admin->isAdmin())->toBeTrue();
            expect($admin->isCustomer())->toBeFalse();
            expect(Gate::forUser($admin)->allows('manage-positions'))->toBeTrue();
            
            // Test customer user
            expect($customerUser->isAdmin())->toBeFalse();
            expect($customerUser->isCustomer())->toBeTrue();
            expect(Gate::forUser($customerUser)->allows('manage-positions'))->toBeFalse();
        });

        it('handles edge cases in role detection', function () {
            // Test user with admin role but no customer relationship
            $adminWithoutCustomer = User::factory()->create([
                'role' => 'admin',
                'customer_id' => null,
            ]);
            
            expect($adminWithoutCustomer->isAdmin())->toBeTrue();
            expect($adminWithoutCustomer->isCustomer())->toBeFalse();
            expect(Gate::forUser($adminWithoutCustomer)->allows('manage-positions'))->toBeTrue();
            
            // Test user with customer role but no customer_id (should not be considered customer)
            $customerUserWithoutId = User::factory()->create([
                'role' => 'customer',
                'customer_id' => null,
            ]);
            
            expect($customerUserWithoutId->isAdmin())->toBeFalse();
            expect($customerUserWithoutId->isCustomer())->toBeFalse();
            expect(Gate::forUser($customerUserWithoutId)->allows('manage-positions'))->toBeFalse();
        });
    });

    describe('Authorization Middleware Integration', function () {
        it('applies authorization consistently across controllers', function () {
            $admin = User::factory()->create(['role' => 'admin']);
            $customer = \App\Models\Customer::factory()->create();
            $customerUser = User::factory()->create([
                'role' => 'customer',
                'customer_id' => $customer->id,
            ]);
            
            // Create test position
            $position = \App\Models\Position::factory()->create(['customer_id' => $customer->id]);
            
            // Test POST requests (create/store)
            $this->actingAs($customerUser)
                ->post(route('positions.store'), [
                    'customer_id' => $customer->id,
                    'name' => 'Test Position',
                    'quantity' => 1.00,
                    'unit_name' => 'item',
                    'unit_price' => 100.00,
                ])
                ->assertStatus(403);
            
            $this->actingAs($admin)
                ->post(route('positions.store'), [
                    'customer_id' => $customer->id,
                    'name' => 'Admin Position',
                    'quantity' => 1.00,
                    'unit_name' => 'item',
                    'unit_price' => 100.00,
                ])
                ->assertRedirect(route('positions.index'));
            
            // Test PUT requests (update)
            $this->actingAs($customerUser)
                ->put(route('positions.update', $position), [
                    'customer_id' => $customer->id,
                    'name' => 'Updated Position',
                    'quantity' => 2.00,
                    'unit_name' => 'item',
                    'unit_price' => 100.00,
                ])
                ->assertStatus(403);
            
            // Test DELETE requests (destroy)
            $this->actingAs($customerUser)
                ->delete(route('positions.destroy', $position))
                ->assertStatus(403);
        });

        it('allows access to non-protected routes for all authenticated users', function () {
            $admin = User::factory()->create(['role' => 'admin']);
            $customer = \App\Models\Customer::factory()->create();
            $customerUser = User::factory()->create([
                'role' => 'customer',
                'customer_id' => $customer->id,
            ]);
            
            // Both users should be able to access dashboard
            $this->actingAs($admin)
                ->get(route('dashboard'))
                ->assertStatus(200);
                
            $this->actingAs($customerUser)
                ->get(route('dashboard'))
                ->assertStatus(200);
            
            // Both users should be able to access profile
            $this->actingAs($admin)
                ->get(route('profile.edit'))
                ->assertStatus(200);
                
            $this->actingAs($customerUser)
                ->get(route('profile.edit'))
                ->assertStatus(200);
        });
    });
});