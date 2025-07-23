<?php

namespace Database\Factories;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Customer>
 */
class CustomerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'customer_number' => 'CUST-' . $this->faker->unique()->numberBetween(1000, 9999),
            'lexoffice_id' => $this->faker->uuid(),
            'organization_id' => $this->faker->optional()->uuid(),
            'company_name' => $this->faker->company(),
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => $this->faker->phoneNumber(),
            'street' => $this->faker->streetAddress(),
            'zip' => $this->faker->postcode(),
            'city' => $this->faker->city(),
            'country' => $this->faker->countryCode(),
            'billing_day' => $this->faker->numberBetween(1, 28),
            'lexoffice_data' => json_encode([
                'id' => $this->faker->uuid(),
                'organizationId' => $this->faker->uuid(),
            ]),
        ];
    }

    /**
     * Create a customer without a company (person).
     */
    public function person(): static
    {
        return $this->state(fn (array $attributes) => [
            'company_name' => null,
        ]);
    }

    /**
     * Create a customer without lexoffice integration.
     */
    public function withoutLexoffice(): static
    {
        return $this->state(fn (array $attributes) => [
            'lexoffice_id' => null,
            'lexoffice_data' => null,
        ]);
    }
}