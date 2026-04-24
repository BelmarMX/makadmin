<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Clinic\Actions\InviteClinicAdminAction;
use App\Domain\Clinic\DataTransferObjects\ClinicAdminInvitationData;
use App\Domain\Clinic\Models\Clinic;
use App\Domain\Clinic\Models\ClinicBranch;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ClinicAdminController extends Controller
{
    public function invite(Request $request, Clinic $clinic, InviteClinicAdminAction $action): RedirectResponse
    {
        $this->authorize('update', $clinic);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:200'],
            'email' => ['required', 'email', Rule::unique('users', 'email')],
            'phone' => ['nullable', 'string', 'max:20'],
        ]);

        $mainBranch = ClinicBranch::withoutGlobalScopes()
            ->where('clinic_id', $clinic->id)
            ->where('is_main', true)
            ->firstOrFail();

        $action->handle(
            new ClinicAdminInvitationData($validated['name'], $validated['email'], $validated['phone'] ?? null),
            $clinic,
            $mainBranch,
        );

        return back()->with('success', "Invitación enviada a {$validated['email']}.");
    }
}
