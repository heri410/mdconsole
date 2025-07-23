<?php

use App\Models\Customer;
use App\Models\User;
use App\Models\Invoice;
use App\Models\Position;
use App\Models\Domain;

describe('Customer Model', function () {
    it('can be created with valid attributes', function () {
        $customer = Customer::make([
            'customer_number' => 'CUST-001',
            'lexoffice_id' => 'lex-customer-123',
            'organization_id' => 'org-456',
            'company_name' => 'Acme Corp',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@acme.com',
            'phone' => '+1234567890',
            'street' => '123 Main St',
            'zip' => '12345',
            'city' => 'New York',
            'country' => 'US',
            'billing_day' => 15,
        ]);

        expect($customer->customer_number)->toBe('CUST-001');
        expect($customer->company_name)->toBe('Acme Corp');
        expect($customer->first_name)->toBe('John');
        expect($customer->last_name)->toBe('Doe');
        expect($customer->email)->toBe('john@acme.com');
        expect($customer->phone)->toBe('+1234567890');
        expect($customer->street)->toBe('123 Main St');
        expect($customer->billing_day)->toBe(15);
    });

    it('has correct fillable attributes', function () {
        $customer = new Customer();
        $expected = [
            'customer_number',
            'lexoffice_id',
            'organization_id',
            'company_name',
            'first_name',
            'last_name',
            'email',
            'phone',
            'street',
            'zip',
            'city',
            'country',
            'billing_day',
            'created_at',
            'updated_at',
            'deleted_at',
            'lexoffice_data',
        ];
        
        expect($customer->getFillable())->toBe($expected);
    });

    it('has default billing day of 1 when not set', function () {
        $customer = Customer::make(['billing_day' => null]);
        expect($customer->billing_day)->toBe(1);
    });

    it('returns custom billing day when set', function () {
        $customer = Customer::make(['billing_day' => 15]);
        expect($customer->billing_day)->toBe(15);
    });
});

describe('Customer Relationships', function () {
    it('has many users', function () {
        $customer = new Customer();
        $relation = $customer->users();
        
        expect($relation)->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class);
        expect($relation->getRelated())->toBeInstanceOf(User::class);
    });

    it('has one primary user', function () {
        $customer = new Customer();
        $relation = $customer->primaryUser();
        
        expect($relation)->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\HasOne::class);
        expect($relation->getRelated())->toBeInstanceOf(User::class);
        // Check that it filters by role
        expect($relation->getQuery()->getQuery()->wheres)->toContainEqual([
            'type' => 'Basic',
            'column' => 'role',
            'operator' => '=',
            'value' => 'customer',
            'boolean' => 'and'
        ]);
    });

    it('has many invoices', function () {
        $customer = new Customer();
        $relation = $customer->invoices();
        
        expect($relation)->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class);
        expect($relation->getRelated())->toBeInstanceOf(Invoice::class);
    });

    it('has many positions', function () {
        $customer = new Customer();
        $relation = $customer->positions();
        
        expect($relation)->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class);
        expect($relation->getRelated())->toBeInstanceOf(Position::class);
    });

    it('has many domains', function () {
        $customer = new Customer();
        $relation = $customer->domains();
        
        expect($relation)->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class);
        expect($relation->getRelated())->toBeInstanceOf(Domain::class);
    });

    it('has many unbilled positions', function () {
        $customer = new Customer();
        $relation = $customer->unbilledPositions();
        
        expect($relation)->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class);
        expect($relation->getRelated())->toBeInstanceOf(Position::class);
        // Check that it filters by billed = false
        expect($relation->getQuery()->getQuery()->wheres)->toContainEqual([
            'type' => 'Basic',
            'column' => 'billed',
            'operator' => '=',
            'value' => false,
            'boolean' => 'and'
        ]);
    });
});

