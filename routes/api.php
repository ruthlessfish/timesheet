<?php

use App\Http\Controllers\Api;
use Illuminate\Support\Facades\Route;

// API Version 1
Route::prefix('v1')->name('api.v1.')->middleware('throttle:api')->group(function () {
    // Public routes - stricter rate limiting for auth endpoints
    Route::middleware('throttle:auth')->group(function () {
        Route::post('register', [Api\AuthController::class, 'register'])->name('register');
        Route::post('login', [Api\AuthController::class, 'login'])->name('login');
    });

    // Protected routes
    Route::middleware('auth:sanctum')->group(function () {
        // Auth
        Route::post('logout', [Api\AuthController::class, 'logout'])->name('logout');
        Route::get('user', [Api\AuthController::class, 'user'])->name('user');
        
        // Clients
        Route::apiResource('clients', Api\ClientController::class);
        
        // Projects
        Route::apiResource('projects', Api\ProjectController::class);
        Route::get('clients/{client}/projects', [Api\ProjectController::class, 'byClient'])->name('clients.projects');
        
        // Time Entries
        Route::get('time-entries/active', [Api\TimeEntryController::class, 'active'])->name('time-entries.active');
        Route::post('time-entries/{timeEntry}/stop', [Api\TimeEntryController::class, 'stop'])->name('time-entries.stop');
        Route::apiResource('time-entries', Api\TimeEntryController::class);
        
        // Invoices
        Route::get('clients/{client}/unbilled-entries', [Api\InvoiceController::class, 'unbilledEntries'])->name('clients.unbilled-entries');
        Route::get('invoices/{invoice}/pdf', [Api\InvoiceController::class, 'downloadPdf'])->name('invoices.pdf');
        Route::apiResource('invoices', Api\InvoiceController::class);
        
        // Dashboard
        Route::get('dashboard/stats', [Api\DashboardController::class, 'stats'])->name('dashboard.stats');
        Route::get('dashboard/charts', [Api\DashboardController::class, 'charts'])->name('dashboard.charts');
    });
});
