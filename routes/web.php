<?php

use App\Http\Controllers\Api\CatalogController;
use App\Http\Controllers\Clinic\ClinicDashboardController;
use App\Http\Controllers\Clinic\ProfileController as ClinicProfileController;
use App\Http\Controllers\Clinic\UserController as ClinicUserController;
use App\Http\Controllers\Clinic\UserPermissionController;
use App\Http\Controllers\Clinic\UserRoleController;
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
    Route::middleware(['tenant', 'auth', 'clinic-access'])->group(function () {
        Route::get('/', [ClinicDashboardController::class, 'index'])->name('clinic.dashboard');

        Route::prefix('users')->name('clinic.users.')->group(function () {
            Route::get('/', [ClinicUserController::class, 'index'])->middleware('permission:users.view')->name('index');
            Route::get('/create', [ClinicUserController::class, 'create'])->middleware('permission:users.create')->name('create');
            Route::post('/', [ClinicUserController::class, 'store'])->middleware('permission:users.create')->name('store');
            Route::get('/{user}', [ClinicUserController::class, 'show'])->middleware('permission:users.view')->name('show');
            Route::get('/{user}/edit', [ClinicUserController::class, 'edit'])->middleware('permission:users.update')->name('edit');
            Route::put('/{user}', [ClinicUserController::class, 'update'])->middleware('permission:users.update')->name('update');
            Route::patch('/{user}/permissions', [UserPermissionController::class, 'update'])->middleware('permission:users.manage_permissions')->name('permissions.update');
            Route::post('/{user}/roles', [UserRoleController::class, 'store'])->middleware('permission:users.manage_roles')->name('roles.store');
            Route::delete('/{user}/roles', [UserRoleController::class, 'destroy'])->middleware('permission:users.manage_roles')->name('roles.destroy');
            Route::post('/{user}/deactivate', [ClinicUserController::class, 'deactivate'])->middleware('permission:users.deactivate')->name('deactivate');
            Route::post('/{user}/restore', [ClinicUserController::class, 'restore'])->middleware('permission:users.restore')->name('restore');
        });

        Route::prefix('profile')->name('clinic.profile.')->group(function () {
            Route::get('/', [ClinicProfileController::class, 'edit'])->name('edit');
            Route::put('/', [ClinicProfileController::class, 'update'])->name('update');
        });
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
