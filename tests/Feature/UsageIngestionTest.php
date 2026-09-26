<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\UsageEvent;
use App\Models\DailyUsageAggregate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UsageIngestionTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_record_usage_event_successfully(): void
    {
        $customer = Customer::factory()->create();

        $response = $this->postJson('/api/usage', [
            'customer_id' => $customer->id,
            'units' => 50,
            'usage_date' => now()->toDateString(),
            'idempotency_key' => 'unique-key-1',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('usage_events', ['customer_id' => $customer->id, 'units' => 50]);
        $this->assertDatabaseHas('daily_usage_aggregates', ['customer_id' => $customer->id, 'total_units' => 50]);
    }

    public function test_endpoint_is_strictly_idempotent(): void
    {
        $customer = Customer::factory()->create();
        $payload = [
            'customer_id' => $customer->id,
            'units' => 50,
            'usage_date' => now()->toDateString(),
            'idempotency_key' => 'unique-key-1',
        ];

        $this->postJson('/api/usage', $payload);
        $response = $this->postJson('/api/usage', $payload);

        $response->assertStatus(200);
        $this->assertEquals(1, UsageEvent::count());
        $this->assertEquals(50, DailyUsageAggregate::first()->total_units);
    }

    public function test_validation_catches_invalid_or_missing_fields(): void
    {
        $response = $this->postJson('/api/usage', ['units' => -1]);
        $response->assertStatus(422);
    }

    public function test_rate_limiter_blocks_excessive_traffic(): void
    {
        $customer = Customer::factory()->create();
        for ($i = 0; $i < 125; $i++) {
            $this->postJson('/api/usage', [
                'customer_id' => $customer->id,
                'units' => 1,
                'usage_date' => now()->toDateString(),
                'idempotency_key' => 'key-' . $i,
            ]);
        }
        $response = $this->postJson('/api/usage', [
            'customer_id' => $customer->id,
            'units' => 1,
            'usage_date' => now()->toDateString(),
            'idempotency_key' => 'too-many',
        ]);
        $response->assertStatus(429);
    }
}
