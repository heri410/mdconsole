<?php

namespace App\Models;

use Exception;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $fillable = [
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

    public static function fromLexofficeContact($contact): Customer
    {
        // Prepare data for new Customer instance
        $data = [
            'organization_id' => $contact->organizationId ?? null,
            'company_name' => isset($contact->company) ? ($contact->company->name ?? null) : null,
            'first_name' => null,
            'last_name' => null,
            'street' => isset($contact->addresses->billing[0]) ? ($contact->addresses->billing[0]->street ?? null) : null,
            'zip' => isset($contact->addresses->billing[0]) ? ($contact->addresses->billing[0]->zip ?? null) : null,
            'city' => isset($contact->addresses->billing[0]) ? ($contact->addresses->billing[0]->city ?? null) : null,
            'country' => isset($contact->addresses->billing[0]) ? ($contact->addresses->billing[0]->countryCode ?? null) : null,
            'email' => null,
            'phone' => null,
            'customer_number' => isset($contact->roles->customer->number) ? $contact->roles->customer->number : null,
            'lexoffice_data' => json_encode($contact),
        ];

        // Set first/last name for company contact person or person
        if (isset($contact->company) && isset($contact->company->contactPersons[0])) {
            $cp = $contact->company->contactPersons[0];
            $data['first_name'] = $cp->firstName ?? null;
            $data['last_name'] = $cp->lastName ?? null;
        } elseif (isset($contact->person)) {
            $data['first_name'] = $contact->person->firstName ?? null;
            $data['last_name'] = $contact->person->lastName ?? null;
            $data['company_name'] = null;
        }

        // Email (priority: business > office > private > other)
        if (isset($contact->emailAddresses)) {
            if (isset($contact->emailAddresses->business[0])) {
                $data['email'] = $contact->emailAddresses->business[0];
            } elseif (isset($contact->emailAddresses->office[0])) {
                $data['email'] = $contact->emailAddresses->office[0];
            } elseif (isset($contact->emailAddresses->private[0])) {
                $data['email'] = $contact->emailAddresses->private[0];
            } elseif (isset($contact->emailAddresses->other[0])) {
                $data['email'] = $contact->emailAddresses->other[0];
            }
        }

        // Phone (priority: business > office > mobile > private > other)
        if (isset($contact->phoneNumbers)) {
            if (isset($contact->phoneNumbers->business[0])) {
                $data['phone'] = $contact->phoneNumbers->business[0];
            } elseif (isset($contact->phoneNumbers->office[0])) {
                $data['phone'] = $contact->phoneNumbers->office[0];
            } elseif (isset($contact->phoneNumbers->mobile[0])) {
                $data['phone'] = $contact->phoneNumbers->mobile[0];
            } elseif (isset($contact->phoneNumbers->private[0])) {
                $data['phone'] = $contact->phoneNumbers->private[0];
            } elseif (isset($contact->phoneNumbers->other[0])) {
                $data['phone'] = $contact->phoneNumbers->other[0];
            }
        }

        // Customer number check (optional: Exception werfen, wenn nicht vorhanden)
        // if (!$data['customer_number']) {
        //     throw new \InvalidArgumentException("Customer {$contact->id} has no customer number");
        // }

        // Erstelle ein Customer-Objekt, aber speichere es nicht in der DB
        $customer = new Customer($data);
        $customer->lexoffice_id = $contact->id ?? null;
        return $customer;
    }

    /**
     * Get the users associated with this customer.
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }
    
    /**
     * Get the primary user for this customer.
     */
    public function primaryUser()
    {
        return $this->hasOne(User::class)->where('role', 'customer');
    }
    
    /**
     * Create a user account for this customer.
     */
    public function createUserAccount(string $password = null): User
    {
        if (!$this->email) {
            throw new \InvalidArgumentException('Customer must have an email to create user account');
        }
        
        // Prüfe ob bereits ein User existiert
        $existingUser = User::where('email', $this->email)->first();
        if ($existingUser) {
            // Update existing user with customer relationship
            $existingUser->update([
                'customer_id' => $this->id,
                'role' => 'customer'
            ]);
            return $existingUser;
        }
        
        // Erstelle neuen User
        $name = trim(($this->first_name ?? '') . ' ' . ($this->last_name ?? ''));
        if (empty($name)) {
            $name = $this->company_name ?? 'Customer';
        }
        
        return User::create([
            'name' => $name,
            'email' => $this->email,
            'password' => $password ?? \Illuminate\Support\Str::random(12),
            'customer_id' => $this->id,
            'role' => 'customer',
            'email_verified_at' => now(),
        ]);
    }
    
    /**
     * Get the invoices for the customer.
     */
    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }
    
    /**
     * Get the positions for the customer.
     */
    public function positions()
    {
        return $this->hasMany(Position::class);
    }
    
    /**
     * Get unbilled positions for the customer.
     */
    public function unbilledPositions()
    {
        return $this->hasMany(Position::class)->where('billed', false);
    }
    
    /**
     * Get billing day for this customer (default: 1st of month).
     */
    public function getBillingDayAttribute($value): int
    {
        return $value ?? 1;
    }
}
