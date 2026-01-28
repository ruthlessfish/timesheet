<?php

use App\Http\Controllers\CalendarController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\Settings\ThemeController;
use App\Http\Controllers\TimeEntryController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::resource('clients', ClientController::class);
    Route::resource('projects', ProjectController::class);

    // Time entry bulk operations (must be before resource routes)
    Route::post('/time-entries/bulk-delete', [TimeEntryController::class, 'bulkDelete'])->name('time-entries.bulk-delete');
    Route::get('/time-entries/bulk-edit', [TimeEntryController::class, 'bulkEditForm'])->name('time-entries.bulk-edit');
    Route::patch('/time-entries/bulk-update', [TimeEntryController::class, 'bulkUpdate'])->name('time-entries.bulk-update');

    Route::resource('time-entries', TimeEntryController::class);
    Route::resource('invoices', InvoiceController::class);

    // Time entry specific routes
    Route::post('/time-entries/{timeEntry}/stop', [TimeEntryController::class, 'stop'])->name('time-entries.stop');

    // Invoice specific routes
    Route::get('/invoices/{invoice}/pdf', [InvoiceController::class, 'pdf'])->name('invoices.pdf');

    // Calendar routes
    Route::get('/calendar', [CalendarController::class, 'index'])->name('calendar.index');
    Route::get('/calendar/entries', [CalendarController::class, 'entries'])->name('calendar.entries');

    // Profile routes
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::patch('/settings/theme', [ThemeController::class, 'update'])->name('settings.theme.update');
});

require __DIR__.'/auth.php';
