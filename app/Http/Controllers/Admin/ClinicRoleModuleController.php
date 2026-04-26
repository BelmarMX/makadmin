<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Clinic\Actions\SyncClinicRoleModulesAction;
use App\Domain\Clinic\Enums\ModuleKey;
use App\Domain\Clinic\Models\Clinic;
use App\Domain\Clinic\Models\ClinicRoleModule;
use App\Domain\User\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SyncClinicRoleModulesRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ClinicRoleModuleController extends Controller
{
    public function show(Request $request, Clinic $clinic, string $role): Response
    {
        abort_unless((bool) $request->user()?->is_super_admin, 403);
        UserRole::from($role);

        $enabledModules = ClinicRoleModule::enabledModulesForRole($clinic->id, $role);

        $modules = collect(ModuleKey::cases())->map(fn (ModuleKey $module) => [
            'key' => $module->value,
            'label' => $module->label(),
            'description' => $module->description(),
            'icon' => $module->icon(),
            'is_enabled' => in_array($module->value, $enabledModules, true),
        ]);

        return Inertia::render('Admin/Clinics/RoleModules', [
            'clinic' => $clinic->only('id', 'commercial_name'),
            'role' => $role,
            'roleLabel' => UserRole::from($role)->label(),
            'modules' => $modules,
        ]);
    }

    public function update(SyncClinicRoleModulesRequest $request, Clinic $clinic, SyncClinicRoleModulesAction $action): RedirectResponse
    {
        $action->handle($clinic, $request->validated('role'), $request->validated('enabled_modules'));

        return back()->with('success', 'Configuración de módulos actualizada.');
    }
}
