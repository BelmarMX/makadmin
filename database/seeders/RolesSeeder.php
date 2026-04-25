<?php

namespace Database\Seeders;

use App\Domain\Clinic\Enums\ModuleKey;
use App\Domain\User\Permissions as UserPermissions;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        foreach ($this->permissions() as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $roles = [
            'clinic_admin',
            'veterinarian',
            'groomer',
            'receptionist',
            'cashier',
        ];

        foreach ($roles as $role) {
            Role::findOrCreate($role, 'web');
        }

        Role::findOrCreate('clinic_admin', 'web')->syncPermissions($this->permissions());
        Role::findOrCreate('veterinarian', 'web')->syncPermissions(['users.view', 'patients.view', 'patients.create', 'appointments.view']);
        Role::findOrCreate('groomer', 'web')->syncPermissions(['users.view', 'grooming.view', 'grooming.update']);
        Role::findOrCreate('receptionist', 'web')->syncPermissions(['users.view', 'appointments.view', 'appointments.create', 'patients.view']);
        Role::findOrCreate('cashier', 'web')->syncPermissions(['users.view', 'pos.view', 'pos.create']);
    }

    /** @return list<string> */
    private function permissions(): array
    {
        $modulePermissions = collect(ModuleKey::cases())
            ->flatMap(fn (ModuleKey $module): array => [
                "{$module->value}.view",
                "{$module->value}.create",
                "{$module->value}.update",
                "{$module->value}.delete",
            ])
            ->all();

        return array_values(array_unique([...UserPermissions::all(), ...$modulePermissions]));
    }
}
