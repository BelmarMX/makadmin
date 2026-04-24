<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Clinic\Actions\ActivateClinicAction;
use App\Domain\Clinic\Actions\CreateClinicAction;
use App\Domain\Clinic\Actions\DeactivateClinicAction;
use App\Domain\Clinic\Actions\UpdateClinicAction;
use App\Domain\Clinic\DataTransferObjects\ClinicData;
use App\Domain\Clinic\Enums\FiscalRegime;
use App\Domain\Clinic\Enums\ModuleKey;
use App\Domain\Clinic\Models\Clinic;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreClinicRequest;
use App\Http\Requests\Admin\UpdateClinicRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ClinicController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', Clinic::class);

        $clinics = Clinic::withTrashed()
            ->with(['branches' => fn ($q) => $q->where('is_main', true), 'modules'])
            ->orderByDesc('created_at')
            ->paginate(20);

        return Inertia::render('Admin/Clinics/Index', [
            'clinics' => $clinics,
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Clinic::class);

        return Inertia::render('Admin/Clinics/Create', [
            'modules' => collect(ModuleKey::cases())->map(fn (ModuleKey $m) => [
                'key' => $m->value,
                'label' => $m->label(),
                'description' => $m->description(),
                'icon' => $m->icon(),
                'dependsOn' => array_map(fn (ModuleKey $d) => $d->value, $m->dependsOn()),
            ]),
            'fiscalRegimes' => collect(FiscalRegime::cases())->map(fn (FiscalRegime $r) => [
                'value' => $r->value,
                'label' => $r->label(),
            ]),
        ]);
    }

    public function store(StoreClinicRequest $request, CreateClinicAction $action): RedirectResponse
    {
        $clinic = $action->handle(ClinicData::fromRequest($request));

        return redirect()
            ->route('admin.clinics.show', $clinic)
            ->with('success', "Clínica «{$clinic->commercial_name}» creada. Invitación enviada a {$clinic->contact_email}.");
    }

    public function show(Clinic $clinic): Response
    {
        $this->authorize('view', $clinic);

        $clinic->load(['branches', 'modules', 'users']);

        return Inertia::render('Admin/Clinics/Show', [
            'clinic' => $clinic->append('subdomain_url'),
            'modules' => collect(ModuleKey::cases())->map(fn (ModuleKey $m) => [
                'key' => $m->value,
                'label' => $m->label(),
                'description' => $m->description(),
                'icon' => $m->icon(),
                'dependsOn' => array_map(fn (ModuleKey $d) => $d->value, $m->dependsOn()),
                'active' => $clinic->modules->where('module_key', $m->value)->where('is_active', true)->isNotEmpty(),
            ]),
        ]);
    }

    public function edit(Clinic $clinic): Response
    {
        $this->authorize('update', $clinic);

        return Inertia::render('Admin/Clinics/Edit', [
            'clinic' => $clinic,
            'fiscalRegimes' => collect(FiscalRegime::cases())->map(fn (FiscalRegime $r) => [
                'value' => $r->value,
                'label' => $r->label(),
            ]),
        ]);
    }

    public function update(UpdateClinicRequest $request, Clinic $clinic, UpdateClinicAction $action): RedirectResponse
    {
        $action->handle($clinic, ClinicData::fromUpdateRequest($request));

        return redirect()
            ->route('admin.clinics.show', $clinic)
            ->with('success', 'Datos de la clínica actualizados.');
    }

    public function destroy(Clinic $clinic): RedirectResponse
    {
        $this->authorize('delete', $clinic);

        $clinic->delete();

        return redirect()
            ->route('admin.clinics.index')
            ->with('success', "Clínica «{$clinic->commercial_name}» archivada.");
    }

    public function activate(Clinic $clinic, ActivateClinicAction $action): RedirectResponse
    {
        $this->authorize('update', $clinic);

        $action->handle($clinic);

        return back()->with('success', "Clínica «{$clinic->commercial_name}» activada.");
    }

    public function deactivate(Clinic $clinic, DeactivateClinicAction $action): RedirectResponse
    {
        $this->authorize('update', $clinic);

        $action->handle($clinic);

        return back()->with('success', "Clínica «{$clinic->commercial_name}» desactivada.");
    }
}
