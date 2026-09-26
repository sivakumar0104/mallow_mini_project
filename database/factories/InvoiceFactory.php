<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Subscription;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Invoice>
 */
class InvoiceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'subscription_id' => Subscription::factory(),
            'cycle_start' => now()->subMonth()->startOfMonth(),
            'cycle_end' => now()->subMonth()->endOfMonth(),
            'base_amount' => 5000,
            'overage_amount' => 0,
            'total_amount' => 5000,
            'status' => 'paid',
        ];
    }
}
