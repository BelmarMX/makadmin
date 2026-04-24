<?php

use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\ClinicAdminController;
use App\Http\Controllers\Admin\ClinicBranchController;
use App\Http\Controllers\Admin\ClinicController;
use App\Http\Controllers\Admin\ClinicModuleController;
use Illuminate\Support\Facades\Route;

Route::get('/', [AdminDashboardController::class, 'index'])->name('admin.dashboard');

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
});
