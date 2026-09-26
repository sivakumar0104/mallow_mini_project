<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\UsageController;
use App\Http\Controllers\Api\MerchantDashboardController;

Route::post('/usage', [UsageController::class, 'store'])
    ->middleware('throttle:usage-limiter');

Route::get('/merchants/{merchant}/dashboard', [MerchantDashboardController::class, 'show']);
