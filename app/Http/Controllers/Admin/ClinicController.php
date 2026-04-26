<?php

namespace App\Http\Controllers\Admin;

use App\Contracts\Integrations\MediaStorage;
use App\Domain\Clinic\Actions\ActivateClinicAction;
use App\Domain\Clinic\Actions\CreateClinicAction;
use App\Domain\Clinic\Actions\DeactivateClinicAction;
use App\Domain\Clinic\Actions\UpdateClinicAction;
use App\Domain\Clinic\Actions\UploadClinicLogoAction;
use App\Domain\Clinic\DataTransferObjects\ClinicData;
use App\Domain\Clinic\Enums\FiscalRegime;
use App\Domain\Clinic\Enums\ModuleKey;
use App\Domain\Clinic\Models\Clinic;
use App\Domain\Clinic\Models\ClinicRoleModule;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreClinicRequest;
use App\Http\Requests\Admin\UpdateClinicRequest;
use App\Http\Requests\UploadImageRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ClinicController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Clinic::class);

        $search = $request->string('search')->trim()->toString();

        $clinics = Clinic::withTrashed()
            ->with(['branches' => fn ($q) => $q->where('is_main', true), 'modules'])
            ->when($search !== '', fn ($q) => $q->where(fn ($q) => $q
                ->where('commercial_name', 'ilike', "%{$search}%")
                ->orWhere('legal_name', 'ilike', "%{$search}%")
                ->orWhere('contact_email', 'ilike', "%{$search}%")
                ->orWhere('contact_phone', 'ilike', "%{$search}%")
                ->orWhere('slug', 'ilike', "%{$search}%")
            ))
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Admin/Clinics/Index', [
            'clinics' => $clinics,
            'filters' => ['search' => $search],
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

        $clinic->load([
            'branches',
            'modules',
            'users' => fn ($query) => $query
                ->with('roles:id,name')
                ->select(['id', 'clinic_id', 'name', 'email', 'phone', 'is_active', 'email_verified_at']),
        ]);
        $roleModuleConfig = ClinicRoleModule::where('clinic_id', $clinic->id)
            ->get(['role', 'module_key', 'is_enabled'])
            ->toArray();

        return Inertia::render('Admin/Clinics/Show', [
            'clinic' => array_merge($clinic->append('subdomain_url')->toArray(), [
                'roleModuleConfig' => $roleModuleConfig,
            ]),
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

    public function uploadLogo(UploadImageRequest $request, Clinic $clinic, UploadClinicLogoAction $action): RedirectResponse
    {
        $this->authorize('update', $clinic);
        $action->handle($clinic, $request->file('image'));

        return back()->with('success', 'Logo actualizado.');
    }

    public function destroyLogo(Clinic $clinic): RedirectResponse
    {
        $this->authorize('update', $clinic);

        if ($clinic->logo_path) {
            app(MediaStorage::class)->delete($clinic->logo_path);
            $clinic->logo_path = null;
            $clinic->save();
        }

        return back()->with('success', 'Logo eliminado.');
    }
}
