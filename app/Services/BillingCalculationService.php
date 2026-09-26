<?php

namespace App\Services;

use App\Models\Subscription;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\UsageEvent;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class BillingCalculationService
{
    public function generateInvoice(Subscription $subscription): Invoice
    {
        return DB::transaction(function () use ($subscription) {
            $periods = $subscription->subscriptionPeriods;
            $cycleStart = Carbon::parse($subscription->cycle_start_date);
            $cycleEnd = Carbon::parse($subscription->cycle_end_date);
            $totalDays = $cycleStart->diffInDays($cycleEnd) + 1;

            $baseAmount = 0;
            $overageAmount = 0;
            $invoiceItems = [];

            foreach ($periods as $period) {
                $pStart = Carbon::parse($period->start_date)->max($cycleStart);
                $pEnd = Carbon::parse($period->end_date ?? $cycleEnd)->min($cycleEnd);
                
                $daysInSegment = $pStart->diffInDays($pEnd) + 1;
                $prorationFactor = $daysInSegment / $totalDays;

                $plan = $period->plan;
                
                // Base Fee Calculation
                $segmentBaseFee = (int) ($plan->base_price * $prorationFactor);
                $baseAmount += $segmentBaseFee;

                // Included Units & Overage
                $segmentAllowedUnits = (int) ($plan->included_units * $prorationFactor);
                $segmentUsage = UsageEvent::where('customer_id', $subscription->customer_id)
                    ->whereBetween('usage_date', [$pStart, $pEnd])
                    ->sum('units');
                
                $overageUnits = max(0, $segmentUsage - $segmentAllowedUnits);
                $segmentOverageFee = (int) ($overageUnits * $plan->overage_rate_per_unit * 100);
                $overageAmount += $segmentOverageFee;

                $invoiceItems[] = [
                    'plan_id' => $plan->id,
                    'description' => "Prorated base fee for {$plan->name} ({$daysInSegment} days)",
                    'units_consumed' => $segmentUsage,
                    'rate' => $plan->overage_rate_per_unit * 100,
                    'amount' => $segmentBaseFee + $segmentOverageFee,
                    'proration_factor' => $prorationFactor,
                ];
            }

            $invoice = Invoice::create([
                'customer_id' => $subscription->customer_id,
                'subscription_id' => $subscription->id,
                'cycle_start' => $subscription->cycle_start_date,
                'cycle_end' => $subscription->cycle_end_date,
                'base_amount' => $baseAmount,
                'overage_amount' => $overageAmount,
                'total_amount' => $baseAmount + $overageAmount,
                'status' => 'unpaid',
            ]);

            foreach ($invoiceItems as $item) {
                $invoice->invoiceItems()->create($item);
            }

            return $invoice;
        });
    }
}
