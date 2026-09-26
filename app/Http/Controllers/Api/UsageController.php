<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUsageRequest;
use App\Models\UsageEvent;
use App\Models\DailyUsageAggregate;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class UsageController extends Controller
{
    public function store(StoreUsageRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $existing = UsageEvent::where('customer_id', $validated['customer_id'])
            ->where('idempotency_key', $validated['idempotency_key'])
            ->first();

        if ($existing) {
            return response()->json([
                'status' => 'duplicate',
                'message' => 'Usage event already recorded',
                'data' => $existing,
            ], 200);
        }

        return DB::transaction(function () use ($validated) {
            $usage = UsageEvent::create($validated);

            DailyUsageAggregate::updateOrCreate(
                [
                    'customer_id' => $validated['customer_id'],
                    'date' => $validated['usage_date'],
                ],
                [
                    'total_units' => DB::raw('total_units + ' . $validated['units']),
                ]
            );

            return response()->json([
                'status' => 'created',
            ], 201);
        });
    }
}
