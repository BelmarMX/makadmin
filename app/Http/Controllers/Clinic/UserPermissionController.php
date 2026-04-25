<?php

namespace App\Http\Controllers\Clinic;

use App\Domain\User\Actions\SyncPermissionsAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Clinic\SyncUserPermissionsRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

class UserPermissionController extends Controller
{
    public function update(SyncUserPermissionsRequest $request, string $clinic, User $user, SyncPermissionsAction $action): RedirectResponse
    {
        $action->handle($user, $request->validated('permissions'));

        return back()->with('success', 'Permisos actualizados.');
    }
}
