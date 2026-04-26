<?php

use App\Domain\Clinic\Models\Clinic;
use App\Domain\Clinic\Models\ClinicBranch;
use App\Http\Middleware\ResolveClinic;
use App\Models\User;
use App\Support\Tenancy\Scopes\ClinicScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

uses(RefreshDatabase::class);

test('blocks cross-clinic access at global scope', function () {
    $clinicA = Clinic::create([
        'slug' => 'clinic-a',
        'legal_name' => 'Clinic A SA',
        'commercial_name' => 'Clinic A',
        'responsible_vet_name' => 'Dr A',
        'responsible_vet_license' => '111',
        'contact_phone' => '5500000001',
        'contact_email' => 'a@test.com',
    ]);

    $clinicB = Clinic::create([
        'slug' => 'clinic-b',
        'legal_name' => 'Clinic B SA',
        'commercial_name' => 'Clinic B',
        'responsible_vet_name' => 'Dr B',
        'responsible_vet_license' => '222',
        'contact_phone' => '5500000002',
        'contact_email' => 'b@test.com',
    ]);

    $branchB = ClinicBranch::withoutGlobalScopes()->create([
        'clinic_id' => $clinicB->id,
        'name' => 'Branch B',
        'address' => 'Av. B 456',
    ]);

    app()->instance('current.clinic', $clinicA);

    expect(ClinicBranch::find($branchB->id))->toBeNull();
});

test('super admin can bypass tenancy explicitly', function () {
    $clinicA = Clinic::create([
        'slug' => 'clinic-sa-a',
        'legal_name' => 'Clinic SA',
        'commercial_name' => 'Clinic SA',
        'responsible_vet_name' => 'Dr SA',
        'responsible_vet_license' => '333',
        'contact_phone' => '5500000003',
        'contact_email' => 'sa@test.com',
    ]);

    $clinicB = Clinic::create([
        'slug' => 'clinic-sa-b',
        'legal_name' => 'Clinic SB',
        'commercial_name' => 'Clinic SB',
        'responsible_vet_name' => 'Dr SB',
        'responsible_vet_license' => '444',
        'contact_phone' => '5500000004',
        'contact_email' => 'sb@test.com',
    ]);

    $branchB = ClinicBranch::withoutGlobalScopes()->create([
        'clinic_id' => $clinicB->id,
        'name' => 'Branch SB',
        'address' => 'Av. SB 789',
    ]);

    $superAdmin = User::factory()->create(['is_super_admin' => true]);
    $this->actingAs($superAdmin);

    app()->instance('current.clinic', $clinicA);

    $found = ClinicBranch::withoutGlobalScope(ClinicScope::class)->find($branchB->id);

    expect($found)->not->toBeNull();
    expect($found->id)->toBe($branchB->id);
});

test('non-super admin throws when calling withoutTenancy', function () {
    $clinic = Clinic::create([
        'slug' => 'clinic-throw',
        'legal_name' => 'Clinic Throw',
        'commercial_name' => 'Clinic Throw',
        'responsible_vet_name' => 'Dr T',
        'responsible_vet_license' => '555',
        'contact_phone' => '5500000005',
        'contact_email' => 'throw@test.com',
    ]);

    $user = User::factory()->create(['is_super_admin' => false, 'clinic_id' => $clinic->id]);
    $this->actingAs($user);

    expect(fn () => ClinicBranch::withoutTenancy(fn ($q) => $q))->toThrow(RuntimeException::class);
});

test('subdomain resolves correctly via middleware', function () {
    $clinic = Clinic::create([
        'slug' => 'mivet',
        'legal_name' => 'Mi Vet SA',
        'commercial_name' => 'MiVet',
        'responsible_vet_name' => 'Dr MiVet',
        'responsible_vet_license' => '666',
        'contact_phone' => '5500000006',
        'contact_email' => 'mivet@test.com',
    ]);

    $request = Request::create('http://mivet.'.config('branding.apex_domain').'/');

    $middleware = app(ResolveClinic::class);
    $middleware->handle($request, function () {
        return response('ok');
    });

    expect(app()->bound('current.clinic'))->toBeTrue();
    expect(app('current.clinic')->id)->toBe($clinic->id);
});

test('unknown subdomain returns 404 via middleware', function () {
    $request = Request::create('http://unknown-xyz.'.config('branding.apex_domain').'/');

    $middleware = app(ResolveClinic::class);
    $middleware->handle($request, fn () => response('ok'));
})->throws(HttpException::class);

test('www subdomain returns 404 via middleware', function () {
    $request = Request::create('http://'.config('branding.public_subdomain').'.'.config('branding.apex_domain').'/');

    $middleware = app(ResolveClinic::class);
    $middleware->handle($request, fn () => response('ok'));
})->throws(HttpException::class);

test('authenticated user cannot access different clinic subdomain', function () {
    $apex = config('branding.apex_domain');

    $clinicA = Clinic::create([
        'slug' => 'cross-clinic-a',
        'legal_name' => 'Cross Clinic A SA',
        'commercial_name' => 'Cross Clinic A',
        'responsible_vet_name' => 'Dr A',
        'responsible_vet_license' => '777',
        'contact_phone' => '5500000007',
        'contact_email' => 'crossa@test.com',
    ]);

    Clinic::create([
        'slug' => 'cross-clinic-b',
        'legal_name' => 'Cross Clinic B SA',
        'commercial_name' => 'Cross Clinic B',
        'responsible_vet_name' => 'Dr B',
        'responsible_vet_license' => '888',
        'contact_phone' => '5500000008',
        'contact_email' => 'crossb@test.com',
    ]);

    $user = User::factory()->create([
        'is_super_admin' => false,
        'clinic_id' => $clinicA->id,
    ]);

    $response = $this->actingAs($user)
        ->get('http://cross-clinic-b.'.$apex.'/');

    $response->assertForbidden();
});

test('super admin can access any clinic subdomain', function () {
    $apex = 'makadmin.test';
    config(['branding.apex_domain' => $apex]);

    $clinicA = Clinic::create([
        'slug' => 'super-cross-a',
        'legal_name' => 'Super Cross A SA',
        'commercial_name' => 'Super Cross A',
        'responsible_vet_name' => 'Dr SA',
        'responsible_vet_license' => '999',
        'contact_phone' => '5500000009',
        'contact_email' => 'supera@test.com',
    ]);

    Clinic::create([
        'slug' => 'super-cross-b',
        'legal_name' => 'Super Cross B SA',
        'commercial_name' => 'Super Cross B',
        'responsible_vet_name' => 'Dr SB',
        'responsible_vet_license' => '000',
        'contact_phone' => '5500000010',
        'contact_email' => 'superb@test.com',
    ]);

    $superAdmin = User::factory()->create([
        'is_super_admin' => true,
        'clinic_id' => $clinicA->id,
    ]);

    $response = $this->actingAs($superAdmin)
        ->get('http://super-cross-b.'.$apex.'/');

    $response->assertOk();
});
