<?php

namespace Tests\Unit;

use App\Models\Customer;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\SubscriptionPeriod;
use App\Services\BillingCalculationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BillingCalculationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_mid_cycle_upgrade_proration(): void
    {
        $customer = Customer::factory()->create();
        $planA = Plan::factory()->create(['base_price' => 3000, 'included_units' => 300, 'overage_rate_per_unit' => 0.10]);
        $planB = Plan::factory()->create(['base_price' => 6000, 'included_units' => 600, 'overage_rate_per_unit' => 0.05]);
        
        $subscription = Subscription::factory()->create([
            'customer_id' => $customer->id,
            'plan_id' => $planA->id,
            'cycle_start_date' => '2026-09-01',
            'cycle_end_date' => '2026-09-30',
        ]);

        SubscriptionPeriod::factory()->create(['subscription_id' => $subscription->id, 'plan_id' => $planA->id, 'start_date' => '2026-09-01', 'end_date' => '2026-09-10']);
        SubscriptionPeriod::factory()->create(['subscription_id' => $subscription->id, 'plan_id' => $planB->id, 'start_date' => '2026-09-11', 'end_date' => '2026-09-30']);

        // Mock usage: Seg1: 150, Seg2: 300
        \App\Models\UsageEvent::factory()->create(['customer_id' => $customer->id, 'usage_date' => '2026-09-05', 'units' => 150]);
        \App\Models\UsageEvent::factory()->create(['customer_id' => $customer->id, 'usage_date' => '2026-09-15', 'units' => 300]);

        $service = new BillingCalculationService();
        $invoice = $service->generateInvoice($subscription);

        $this->assertEquals(5000, $invoice->base_amount); // 10 + 40
        $this->assertEquals(500, $invoice->overage_amount); // 5.00 in cents
        $this->assertEquals(5500, $invoice->total_amount);
    }
}
