<?php

use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\CatalogController as AdminCatalogController;
use App\Http\Controllers\Admin\ClinicAdminController;
use App\Http\Controllers\Admin\ClinicBranchController;
use App\Http\Controllers\Admin\ClinicController;
use App\Http\Controllers\Admin\ClinicModuleController;
use App\Http\Controllers\Admin\ClinicRoleModuleController;
use App\Http\Controllers\Admin\ClinicUserController;
use Illuminate\Support\Facades\Route;

Route::get('/', [AdminDashboardController::class, 'index'])->name('admin.dashboard');

Route::prefix('catalog')->name('admin.catalog.')->group(function () {
    Route::get('/', [AdminCatalogController::class, 'index'])->name('index');
    Route::post('/', [AdminCatalogController::class, 'store'])->name('store');
    Route::put('/{id}', [AdminCatalogController::class, 'update'])->name('update');
    Route::delete('/{id}', [AdminCatalogController::class, 'destroy'])->name('destroy');
});

Route::prefix('clinics')->name('admin.clinics.')->group(function () {
    Route::get('/', [ClinicController::class, 'index'])->name('index');
    Route::get('/create', [ClinicController::class, 'create'])->name('create');
    Route::post('/', [ClinicController::class, 'store'])->name('store');
    Route::get('/{clinic}', [ClinicController::class, 'show'])->name('show');
    Route::get('/{clinic}/edit', [ClinicController::class, 'edit'])->name('edit');
    Route::put('/{clinic}', [ClinicController::class, 'update'])->name('update');
    Route::delete('/{clinic}', [ClinicController::class, 'destroy'])->name('destroy');
    Route::post('/{clinic}/activate', [ClinicController::class, 'activate'])->name('activate');
    Route::post('/{clinic}/deactivate', [ClinicController::class, 'deactivate'])->name('deactivate');

    Route::post('/{clinic}/branches', [ClinicBranchController::class, 'store'])->name('branches.store');
    Route::put('/{clinic}/branches/{branch}', [ClinicBranchController::class, 'update'])->name('branches.update');
    Route::delete('/{clinic}/branches/{branch}', [ClinicBranchController::class, 'destroy'])->name('branches.destroy');

    Route::post('/{clinic}/modules/{module}/toggle', [ClinicModuleController::class, 'toggle'])->name('modules.toggle');

    Route::post('/{clinic}/invite-admin', [ClinicAdminController::class, 'invite'])->name('invite-admin');
    Route::post('/{clinic}/users/{user}/verify-email', [ClinicAdminController::class, 'verifyEmail'])->name('users.verify-email');
    Route::put('/{clinic}/users/{user}', [ClinicUserController::class, 'update'])->name('users.update');
    Route::post('/{clinic}/users/{user}/activate', [ClinicUserController::class, 'activate'])->name('users.activate');
    Route::post('/{clinic}/users/{user}/deactivate', [ClinicUserController::class, 'deactivate'])->name('users.deactivate');
    Route::delete('/{clinic}/users/{user}', [ClinicUserController::class, 'destroy'])->name('users.destroy');
    Route::get('/{clinic}/role-modules/{role}', [ClinicRoleModuleController::class, 'show'])->name('role-modules.show');
    Route::put('/{clinic}/role-modules', [ClinicRoleModuleController::class, 'update'])->name('role-modules.update');

    Route::post('/{clinic}/logo', [ClinicController::class, 'uploadLogo'])->name('upload-logo');
    Route::delete('/{clinic}/logo', [ClinicController::class, 'destroyLogo'])->name('destroy-logo');
});
