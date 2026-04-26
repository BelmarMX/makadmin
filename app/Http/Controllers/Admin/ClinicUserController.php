<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Clinic\Models\Clinic;
use App\Domain\User\Actions\DeactivateUserAction;
use App\Domain\User\Actions\RestoreUserAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateClinicUserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ClinicUserController extends Controller
{
    public function update(UpdateClinicUserRequest $request, Clinic $clinic, User $user): RedirectResponse
    {
        abort_unless($user->clinic_id === $clinic->id, 403);

        $user->update($request->only('name', 'email', 'phone'));

        return back()->with('success', "Usuario {$user->name} actualizado.");
    }

    public function activate(Request $request, Clinic $clinic, User $user, RestoreUserAction $action): RedirectResponse
    {
        abort_unless((bool) $request->user()?->is_super_admin, 403);
        abort_unless($user->clinic_id === $clinic->id, 403);

        $action->handle($user);

        return back()->with('success', "{$user->name} activado.");
    }

    public function deactivate(Request $request, Clinic $clinic, User $user, DeactivateUserAction $action): RedirectResponse
    {
        abort_unless((bool) $request->user()?->is_super_admin, 403);
        abort_unless($user->clinic_id === $clinic->id, 403);

        $action->handle($user);

        return back()->with('success', "{$user->name} suspendido.");
    }

    public function destroy(Request $request, Clinic $clinic, User $user): RedirectResponse
    {
        abort_unless((bool) $request->user()?->is_super_admin, 403);
        abort_unless($user->clinic_id === $clinic->id, 403);
        abort_if($user->is_super_admin, 403);

        $user->delete();

        return back()->with('success', "{$user->name} eliminado.");
    }
}
