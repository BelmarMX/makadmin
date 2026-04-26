<?php

namespace App\Http\Controllers\Clinic;

use App\Domain\Patient\Actions\CreateClientAction;
use App\Domain\Patient\Actions\DeactivateClientAction;
use App\Domain\Patient\Actions\ListClientsAction;
use App\Domain\Patient\Actions\RestoreClientAction;
use App\Domain\Patient\Actions\UpdateClientAction;
use App\Domain\Patient\DataTransferObjects\ClientData;
use App\Domain\Patient\Models\Client;
use App\Http\Controllers\Controller;
use App\Http\Requests\Patient\StoreClientRequest;
use App\Http\Requests\Patient\UpdateClientRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ClientController extends Controller
{
    public function index(Request $request, string $clinic, ListClientsAction $action): Response
    {
        $this->authorize('viewAny', Client::class);

        return Inertia::render('Clinic/Clients/Index', [
            'clients' => $action->handle($request),
            'filters' => [
                'search' => $request->string('search')->trim()->toString(),
                'status' => $request->string('status')->toString() ?: null,
            ],
        ]);
    }

    public function create(string $clinic): Response
    {
        $this->authorize('create', Client::class);

        return Inertia::render('Clinic/Clients/Create');
    }

    public function store(StoreClientRequest $request, string $clinic, CreateClientAction $action): RedirectResponse
    {
        $client = $action->handle(
            ClientData::fromRequest($request),
            $request->file('avatar'),
        );

        return redirect()
            ->route('clinic.clients.show', ['clinic' => $clinic, 'client' => $client])
            ->with('success', "Tutor {$client->name} registrado.");
    }

    public function show(string $clinic, int $client): Response
    {
        $clientModel = Client::withTrashed()->findOrFail($client);
        $this->authorize('view', $clientModel);

        $clientModel->load([
            'patients' => fn ($query) => $query
                ->with(['species:id,name', 'breed:id,name', 'coatColor:id,name', 'client:id,name'])
                ->withTrashed()
                ->orderBy('name'),
        ]);

        return Inertia::render('Clinic/Clients/Show', [
            'client' => $clientModel,
        ]);
    }

    public function edit(string $clinic, int $client): Response
    {
        $clientModel = Client::withTrashed()->findOrFail($client);
        $this->authorize('update', $clientModel);

        return Inertia::render('Clinic/Clients/Edit', [
            'client' => $clientModel,
        ]);
    }

    public function update(UpdateClientRequest $request, string $clinic, int $client, UpdateClientAction $action): RedirectResponse
    {
        $clientModel = Client::withTrashed()->findOrFail($client);
        $this->authorize('update', $clientModel);

        $updated = $action->handle(
            $clientModel,
            ClientData::fromRequest($request),
            $request->file('avatar'),
        );

        return redirect()
            ->route('clinic.clients.show', ['clinic' => $clinic, 'client' => $updated])
            ->with('success', 'Datos del tutor actualizados.');
    }

    public function deactivate(string $clinic, int $client, DeactivateClientAction $action): RedirectResponse
    {
        $clientModel = Client::withTrashed()->findOrFail($client);
        $this->authorize('deactivate', $clientModel);

        $action->handle($clientModel);

        return back()->with('success', 'Tutor desactivado.');
    }

    public function restore(string $clinic, int $client, RestoreClientAction $action): RedirectResponse
    {
        $restorable = Client::withTrashed()->findOrFail($client);
        $this->authorize('restore', $restorable);

        $action->handle($restorable);

        return back()->with('success', 'Tutor reactivado.');
    }
}
