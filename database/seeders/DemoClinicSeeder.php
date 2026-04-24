<?php

namespace Database\Seeders;

use App\Domain\Clinic\Models\Clinic;
use App\Domain\Clinic\Models\ClinicBranch;
use App\Domain\Clinic\Models\ClinicModule;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class DemoClinicSeeder extends Seeder
{
    public function run(): void
    {
        $clinic = Clinic::firstOrCreate(
            ['slug' => 'demo'],
            [
                'legal_name' => 'Clínica Demo S.A. de C.V.',
                'commercial_name' => 'VetDemo',
                'responsible_vet_name' => 'Dr. Demo',
                'responsible_vet_license' => '0000000',
                'contact_phone' => '5500000000',
                'contact_email' => 'demo@makadmin.com',
                'is_active' => true,
                'activated_at' => now(),
            ]
        );

        ClinicBranch::firstOrCreate(
            ['clinic_id' => $clinic->id, 'name' => 'Sucursal Principal'],
            [
                'address' => 'Calle Demo 123, Ciudad de México',
                'is_main' => true,
                'is_active' => true,
            ]
        );

        $modules = ['inventory', 'pos', 'grooming', 'hospitalization', 'appointments'];
        foreach ($modules as $module) {
            ClinicModule::firstOrCreate(
                ['clinic_id' => $clinic->id, 'module_key' => $module],
                ['is_active' => true, 'activated_at' => now()]
            );
        }

        $adminRole = Role::firstOrCreate(['name' => 'clinic_admin', 'guard_name' => 'web']);

        $admin = User::firstOrCreate(
            ['email' => 'admin@demo.makadmin.com'],
            [
                'name' => 'Admin Demo',
                'password' => 'demo-password',
                'clinic_id' => $clinic->id,
                'is_super_admin' => false,
                'email_verified_at' => now(),
            ]
        );

        app()[PermissionRegistrar::class]->setPermissionsTeamId($clinic->id);
        $admin->assignRole($adminRole);
    }
}
