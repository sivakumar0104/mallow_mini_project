<?php

namespace App\Jobs;

use App\Models\Subscription;
use App\Services\BillingCalculationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class GenerateCycleInvoicesJob implements ShouldQueue
{
    use Queueable;

    public function handle(BillingCalculationService $billingService): void
    {
        Subscription::where('cycle_end_date', '<=', now()->toDateString())
            ->chunkById(100, function ($subscriptions) use ($billingService) {
                foreach ($subscriptions as $subscription) {
                    try {
                        $billingService->generateInvoice($subscription);

                        $subscription->update([
                            'cycle_start_date' => Carbon::parse($subscription->cycle_end_date)->addDay(),
                            'cycle_end_date' => Carbon::parse($subscription->cycle_end_date)->addMonth(),
                        ]);
                    } catch (\Exception $e) {
                        Log::error("Failed to generate invoice for subscription {$subscription->id}: " . $e->getMessage());
                    }
                }
            });
    }
}
