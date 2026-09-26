# Code Review: Usage Ingestion Module

## Overview
Review of the draft implementation for the `/usage` endpoint.

## Feedback

### 1. Null Safety & Error Handling
- **Issue:** `Customer::find($request->customer_id)` will return `null` if the customer does not exist. Calling `$customer->id` on a null result will trigger a `FatalThrowableError` (Attempt to read property "id" on null).
- **Recommendation:** Always use `findOrFail` or rely on the `exists` validation rule to ensure the customer exists before proceeding.

### 2. Validation
- **Issue:** The draft performs no validation on incoming requests, making the endpoint vulnerable to malformed data, negative units, or future-dated events.

### 3. Idempotency & Concurrency
- **Issue:** The draft fails to handle idempotency. Repeated requests with the same payload will result in multiple `UsageEvent` records and double-billing.
- **Recommendation:** Implement a unique index on `(customer_id, idempotency_key)` at the database level and use `DB::transaction` or `updateOrCreate` to handle duplicate attempts gracefully.

### 4. Performance (High Write Throughput)
- **Issue:** Creating an Eloquent model (`new UsageEvent()`) for every single request adds significant overhead. For 5M+ rows, consider `UsageEvent::insert([...])` or a buffered approach if the rate exceeds DB capacity.
- **Recommendation:** Atomic database operations are essential. Use `DB::raw` incrementing or `upsert` for aggregates to maintain data integrity under load.

### 5. REST Standards
- **Issue:** Returning HTTP 200 for a resource creation is non-standard.
- **Recommendation:** Use HTTP 201 (Created) for successful resource creation and handle duplicates explicitly with HTTP 200 or 409 (Conflict).

## Improved Solution

```php
public function store(StoreUsageRequest $request): JsonResponse
{
    $validated = $request->validated();

    // Check for existing idempotency key
    $existing = UsageEvent::where('customer_id', $validated['customer_id'])
        ->where('idempotency_key', $validated['idempotency_key'])
        ->first();

    if ($existing) {
        return response()->json(['status' => 'duplicate'], 200);
    }

    return DB::transaction(function () use ($validated) {
        UsageEvent::create($validated);

        DailyUsageAggregate::updateOrCreate(
            ['customer_id' => $validated['customer_id'], 'date' => $validated['usage_date']],
            ['total_units' => DB::raw('total_units + ' . $validated['units'])]
        );

        return response()->json(['status' => 'created'], 201);
    });
}
```
