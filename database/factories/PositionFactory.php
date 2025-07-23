<?php

namespace Database\Factories;

use App\Models\Position;
use App\Models\Customer;
use App\Models\Invoice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Position>
 */
class PositionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'name' => $this->faker->words(3, true),
            'description' => $this->faker->sentence(),
            'quantity' => $this->faker->randomFloat(2, 1, 100),
            'unit_name' => $this->faker->randomElement(['hours', 'days', 'pieces', 'months']),
            'unit_price' => $this->faker->randomFloat(2, 10, 500),
            'discount' => $this->faker->randomFloat(2, 0, 25),
            'billed' => false,
            'billed_at' => null,
            'invoice_id' => null,
        ];
    }

    /**
     * Create a billed position.
     */
    public function billed(): static
    {
        return $this->state(fn (array $attributes) => [
            'billed' => true,
            'billed_at' => $this->faker->dateTimeBetween('-6 months', 'now'),
            'invoice_id' => Invoice::factory(),
        ]);
    }

    /**
     * Create an unbilled position.
     */
    public function unbilled(): static
    {
        return $this->state(fn (array $attributes) => [
            'billed' => false,
            'billed_at' => null,
            'invoice_id' => null,
        ]);
    }

    /**
     * Create a position with no discount.
     */
    public function withoutDiscount(): static
    {
        return $this->state(fn (array $attributes) => [
            'discount' => 0,
        ]);
    }

    /**
     * Create a position with specific service type.
     */
    public function webDevelopment(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Web Development',
            'description' => 'Frontend and backend development work',
            'unit_name' => 'hours',
            'unit_price' => $this->faker->randomFloat(2, 75, 150),
        ]);
    }

    /**
     * Create a position with specific service type.
     */
    public function consulting(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Consulting',
            'description' => 'Technical consulting and advice',
            'unit_name' => 'hours',
            'unit_price' => $this->faker->randomFloat(2, 100, 200),
        ]);
    }
}