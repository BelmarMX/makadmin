<?php

namespace App\Http\Controllers\Clinic;

use App\Domain\Clinic\Models\ClinicBranch;
use App\Domain\User\Actions\CreateUserAction;
use App\Domain\User\Actions\DeactivateUserAction;
use App\Domain\User\Actions\ListUsersAction;
use App\Domain\User\Actions\RestoreUserAction;
use App\Domain\User\Actions\UpdateUserAction;
use App\Domain\User\DataTransferObjects\UserData;
use App\Domain\User\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Clinic\StoreUserRequest;
use App\Http\Requests\Clinic\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(Request $request, string $clinic, ListUsersAction $action): Response
    {
        $this->authorize('viewAny', User::class);

        return Inertia::render('Clinic/Users/Index', $this->indexProps($request, $action));
    }

    public function create(string $clinic): Response
    {
        $this->authorize('create', User::class);

        return Inertia::render('Clinic/Users/Create', $this->formProps());
    }

    public function store(StoreUserRequest $request, string $clinic, CreateUserAction $action): RedirectResponse
    {
        $user = $action->handle(UserData::fromStoreRequest($request), $this->branch($request->integer('branch_id')));

        return redirect()->route('clinic.users.show', ['clinic' => $clinic, 'user' => $user])->with('success', "Usuario {$user->name} creado. Invitación enviada a {$user->email}.");
    }

    public function show(string $clinic, User $user): Response
    {
        $this->authorize('view', $user);

        return Inertia::render('Clinic/Users/Show', ['user' => $user->load(['branch', 'roles', 'permissions']), ...$this->formProps()]);
    }

    public function edit(string $clinic, User $user): Response
    {
        $this->authorize('update', $user);

        return Inertia::render('Clinic/Users/Edit', ['user' => $user->load(['branch', 'roles']), ...$this->formProps()]);
    }

    public function update(UpdateUserRequest $request, string $clinic, User $user, UpdateUserAction $action): RedirectResponse
    {
        $action->handle($user, UserData::fromUpdateRequest($request));

        return redirect()->route('clinic.users.show', ['clinic' => $clinic, 'user' => $user])->with('success', 'Usuario actualizado.');
    }

    public function deactivate(string $clinic, User $user, DeactivateUserAction $action): RedirectResponse
    {
        $this->authorize('deactivate', $user);
        $action->handle($user);

        return back()->with('success', 'Usuario desactivado.');
    }

    public function restore(string $clinic, User $user, RestoreUserAction $action): RedirectResponse
    {
        $this->authorize('restore', $user);
        $action->handle($user);

        return back()->with('success', 'Usuario activado.');
    }

    /** @return array<string, mixed> */
    private function indexProps(Request $request, ListUsersAction $action): array
    {
        return ['users' => $action->handle($request), 'filters' => $this->filters($request), ...$this->formProps()];
    }

    /** @return array<string, mixed> */
    private function formProps(): array
    {
        return [
            'branches' => current_clinic()->branches()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'roles' => UserRole::options(),
            'modules' => current_clinic()->modules()->where('is_active', true)->orderBy('module_key')->get(['module_key']),
        ];
    }

    private function branch(int $id): ClinicBranch
    {
        return ClinicBranch::query()
            ->where('clinic_id', current_clinic()->id)
            ->where('is_active', true)
            ->findOrFail($id);
    }

    /** @return array{search: string, branch_id: int|null, role: string|null, status: string|null} */
    private function filters(Request $request): array
    {
        return [
            'search' => $request->string('search')->trim()->toString(),
            'branch_id' => $request->integer('branch_id') ?: null,
            'role' => $request->string('role')->toString() ?: null,
            'status' => $request->string('status')->toString() ?: null,
        ];
    }
}
