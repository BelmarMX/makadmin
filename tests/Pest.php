<?php

use App\Domain\Clinic\Models\Clinic;
use App\Domain\Clinic\Models\ClinicBranch;
use App\Domain\Clinic\Models\ClinicModule;
use App\Domain\Patient\Permissions as PatientPermissions;
use App\Domain\User\Permissions;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Unit/User');

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Unit/Patient');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}

function task03ClinicContext(): array
{
    $clinic = Clinic::factory()->create(['slug' => str(fake()->unique()->words(2, true))->slug()->toString()]);
    $branch = ClinicBranch::withoutGlobalScopes()->create([
        'clinic_id' => $clinic->id,
        'name' => 'Sucursal Principal',
        'address' => 'Av. Prueba 123',
        'is_main' => true,
        'is_active' => true,
    ]);

    ClinicModule::withoutGlobalScopes()->create([
        'clinic_id' => $clinic->id,
        'module_key' => 'patients',
        'is_active' => true,
        'activated_at' => now(),
    ]);

    app()->instance('current.clinic', $clinic);
    setPermissionsTeamId($clinic->id);

    return [$clinic, $branch];
}

function task03ClinicAdmin(Clinic $clinic, ClinicBranch $branch): User
{
    foreach ([...Permissions::all(), ...PatientPermissions::all()] as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $role = Role::findOrCreate('clinic_admin', 'web');
    $role->syncPermissions([...Permissions::all(), ...PatientPermissions::all()]);

    $user = User::factory()->create([
        'clinic_id' => $clinic->id,
        'branch_id' => $branch->id,
        'is_active' => true,
    ]);

    setPermissionsTeamId($clinic->id);
    $user->assignRole($role);

    return $user;
}

function task03ClinicRoute(string $name, Clinic $clinic, array $parameters = []): string
{
    return route($name, ['clinic' => $clinic->slug, ...$parameters]);
}
