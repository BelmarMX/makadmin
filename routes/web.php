<?php

use App\Http\Controllers\Clinic\ClinicDashboardController;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

// Landing page (apex domain)
Route::inertia('/', 'Welcome', [
    'canRegister' => Features::enabled(Features::registration()),
])->name('home');

// Apex-domain authenticated users placeholder (redirects to clinic subdomain in production)
Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
});

// Admin subdomain (superadmin only)
Route::domain(config('branding.superadmin_subdomain').'.'.config('branding.apex_domain'))->group(function () {
    Route::middleware(['auth', 'super-admin'])->group(function () {
        require __DIR__.'/admin.php';
    });
});

// Clinic subdomain
Route::domain('{clinic}.'.config('branding.apex_domain'))->group(function () {
    Route::middleware(['tenant', 'auth'])->group(function () {
        Route::get('/', [ClinicDashboardController::class, 'index'])->name('clinic.dashboard');
    });
});

require __DIR__.'/settings.php';
