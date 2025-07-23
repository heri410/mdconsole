<?php

use App\Models\User;
use App\Models\Customer;

describe('User Model', function () {
    it('can be created with valid attributes', function () {
        $user = User::make([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'customer_id' => 1,
            'role' => 'customer',
        ]);

        expect($user->name)->toBe('John Doe');
        expect($user->email)->toBe('john@example.com');
        expect($user->customer_id)->toBe(1);
        expect($user->role)->toBe('customer');
    });

    it('has correct fillable attributes', function () {
        $user = new User();
        $expected = [
            'name',
            'email',
            'password',
            'customer_id',
            'role',
        ];
        
        expect($user->getFillable())->toBe($expected);
    });

    it('has correct hidden attributes', function () {
        $user = new User();
        $expected = [
            'password',
            'remember_token',
        ];
        
        expect($user->getHidden())->toBe($expected);
    });

    it('casts email_verified_at to datetime', function () {
        $user = User::make([
            'email_verified_at' => '2024-01-01 12:00:00',
        ]);

        expect($user->email_verified_at)->toBeInstanceOf(\Carbon\Carbon::class);
    });

    it('hashes password automatically', function () {
        $user = User::make([
            'password' => 'plain-password',
        ]);

        // Password should be hashed (we can't test the exact hash due to randomization)
        expect($user->password)->not->toBe('plain-password');
        expect(strlen($user->password))->toBeGreaterThan(50); // Hashed passwords are long
    });
});

describe('User Relationships', function () {
    it('belongs to a customer', function () {
        $user = new User();
        $relation = $user->customer();
        
        expect($relation)->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class);
        expect($relation->getRelated())->toBeInstanceOf(Customer::class);
    });
});

describe('User Role Methods', function () {
    it('correctly identifies admin users', function () {
        $adminUser = User::make(['role' => 'admin']);
        $customerUser = User::make(['role' => 'customer']);
        $userWithoutRole = User::make(['role' => null]);

        expect($adminUser->isAdmin())->toBeTrue();
        expect($customerUser->isAdmin())->toBeFalse();
        expect($userWithoutRole->isAdmin())->toBeFalse();
    });

    it('correctly identifies customer users', function () {
        $customerUser = User::make(['role' => 'customer', 'customer_id' => 1]);
        $customerUserWithoutId = User::make(['role' => 'customer', 'customer_id' => null]);
        $adminUser = User::make(['role' => 'admin', 'customer_id' => 1]);
        $userWithoutRole = User::make(['role' => null, 'customer_id' => 1]);

        expect($customerUser->isCustomer())->toBeTrue();
        expect($customerUserWithoutId->isCustomer())->toBeFalse(); // Needs customer_id
        expect($adminUser->isCustomer())->toBeFalse(); // Wrong role
        expect($userWithoutRole->isCustomer())->toBeFalse(); // No role
    });

    it('handles different role scenarios', function () {
        $cases = [
            ['role' => 'admin', 'customer_id' => null, 'isAdmin' => true, 'isCustomer' => false],
            ['role' => 'admin', 'customer_id' => 1, 'isAdmin' => true, 'isCustomer' => false],
            ['role' => 'customer', 'customer_id' => 1, 'isAdmin' => false, 'isCustomer' => true],
            ['role' => 'customer', 'customer_id' => null, 'isAdmin' => false, 'isCustomer' => false],
            ['role' => 'moderator', 'customer_id' => 1, 'isAdmin' => false, 'isCustomer' => false],
            ['role' => null, 'customer_id' => 1, 'isAdmin' => false, 'isCustomer' => false],
        ];

        foreach ($cases as $case) {
            $user = User::make([
                'role' => $case['role'],
                'customer_id' => $case['customer_id'],
            ]);

            expect($user->isAdmin())->toBe($case['isAdmin']);
            expect($user->isCustomer())->toBe($case['isCustomer']);
        }
    });
});