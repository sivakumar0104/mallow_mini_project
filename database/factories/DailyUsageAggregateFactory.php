<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\DailyUsageAggregate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DailyUsageAggregate>
 */
class DailyUsageAggregateFactory extends Factory
{
    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'date' => now(),
            'total_units' => $this->faker->numberBetween(100, 10000),
        ];
    }
}
