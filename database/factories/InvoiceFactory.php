<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Invoice>
 */
class InvoiceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $totalAmount = $this->faker->randomFloat(2, 100, 5000);
        
        return [
            'number' => 'INV-' . $this->faker->year() . '-' . str_pad($this->faker->numberBetween(1, 999), 3, '0', STR_PAD_LEFT),
            'customer_id' => Customer::factory(),
            'date' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'due_date' => $this->faker->dateTimeBetween('now', '+2 months'),
            'total_amount' => $totalAmount,
            'open_amount' => $totalAmount, // Default to fully unpaid
            'status' => $this->faker->randomElement(['open', 'paid', 'draft', 'overdue']),
            'lexoffice_id' => $this->faker->uuid(),
            'lexoffice_data' => json_encode([
                'id' => $this->faker->uuid(),
                'voucherNumber' => 'INV-' . $this->faker->year() . '-' . $this->faker->numberBetween(1, 999),
                'voucherStatus' => 'open',
            ]),
            'web_payment_id' => null,
            'web_payment_status' => null,
            'web_payment_date' => null,
            'web_payment_amount' => null,
        ];
    }

    /**
     * Create a paid invoice.
     */
    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'paid',
            'open_amount' => 0.00,
        ]);
    }

    /**
     * Create an open invoice.
     */
    public function open(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'open',
            'open_amount' => $attributes['total_amount'] ?? $this->faker->randomFloat(2, 100, 5000),
        ]);
    }

    /**
     * Create an overdue invoice.
     */
    public function overdue(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'overdue',
            'due_date' => $this->faker->dateTimeBetween('-3 months', '-1 day'),
            'open_amount' => $attributes['total_amount'] ?? $this->faker->randomFloat(2, 100, 5000),
        ]);
    }

    /**
     * Create an invoice with web payment.
     */
    public function withWebPayment(): static
    {
        return $this->state(function (array $attributes) {
            $paymentAmount = $attributes['total_amount'] ?? $this->faker->randomFloat(2, 100, 5000);
            
            return [
                'status' => 'paid',
                'open_amount' => 0.00,
                'web_payment_id' => 'payment-' . $this->faker->uuid(),
                'web_payment_status' => 'completed',
                'web_payment_date' => $this->faker->dateTimeBetween('-1 month', 'now'),
                'web_payment_amount' => $paymentAmount,
            ];
        });
    }

    /**
     * Create an invoice without lexoffice integration.
     */
    public function withoutLexoffice(): static
    {
        return $this->state(fn (array $attributes) => [
            'lexoffice_id' => null,
            'lexoffice_data' => null,
        ]);
    }

    /**
     * Create a draft invoice.
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'draft',
            'number' => null, // Drafts might not have numbers yet
        ]);
    }
}