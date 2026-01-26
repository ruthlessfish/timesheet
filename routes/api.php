<?php

use App\Http\Controllers\Api;
use Illuminate\Support\Facades\Route;

// Public routes
Route::post('register', [Api\AuthController::class, 'register'])->name('api.register');
Route::post('login', [Api\AuthController::class, 'login'])->name('api.login');

// Protected routes
Route::middleware('auth:sanctum')->name('api.')->group(function () {
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
