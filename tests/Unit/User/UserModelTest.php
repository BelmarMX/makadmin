<?php

use App\Domain\Clinic\Models\ClinicBranch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

test('user model scopes and role helpers work', function () {
    [$clinic, $branch] = task03ClinicContext();
    $active = User::factory()->create(['clinic_id' => $clinic->id, 'branch_id' => $branch->id, 'is_active' => true]);
    $inactive = User::factory()->create(['clinic_id' => $clinic->id, 'branch_id' => $branch->id, 'is_active' => false]);
    Role::findOrCreate('veterinarian', 'web');

    setPermissionsTeamId($clinic->id);
    $active->assignRole('veterinarian');

    expect(User::active()->pluck('id'))->toContain($active->id)->not->toContain($inactive->id);
    expect(User::inactive()->pluck('id'))->toContain($inactive->id)->not->toContain($active->id);
    expect(User::byBranch(ClinicBranch::find($branch->id))->pluck('id'))->toContain($active->id);
    expect(User::byRole('veterinarian')->pluck('id'))->toContain($active->id);
    expect($active->fresh()->isVeterinarian())->toBeTrue();
});
