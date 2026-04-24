<?php

use App\Domain\Clinic\Models\Clinic;
use App\Domain\Clinic\Models\ClinicBranch;
use App\Domain\Clinic\Models\ClinicModule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    config(['branding.apex_domain' => 'vetfollow.test']);
    Notification::fake();
});

function superAdmin(): User
{
    return User::factory()->create(['is_super_admin' => true]);
}

function validClinicPayload(array $overrides = []): array
{
    return array_merge([
        'slug' => 'testclinic',
        'commercial_name' => 'Test Clinic',
        'legal_name' => 'Test Clinic SA de CV',
        'contact_email' => 'admin@testclinic.com',
        'contact_phone' => '5512345678',
        'responsible_vet_name' => 'Dr. Test',
        'responsible_vet_license' => '12345678',
        'primary_color' => '#3b82f6',
        'main_branch' => ['name' => 'Sucursal Principal', 'address' => 'Av. Test 123, CDMX'],
        'modules' => ['patients'],
        'admin' => ['name' => 'Admin Test', 'email' => 'newadmin@testclinic.com'],
    ], $overrides);
}

test('super admin can create a clinic', function () {
    $this->actingAs(superAdmin())
        ->post(route('admin.clinics.store'), validClinicPayload())
        ->assertRedirect();

    expect(Clinic::withoutGlobalScopes()->where('slug', 'testclinic')->exists())->toBeTrue();
});

test('creates main branch automatically', function () {
    $this->actingAs(superAdmin())
        ->post(route('admin.clinics.store'), validClinicPayload());

    $clinic = Clinic::withoutGlobalScopes()->where('slug', 'testclinic')->first();

    expect(ClinicBranch::withoutGlobalScopes()->where('clinic_id', $clinic->id)->where('is_main', true)->exists())->toBeTrue();
});

test('activates selected modules', function () {
    $this->actingAs(superAdmin())
        ->post(route('admin.clinics.store'), validClinicPayload(['modules' => ['patients', 'appointments']]));

    $clinic = Clinic::withoutGlobalScopes()->where('slug', 'testclinic')->first();

    expect(ClinicModule::where('clinic_id', $clinic->id)->where('is_active', true)->count())->toBe(2);
});

test('creates clinic admin user', function () {
    $this->actingAs(superAdmin())
        ->post(route('admin.clinics.store'), validClinicPayload());

    expect(User::where('email', 'newadmin@testclinic.com')->exists())->toBeTrue();
});

test('clinic admin gets clinic_admin role via spatie teams', function () {
    Role::create(['name' => 'clinic_admin', 'guard_name' => 'web']);

    $this->actingAs(superAdmin())
        ->post(route('admin.clinics.store'), validClinicPayload());

    $clinic = Clinic::withoutGlobalScopes()->where('slug', 'testclinic')->first();
    $adminUser = User::where('email', 'newadmin@testclinic.com')->first();

    setPermissionsTeamId($clinic->id);

    expect($adminUser->fresh()->hasRole('clinic_admin'))->toBeTrue();
});

test('rejects reserved slugs', function (string $slug) {
    $this->actingAs(superAdmin())
        ->post(route('admin.clinics.store'), validClinicPayload(['slug' => $slug]))
        ->assertSessionHasErrors('slug');
})->with(['admin', 'www', 'api', 'app', 'portal']);

test('rejects duplicate slug', function () {
    Clinic::factory()->create(['slug' => 'existingclinic']);

    $this->actingAs(superAdmin())
        ->post(route('admin.clinics.store'), validClinicPayload(['slug' => 'existingclinic']))
        ->assertSessionHasErrors('slug');
});

test('rejects invalid RFC format', function () {
    $this->actingAs(superAdmin())
        ->post(route('admin.clinics.store'), validClinicPayload(['rfc' => 'INVALID']))
        ->assertSessionHasErrors('rfc');
});

test('modules with dependencies auto-activate dependencies', function () {
    $this->actingAs(superAdmin())
        ->post(route('admin.clinics.store'), validClinicPayload(['modules' => ['controlled_drugs']]));

    $clinic = Clinic::withoutGlobalScopes()->where('slug', 'testclinic')->first();

    // controlled_drugs depends on inventory — both should be active
    expect(ClinicModule::where('clinic_id', $clinic->id)->where('module_key', 'inventory')->where('is_active', true)->exists())->toBeTrue();
    expect(ClinicModule::where('clinic_id', $clinic->id)->where('module_key', 'controlled_drugs')->where('is_active', true)->exists())->toBeTrue();
});

test('non-super-admin cannot create clinic', function () {
    $user = User::factory()->create(['is_super_admin' => false]);

    $this->actingAs($user)
        ->post(route('admin.clinics.store'), validClinicPayload())
        ->assertForbidden();
});

test('guest cannot create clinic', function () {
    $this->post(route('admin.clinics.store'), validClinicPayload())
        ->assertRedirect(route('login'));
});
