<?php

namespace App\Domain\Patient\Actions;

use App\Domain\Patient\Models\Client;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

class ListClientsAction
{
    public function handle(Request $request): LengthAwarePaginator
    {
        $search = $request->string('search')->trim()->toString();
        $status = $request->string('status')->toString();

        return Client::withTrashed()
            ->withCount(['patients' => fn ($query) => $query->where('is_active', true)])
            ->when($status === 'active', fn ($query) => $query->where('is_active', true)->whereNull('deleted_at'))
            ->when($status === 'inactive', fn ($query) => $query->where('is_active', false))
            ->when($search !== '', fn ($query) => $query->search($search))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();
    }
}
