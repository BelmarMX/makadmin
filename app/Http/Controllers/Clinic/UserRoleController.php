<?php

namespace App\Http\Controllers\Clinic;

use App\Domain\User\Actions\AssignRoleAction;
use App\Domain\User\Actions\RevokeRoleAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Clinic\AssignUserRoleRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

class UserRoleController extends Controller
{
    public function store(AssignUserRoleRequest $request, string $clinic, User $user, AssignRoleAction $action): RedirectResponse
    {
        $action->handle($user, $request->validated('role'));

        return back()->with('success', 'Rol asignado.');
    }

    public function destroy(AssignUserRoleRequest $request, string $clinic, User $user, RevokeRoleAction $action): RedirectResponse
    {
        $action->handle($user, $request->validated('role'));

        return back()->with('success', 'Rol revocado.');
    }
}
