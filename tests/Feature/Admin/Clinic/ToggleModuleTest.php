<?php

use App\Domain\Clinic\Models\Clinic;
use App\Domain\Clinic\Models\ClinicModule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;

uses(RefreshDatabase::class);

beforeEach(function () {
    config(['branding.apex_domain' => 'vetfollow.test']);
});

function toggleModule(User $user, Clinic $clinic, string $module, bool $activate): TestResponse
{
    return test()->actingAs($user)
        ->post(route('admin.clinics.modules.toggle', ['clinic' => $clinic, 'module' => $module]), [
            'activate' => $activate,
        ]);
}

test('super admin can activate a module', function () {
    $clinic = Clinic::factory()->create();
    $admin = User::factory()->create(['is_super_admin' => true]);

    toggleModule($admin, $clinic, 'patients', true)->assertRedirect();

    expect(ClinicModule::where('clinic_id', $clinic->id)->where('module_key', 'patients')->where('is_active', true)->exists())->toBeTrue();
});

test('activating a module cascades its dependencies', function () {
    $clinic = Clinic::factory()->create();
    $admin = User::factory()->create(['is_super_admin' => true]);

    toggleModule($admin, $clinic, 'controlled_drugs', true)->assertRedirect();

    expect(ClinicModule::where('clinic_id', $clinic->id)->where('module_key', 'inventory')->where('is_active', true)->exists())->toBeTrue();
    expect(ClinicModule::where('clinic_id', $clinic->id)->where('module_key', 'controlled_drugs')->where('is_active', true)->exists())->toBeTrue();
});

test('deactivating a module with active dependents fails', function () {
    $clinic = Clinic::factory()->create();
    $admin = User::factory()->create(['is_super_admin' => true]);

    // Activate inventory and controlled_drugs (which depends on inventory)
    ClinicModule::create(['clinic_id' => $clinic->id, 'module_key' => 'inventory', 'is_active' => true]);
    ClinicModule::create(['clinic_id' => $clinic->id, 'module_key' => 'controlled_drugs', 'is_active' => true]);

    toggleModule($admin, $clinic, 'inventory', false)->assertSessionHasErrors('module');
});

test('deactivating a module without active dependents succeeds', function () {
    $clinic = Clinic::factory()->create();
    $admin = User::factory()->create(['is_super_admin' => true]);

    ClinicModule::create(['clinic_id' => $clinic->id, 'module_key' => 'inventory', 'is_active' => true]);

    toggleModule($admin, $clinic, 'inventory', false)->assertRedirect();

    expect(ClinicModule::where('clinic_id', $clinic->id)->where('module_key', 'inventory')->where('is_active', false)->exists())->toBeTrue();
});

test('non-super-admin cannot toggle modules', function () {
    $clinic = Clinic::factory()->create();
    $user = User::factory()->create(['is_super_admin' => false]);

    toggleModule($user, $clinic, 'patients', true)->assertForbidden();
});
