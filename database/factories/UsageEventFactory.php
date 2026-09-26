<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\UsageEvent;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<UsageEvent>
 */
class UsageEventFactory extends Factory
{
    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'idempotency_key' => Str::random(64),
            'units' => $this->faker->numberBetween(1, 100),
            'usage_date' => now(),
        ];
    }
}
