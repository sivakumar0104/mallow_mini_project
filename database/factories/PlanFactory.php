<?php

namespace Database\Factories;

use App\Models\Merchant;
use App\Models\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Plan>
 */
class PlanFactory extends Factory
{
    public function definition(): array
    {
        return [
            'merchant_id' => Merchant::factory(),
            'name' => $this->faker->word() . ' Plan',
            'base_price' => $this->faker->numberBetween(1000, 100000), // 10 to 1000
            'billing_cycle' => $this->faker->randomElement(['monthly', 'yearly']),
            'included_units' => $this->faker->numberBetween(100, 10000),
            'overage_rate_per_unit' => $this->faker->randomFloat(4, 0.01, 1.00),
        ];
    }
}