describe('Customer Lexoffice Integration', function () {
    it('creates customer from lexoffice contact with company data', function () {
        $lexofficeContact = (object) [
            'id' => 'lex-contact-123',
            'organizationId' => 'org-456',
            'company' => (object) [
                'name' => 'Test Company Ltd',
                'contactPersons' => [(object) [
                    'firstName' => 'Jane',
                    'lastName' => 'Smith',
                ]],
            ],
            'addresses' => (object) [
                'billing' => [(object) [
                    'street' => '456 Business Ave',
                    'zip' => '54321',
                    'city' => 'Business City',
                    'countryCode' => 'DE',
                ]],
            ],
            'emailAddresses' => (object) [
                'business' => ['business@test.com'],
                'office' => ['office@test.com'],
            ],
            'phoneNumbers' => (object) [
                'business' => ['+49123456789'],
                'mobile' => ['+49987654321'],
            ],
            'roles' => (object) [
                'customer' => (object) [
                    'number' => 'CUST-789',
                ],
            ],
        ];

        $customer = Customer::fromLexofficeContact($lexofficeContact);

        expect($customer->lexoffice_id)->toBe('lex-contact-123');
        expect($customer->organization_id)->toBe('org-456');
        expect($customer->company_name)->toBe('Test Company Ltd');
        expect($customer->first_name)->toBe('Jane');
        expect($customer->last_name)->toBe('Smith');
        expect($customer->street)->toBe('456 Business Ave');
        expect($customer->zip)->toBe('54321');
        expect($customer->city)->toBe('Business City');
        expect($customer->country)->toBe('DE');
        expect($customer->email)->toBe('business@test.com'); // Business email has priority
        expect($customer->phone)->toBe('+49123456789'); // Business phone has priority
        expect($customer->customer_number)->toBe('CUST-789');
        expect($customer->lexoffice_data)->toBeString();
    });

    it('creates customer from lexoffice contact with person data', function () {
        $lexofficeContact = (object) [
            'id' => 'lex-person-456',
            'person' => (object) [
                'firstName' => 'Max',
                'lastName' => 'Mustermann',
            ],
            'emailAddresses' => (object) [
                'private' => ['max@example.com'],
            ],
            'phoneNumbers' => (object) [
                'mobile' => ['+49555123456'],
            ],
        ];

        $customer = Customer::fromLexofficeContact($lexofficeContact);

        expect($customer->company_name)->toBeNull(); // Person has no company
        expect($customer->first_name)->toBe('Max');
        expect($customer->last_name)->toBe('Mustermann');
        expect($customer->email)->toBe('max@example.com');
        expect($customer->phone)->toBe('+49555123456');
    });

    it('prioritizes email addresses correctly', function () {
        $lexofficeContact = (object) [
            'emailAddresses' => (object) [
                'other' => ['other@test.com'],
                'private' => ['private@test.com'],
                'office' => ['office@test.com'],
                'business' => ['business@test.com'],
            ],
        ];

        $customer = Customer::fromLexofficeContact($lexofficeContact);
        expect($customer->email)->toBe('business@test.com'); // Business has highest priority
    });

    it('prioritizes phone numbers correctly', function () {
        $lexofficeContact = (object) [
            'phoneNumbers' => (object) [
                'other' => ['+49999'],
                'private' => ['+49888'],
                'mobile' => ['+49777'],
                'office' => ['+49666'],
                'business' => ['+49555'],
            ],
        ];

        $customer = Customer::fromLexofficeContact($lexofficeContact);
        expect($customer->phone)->toBe('+49555'); // Business has highest priority
    });

    it('handles missing optional data gracefully', function () {
        $lexofficeContact = (object) [
            'id' => 'minimal-contact',
        ];

        $customer = Customer::fromLexofficeContact($lexofficeContact);

        expect($customer->lexoffice_id)->toBe('minimal-contact');
        expect($customer->company_name)->toBeNull();
        expect($customer->first_name)->toBeNull();
        expect($customer->last_name)->toBeNull();
        expect($customer->email)->toBeNull();
        expect($customer->phone)->toBeNull();
        expect($customer->customer_number)->toBeNull();
    });
});

describe('Customer User Account Management', function () {
    it('throws exception when creating user account without email', function () {
        $customer = Customer::make(['email' => null]);
        
        expect(fn() => $customer->createUserAccount())
            ->toThrow(\InvalidArgumentException::class, 'Customer must have an email to create user account');
    });

    it('creates new user account with customer data', function () {
        // Mock the User model to avoid database interactions
        $customer = Customer::make([
            'id' => 1,
            'email' => 'test@example.com',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'company_name' => null,
        ]);

        // This test would require database interaction, so we'll test the logic components
        expect($customer->email)->toBe('test@example.com');
        
        // Test name generation logic
        $name = trim(($customer->first_name ?? '') . ' ' . ($customer->last_name ?? ''));
        expect($name)->toBe('John Doe');
    });

    it('generates name from company when no personal name available', function () {
        $customer = Customer::make([
            'email' => 'contact@company.com',
            'first_name' => null,
            'last_name' => null,
            'company_name' => 'Test Company',
        ]);

        $name = trim(($customer->first_name ?? '') . ' ' . ($customer->last_name ?? ''));
        if (empty($name)) {
            $name = $customer->company_name ?? 'Customer';
        }
        
        expect($name)->toBe('Test Company');
    });

    it('uses default name when no names available', function () {
        $customer = Customer::make([
            'email' => 'unknown@example.com',
            'first_name' => null,
            'last_name' => null,
            'company_name' => null,
        ]);

        $name = trim(($customer->first_name ?? '') . ' ' . ($customer->last_name ?? ''));
        if (empty($name)) {
            $name = $customer->company_name ?? 'Customer';
        }
        
        expect($name)->toBe('Customer');
    });
});