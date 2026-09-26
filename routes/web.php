<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/merchants/{merchant}/dashboard-ui', function (\App\Models\Merchant $merchant) {
    $controller = new \App\Http\Controllers\Api\MerchantDashboardController();
    $data = $controller->show($merchant)->getData(true);
    return view('merchant.dashboard', compact('merchant', 'data'));
});
