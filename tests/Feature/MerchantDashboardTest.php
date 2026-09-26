<?php

namespace Tests\Feature;

use App\Models\Merchant;
use App\Models\Customer;
use App\Models\DailyUsageAggregate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MerchantDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_churn_risk_detection(): void
    {
        $merchant = Merchant::factory()->create();
        $stableCust = Customer::factory()->create(['merchant_id' => $merchant->id]);
        $churnCust = Customer::factory()->create(['merchant_id' => $merchant->id]);

        // Stable usage
        DailyUsageAggregate::factory()->create(['customer_id' => $stableCust->id, 'date' => now()->subDays(10), 'total_units' => 100]);
        DailyUsageAggregate::factory()->create(['customer_id' => $stableCust->id, 'date' => now()->subDays(40), 'total_units' => 100]);

        // Churn usage: 100 prev, 30 curr (70% drop)
        DailyUsageAggregate::factory()->create(['customer_id' => $churnCust->id, 'date' => now()->subDays(10), 'total_units' => 30]);
        DailyUsageAggregate::factory()->create(['customer_id' => $churnCust->id, 'date' => now()->subDays(40), 'total_units' => 100]);

        $response = $this->getJson("/api/merchants/{$merchant->id}/dashboard");

        $response->assertStatus(200);
        $this->assertStringContainsString($churnCust->name, $response->json('churn_risk.0'));
    }
}
