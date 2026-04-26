<?php

namespace App\Http\Controllers\Clinic;

use App\Domain\User\Actions\SyncBranchPermissionsAction;
use App\Domain\User\Actions\SyncPermissionsAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Clinic\SyncBranchPermissionsRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

class UserPermissionController extends Controller
{
    public function update(
        SyncBranchPermissionsRequest $request,
        string $clinic,
        User $user,
        SyncPermissionsAction $globalAction,
        SyncBranchPermissionsAction $branchAction,
    ): RedirectResponse {
        $branchId = $request->integer('branch_id') ?: null;

        if ($branchId !== null) {
            $branchAction->handle($user, $branchId, $request->validated('permissions'));
        } else {
            $globalAction->handle($user, $request->validated('permissions'));
        }

        return back()->with('success', 'Permisos actualizados.');
    }
}
