<?php

namespace Database\Seeders;

use App\Models\{Merchant, Customer, Plan, Subscription, DailyUsageAggregate};
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DashboardSeeder extends Seeder
{
    public function run(): void
    {
        $merchant = Merchant::create([
            'id' => 1,
            'name' => 'Acme Corp',
            'api_key' => Str::random(64),
        ]);

        $plan = Plan::create([
            'merchant_id' => $merchant->id,
            'name' => 'Growth',
            'base_price' => 5000,
            'billing_cycle' => 'monthly',
            'included_units' => 1000,
            'overage_rate_per_unit' => 0.05,
        ]);

        for ($i = 1; $i <= 5; $i++) {
            $customer = Customer::create([
                'merchant_id' => $merchant->id,
                'name' => "Customer $i",
                'email' => "cust$i@example.com",
            ]);

            Subscription::create([
                'customer_id' => $customer->id,
                'plan_id' => $plan->id,
                'status' => 'active',
                'starts_at' => now(),
                'cycle_start_date' => now()->startOfMonth(),
                'cycle_end_date' => now()->endOfMonth(),
            ]);

            // Add usage
            DailyUsageAggregate::create([
                'customer_id' => $customer->id,
                'date' => now()->toDateString(),
                'total_units' => rand(500, 1500),
            ]);

            // Add churn risk data (prev month high usage)
            if ($i === 1) {
                DailyUsageAggregate::create([
                    'customer_id' => $customer->id,
                    'date' => now()->subDays(40)->toDateString(),
                    'total_units' => 1000,
                ]);
            }
        }
    }
}
