<?php

use App\Domain\Clinic\Models\Clinic;
use App\Domain\Clinic\Models\ClinicRoleModule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('superadmin can configure role modules for a clinic', function () {
    $superAdmin = User::factory()->create(['is_super_admin' => true]);
    $clinic = Clinic::factory()->create();

    $this->actingAs($superAdmin)
        ->put(route('admin.clinics.role-modules.update', $clinic), [
            'role' => 'veterinarian',
            'enabled_modules' => ['patients', 'appointments'],
        ])
        ->assertRedirect();

    expect(ClinicRoleModule::where('clinic_id', $clinic->id)
        ->where('role', 'veterinarian')
        ->where('module_key', 'patients')
        ->value('is_enabled'))->toBeTrue();

    expect(ClinicRoleModule::where('clinic_id', $clinic->id)
        ->where('role', 'veterinarian')
        ->where('module_key', 'inventory')
        ->value('is_enabled'))->toBeFalse();
});
