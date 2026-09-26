<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InvoiceItem>
 */
class InvoiceItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'invoice_id' => Invoice::factory(),
            'plan_id' => Plan::factory(),
            'description' => $this->faker->sentence(),
            'units_consumed' => $this->faker->numberBetween(1, 100),
            'rate' => 100,
            'amount' => 1000,
            'proration_factor' => 1.0000,
        ];
    }
}
