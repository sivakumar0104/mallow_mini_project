<?php

namespace App\Services;

use App\Models\Plan;
use Illuminate\Support\Facades\Cache;

class PlanService
{
    public function getPlan(int $planId): Plan
    {
        return Cache::remember("plans:{$planId}", now()->addHours(6), function () use ($planId) {
            return Plan::findOrFail($planId);
        });
    }
}
