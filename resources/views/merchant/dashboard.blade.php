<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Merchant Dashboard — {{ $merchant->name }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-gray-50 min-h-screen p-8">
    <header class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Merchant Dashboard — <span class="text-indigo-600">{{ $merchant->name }}</span></h1>
    </header>

    <!-- Top Metric Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="p-6 bg-white border-l-4 border-blue-500 shadow-sm rounded-lg">
            <h3 class="text-sm font-semibold text-gray-500 uppercase">Current Cycle Usage</h3>
            <p class="text-2xl font-bold">{{ number_format($data['current_cycle_usage']) }} / {{ number_format($data['total_included_units']) }} units</p>
        </div>
        <div class="p-6 bg-white border-l-4 border-orange-500 shadow-sm rounded-lg">
            <h3 class="text-sm font-semibold text-gray-500 uppercase">Projected Overage Revenue</h3>
            <p class="text-2xl font-bold">₹ {{ number_format($data['projected_overage_revenue'], 2) }}</p>
        </div>
        <div class="p-6 bg-white border-l-4 border-green-500 shadow-sm rounded-lg">
            <h3 class="text-sm font-semibold text-gray-500 uppercase">Active Plan</h3>
            <p class="text-2xl font-bold">{{ $data['popular_plan'] }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Left Section (2/3) -->
        <div class="lg:col-span-2 space-y-8">
            <div class="bg-white p-6 shadow-sm rounded-lg">
                <h2 class="text-xl font-bold mb-4">Top 5 Customers by Usage</h2>
                <table class="w-full text-left">
                    <thead><tr class="border-b"><th class="py-2">Customer</th><th class="py-2">Usage</th><th class="py-2">% Allowance</th></tr></thead>
                    <tbody>
                        @foreach($data['top_customers'] as $c)
                        <tr class="border-b">
                            <td class="py-3">{{ $c['customer_name'] }}</td>
                            <td class="py-3">{{ number_format($c['usage_units']) }}</td>
                            <td class="py-3">{{ $c['percentage_of_allowance'] }}%</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="bg-white p-6 shadow-sm rounded-lg">
                <h2 class="text-xl font-bold mb-4">Daily Usage Trend (last 30 days)</h2>
                <canvas id="usageChart" height="100"></canvas>
            </div>
        </div>

        <!-- Right Section (1/3) -->
        <div class="space-y-8">
            <div class="bg-red-50 p-6 border border-red-200 rounded-lg">
                <h2 class="text-lg font-bold text-red-800 mb-2">⚠ Churn Risk</h2>
                                <ul class="text-red-700 space-y-1">
                    @forelse($data['churn_risk'] as $risk)
                    <li>{{ $risk }}</li>
                    @empty
                    <li>No churn risks identified.</li>
                    @endforelse
                </ul>
            </div>
            <div class="bg-blue-50 p-6 border border-blue-200 rounded-lg text-sm text-blue-800 space-y-2">
                <h2 class="font-bold">System Status</h2>
                <p><strong>Pricing Cache:</strong> {{ $data['system_status']['plan_pricing_cache'] }}</p>
                <p><strong>Aggregation Job:</strong> {{ $data['system_status']['nightly_aggregation_job'] }}</p>
                <p><strong>Endpoint:</strong> {{ $data['system_status']['usage_endpoint'] }}</p>
            </div>
        </div>
    </div>

    <script>
        const ctx = document.getElementById('usageChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: {!! json_encode(collect($data['daily_usage_trend'])->pluck('date')) !!},
                datasets: [{
                    label: 'Total Units',
                    data: {!! json_encode(collect($data['daily_usage_trend'])->pluck('units')) !!},
                    borderColor: '#4f46e5',
                    tension: 0.1
                }]
            }
        });
    </script>
</body>
</html>
