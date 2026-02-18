<?php

use App\Http\Controllers\Api\BillController;
use App\Http\Controllers\Api\ChartOfAccountController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\ExpenseController;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\PlanController;
use App\Http\Controllers\Api\ProductServiceController;
use App\Http\Controllers\Api\RevenueController;
use App\Http\Controllers\Api\VenderController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Public routes
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'message' => 'Accountify API is running',
        'timestamp' => now()->toIso8601String(),
    ]);
});

// Protected routes (require authentication)
Route::middleware(['auth:sanctum'])->group(function () {

    // User info
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // Customer Management
    Route::apiResource('customers', CustomerController::class);

    // Vendor Management
    Route::apiResource('venders', VenderController::class);

    // Invoice Management
    Route::apiResource('invoices', InvoiceController::class);

    // Bill Management
    Route::apiResource('bills', BillController::class);

    // Product & Service Management
    Route::apiResource('products', ProductServiceController::class);

    // Chart of Accounts
    Route::apiResource('chart-of-accounts', ChartOfAccountController::class);

    // Payments
    Route::apiResource('payments', PaymentController::class);

    // Expenses
    Route::apiResource('expenses', ExpenseController::class);

    // Revenues
    Route::apiResource('revenues', RevenueController::class);

    // Plans
    Route::apiResource('plans', PlanController::class);
});

