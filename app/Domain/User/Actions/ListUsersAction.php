<?php

namespace App\Domain\User\Actions;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

class ListUsersAction
{
    public function handle(Request $request): LengthAwarePaginator
    {
        $search = $request->string('search')->trim()->toString();
        $clinicId = current_clinic()->id;

        return User::query()
            ->with(['branchRoles' => fn ($query) => $query->with('branch:id,name')])
            ->when($request->integer('branch_id') > 0, fn ($query) => $query->whereHas(
                'branchRoles',
                fn ($branchQuery) => $branchQuery->where('branch_id', $request->integer('branch_id'))
            ))
            ->when($request->filled('role'), fn ($query) => $query->role($request->string('role')->toString()))
            ->when($request->string('status')->toString() === 'active', fn ($query) => $query->active())
            ->when($request->string('status')->toString() === 'inactive', fn ($query) => $query->inactive())
            ->when($search !== '', fn ($query) => $query->where(fn ($subquery) => $subquery
                ->where('name', 'ilike', "%{$search}%")
                ->orWhere('email', 'ilike', "%{$search}%")
                ->orWhere('phone', 'ilike', "%{$search}%")
            ))
            ->orderByRaw(
                "CASE WHEN id IN (
                    SELECT ubr.user_id FROM user_branch_roles ubr
                    INNER JOIN roles r ON r.name = ubr.role AND r.team_id = ?
                    WHERE ubr.clinic_id = ? AND ubr.role = 'clinic_admin' AND ubr.deleted_at IS NULL
                ) THEN 0 ELSE 1 END",
                [$clinicId, $clinicId]
            )
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();
    }
}
