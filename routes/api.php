<?php

use App\Http\Controllers\Api\AccountController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\ProvisioningOrderController;
use App\Http\Controllers\Api\QuoteController;
use App\Http\Controllers\Api\TicketController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::get('dashboard', [DashboardController::class, 'summary']);
    Route::get('accounts', [AccountController::class, 'index']);
    Route::get('accounts/{account}', [AccountController::class, 'show']);
    Route::get('tickets', [TicketController::class, 'index']);
    Route::post('tickets', [TicketController::class, 'store']);
    Route::get('tickets/{ticket}', [TicketController::class, 'show']);
    Route::patch('tickets/{ticket}', [TicketController::class, 'update']);

    Route::get('quotes', [QuoteController::class, 'index']);
    Route::get('quotes/{quote}', [QuoteController::class, 'show']);
    Route::get('provisioning-orders', [ProvisioningOrderController::class, 'index']);
    Route::get('provisioning-orders/{provisioningOrder}', [ProvisioningOrderController::class, 'show']);
});
