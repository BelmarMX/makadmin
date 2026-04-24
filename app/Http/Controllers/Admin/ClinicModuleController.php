<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Clinic\Actions\ToggleClinicModuleAction;
use App\Domain\Clinic\Enums\ModuleKey;
use App\Domain\Clinic\Models\Clinic;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ClinicModuleController extends Controller
{
    public function toggle(Request $request, Clinic $clinic, string $module, ToggleClinicModuleAction $action): RedirectResponse
    {
        $this->authorize('manageModules', $clinic);

        $moduleKey = ModuleKey::from($module);
        $activate = (bool) $request->boolean('activate');

        $action->handle($clinic, $moduleKey, $activate);

        $label = $moduleKey->label();
        $status = $activate ? 'activado' : 'desactivado';

        return back()->with('success', "Módulo «{$label}» {$status}.");
    }
}
