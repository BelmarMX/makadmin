<?php

use App\Http\Controllers\Api\CatalogController;
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

// Internal catalog API — accessible from any subdomain, requires auth
Route::middleware(['auth'])->prefix('api/catalog')->name('api.catalog.')->group(function () {
    Route::get('countries', [CatalogController::class, 'countries'])->name('countries');
    Route::get('states', [CatalogController::class, 'states'])->name('states');
    Route::get('municipalities', [CatalogController::class, 'municipalities'])->name('municipalities');
    Route::get('postal-codes', [CatalogController::class, 'postalCodes'])->name('postal-codes');
    Route::get('species', [CatalogController::class, 'species'])->name('species');
    Route::get('breeds', [CatalogController::class, 'breeds'])->name('breeds');
    Route::get('pelage-colors', [CatalogController::class, 'pelageColors'])->name('pelage-colors');
    Route::get('pet-sizes', [CatalogController::class, 'petSizes'])->name('pet-sizes');
    Route::get('temperaments', [CatalogController::class, 'temperaments'])->name('temperaments');
});

require __DIR__.'/settings.php';
