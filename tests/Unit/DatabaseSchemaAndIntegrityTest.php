<?php

namespace Tests\Unit;

use App\Models\{Customer, Merchant, Plan, Subscription, SubscriptionPeriod, UsageEvent, DailyUsageAggregate, Invoice, InvoiceItem};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\QueryException;
use Tests\TestCase;

class DatabaseSchemaAndIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public function test_unique_idempotency_constraint(): void
    {
        $customer = Customer::factory()->create();
        $payload = ['customer_id' => $customer->id, 'idempotency_key' => 'dup-key', 'units' => 10, 'usage_date' => now()];

        UsageEvent::create($payload);

        $this->expectException(QueryException::class);
        UsageEvent::create($payload);
    }

    public function test_daily_rollup_aggregate_integrity(): void
    {
        $customer = Customer::factory()->create();
        $date = now()->toDateString();

        $aggregate = DailyUsageAggregate::create([
            'customer_id' => $customer->id,
            'date' => $date,
            'total_units' => 0,
        ]);

        foreach (range(1, 5) as $i) {
            DailyUsageAggregate::where('id', $aggregate->id)
                ->increment('total_units', 20);
        }

        $this->assertEquals(1, DailyUsageAggregate::count());
        $this->assertEquals(100, DailyUsageAggregate::first()->fresh()->total_units);
    }

    public function test_multi_tenant_foreign_key_cascades(): void
    {
        $merchant = Merchant::factory()->create();
        $customer = Customer::factory()->create(['merchant_id' => $merchant->id]);
        $sub = Subscription::factory()->create(['customer_id' => $customer->id]);
        SubscriptionPeriod::factory()->create(['subscription_id' => $sub->id]);

        $merchant->delete();

        $this->assertDatabaseMissing('customers', ['id' => $customer->id]);
        $this->assertDatabaseMissing('subscriptions', ['id' => $sub->id]);
    }

    public function test_currency_precision_retention(): void
    {
        $plan = Plan::factory()->create(['base_price' => 123456]);
        $invoice = Invoice::factory()->create(['base_amount' => 123456]);
        $item = InvoiceItem::factory()->create(['amount' => 123456]);

        $this->assertIsInt($plan->fresh()->base_price);
        $this->assertIsInt($invoice->fresh()->base_amount);
        $this->assertIsInt($item->fresh()->amount);
        $this->assertEquals(123456, $plan->fresh()->base_price);
    }
}
