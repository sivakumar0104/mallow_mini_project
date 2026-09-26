<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Merchant;
use App\Models\DailyUsageAggregate;
use App\Models\Subscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class MerchantDashboardController extends Controller
{
    public function show(Merchant $merchant): JsonResponse
    {
        $thirtyDaysAgo = now()->subDays(30);

        // 1 & 2. Usage Overview & Revenue Projection
        $metrics = Subscription::whereHas('customer', fn($q) => $q->where('merchant_id', $merchant->id))
            ->where('status', 'active')
            ->join('plans', 'subscriptions.plan_id', '=', 'plans.id')
            ->join('daily_usage_aggregates', 'subscriptions.customer_id', '=', 'daily_usage_aggregates.customer_id')
            ->where('daily_usage_aggregates.date', '>=', DB::raw('subscriptions.cycle_start_date'))
            ->selectRaw('
                SUM(daily_usage_aggregates.total_units) as total_usage,
                SUM(plans.included_units) as total_included,
                SUM(CASE WHEN daily_usage_aggregates.total_units > plans.included_units 
                         THEN (daily_usage_aggregates.total_units - plans.included_units) * plans.overage_rate_per_unit 
                         ELSE 0 END) as revenue
            ')
            ->first();

        // 3. Active Plan Summary
        $popularPlan = $merchant->plans()
            ->join('subscriptions', 'plans.id', '=', 'subscriptions.plan_id')
            ->select('plans.name', 'plans.billing_cycle', DB::raw('count(*) as count'))
            ->groupBy('plans.id', 'plans.name', 'plans.billing_cycle')
            ->orderByDesc('count')
            ->first();

        // 4. Top 5 Customers
        $topCustomers = $merchant->customers()
            ->join('subscriptions', 'customers.id', '=', 'subscriptions.customer_id')
            ->join('plans', 'subscriptions.plan_id', '=', 'plans.id')
            ->join('daily_usage_aggregates', 'customers.id', '=', 'daily_usage_aggregates.customer_id')
            ->where('subscriptions.status', 'active')
            ->where('daily_usage_aggregates.date', '>=', DB::raw('subscriptions.cycle_start_date'))
            ->select('customers.id', 'customers.name', 'daily_usage_aggregates.total_units', 'plans.included_units')
            ->orderByDesc('daily_usage_aggregates.total_units')
            ->limit(5)
            ->get()
            ->map(fn($c) => [
                'customer_id' => $c->id,
                'customer_name' => $c->name,
                'usage_units' => $c->total_units,
                'allowance_units' => $c->included_units,
                'percentage_of_allowance' => $c->included_units > 0 ? round(($c->total_units / $c->included_units) * 100, 1) : 0,
            ]);

        // 5. Churn Risk
        $churnRisk = $merchant->customers()
            ->join('daily_usage_aggregates', 'customers.id', '=', 'daily_usage_aggregates.customer_id')
            ->select('customers.name', 'customers.id')
            ->selectRaw("
                SUM(CASE WHEN date >= ? THEN total_units ELSE 0 END) as curr_usage,
                SUM(CASE WHEN date >= ? AND date < ? THEN total_units ELSE 0 END) as prev_usage
            ", [now()->subDays(30), now()->subDays(60), now()->subDays(30)])
            ->groupBy('customers.id', 'customers.name')
            ->having('prev_usage', '>', 0)
            ->get()
            ->filter(fn($c) => (($c->prev_usage - $c->curr_usage) / $c->prev_usage) > 0.5)
            ->map(fn($c) => "{$c->name} — " . round((($c->prev_usage - $c->curr_usage) / $c->prev_usage) * 100, 1) . "% drop");

        // 6. Usage Trend (Last 30 Days)
        $trend = DailyUsageAggregate::whereIn('customer_id', $merchant->customers()->select('id'))
            ->where('date', '>=', $thirtyDaysAgo)
            ->select('date', DB::raw('SUM(total_units) as units'))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return response()->json([
            'current_cycle_usage' => (int) ($metrics->total_usage ?? 0),
            'total_included_units' => (int) ($metrics->total_included ?? 0),
            'projected_overage_revenue' => (float) ($metrics->revenue ?? 0),
            'popular_plan' => $popularPlan ? "{$popularPlan->name} — {$popularPlan->billing_cycle}" : 'N/A',
            'top_customers' => $topCustomers,
            'churn_risk' => $churnRisk->values(),
            'daily_usage_trend' => $trend,
            'system_status' => [
                'plan_pricing_cache' => "Redis, TTL 6h",
                'nightly_aggregation_job' => "queued, chunked (100 rows/batch)",
                'usage_endpoint' => "rate-limited 120 req/min per API key",
            ],
        ]);
    }
}
