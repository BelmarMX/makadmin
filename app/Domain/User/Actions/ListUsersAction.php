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

        return User::query()
            ->with(['branch:id,name', 'roles:id,name'])
            ->when($request->integer('branch_id') > 0, fn ($query) => $query->where('branch_id', $request->integer('branch_id')))
            ->when($request->filled('role'), fn ($query) => $query->role($request->string('role')->toString()))
            ->when($request->string('status')->toString() === 'active', fn ($query) => $query->active())
            ->when($request->string('status')->toString() === 'inactive', fn ($query) => $query->inactive())
            ->when($search !== '', fn ($query) => $query->where(fn ($subquery) => $subquery
                ->where('name', 'ilike', "%{$search}%")
                ->orWhere('email', 'ilike', "%{$search}%")
                ->orWhere('phone', 'ilike', "%{$search}%")
            ))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();
    }
}
