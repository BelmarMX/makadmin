<?php

namespace App\Http\Controllers\Clinic;

use App\Domain\Catalog\Veterinary\Models\Breed;
use App\Domain\Catalog\Veterinary\Models\PelageColor;
use App\Domain\Catalog\Veterinary\Models\Species;
use App\Domain\Catalog\Veterinary\Models\Temperament;
use App\Domain\Patient\Actions\CreatePatientAction;
use App\Domain\Patient\Actions\DeactivatePatientAction;
use App\Domain\Patient\Actions\RestorePatientAction;
use App\Domain\Patient\Actions\UpdatePatientAction;
use App\Domain\Patient\Actions\UploadPatientPhotoAction;
use App\Domain\Patient\DataTransferObjects\PatientData;
use App\Domain\Patient\Enums\PatientSex;
use App\Domain\Patient\Enums\PatientSize;
use App\Domain\Patient\Models\Client;
use App\Domain\Patient\Models\Patient;
use App\Http\Controllers\Controller;
use App\Http\Requests\Patient\StorePatientRequest;
use App\Http\Requests\Patient\StoreQuickPatientRequest;
use App\Http\Requests\Patient\UpdatePatientRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class PatientController extends Controller
{
    public function create(string $clinic, Client $client): Response
    {
        $this->authorize('create', Patient::class);

        return Inertia::render('Clinic/Patients/Create', [
            'client' => $client->only('id', 'name'),
            ...$this->formProps(),
        ]);
    }

    public function quickCreate(string $clinic): Response
    {
        $this->authorize('create', Patient::class);

        return Inertia::render('Clinic/Patients/QuickCreate', [
            'client' => null,
            ...$this->formProps(),
        ]);
    }

    public function store(
        StorePatientRequest $request,
        string $clinic,
        Client $client,
        CreatePatientAction $action,
        UploadPatientPhotoAction $uploader,
    ): RedirectResponse {
        $patient = $action->handle(PatientData::fromRequest($request), $client, $uploader);

        return redirect()
            ->route('clinic.patients.show', ['clinic' => $clinic, 'patient' => $patient])
            ->with('success', "{$patient->name} registrado.");
    }

    public function quickStore(
        StoreQuickPatientRequest $request,
        string $clinic,
        CreatePatientAction $action,
        UploadPatientPhotoAction $uploader,
    ): RedirectResponse {
        $client = Client::query()->findOrFail((int) $request->validated('client_id'));
        $patient = $action->handle(PatientData::fromRequest($request), $client, $uploader);

        return redirect()
            ->route('clinic.patients.show', ['clinic' => $clinic, 'patient' => $patient])
            ->with('success', "{$patient->name} registrado.");
    }

    public function show(string $clinic, int $patient): Response
    {
        $patientModel = Patient::withTrashed()->findOrFail($patient);
        $this->authorize('view', $patientModel);

        $patientModel->load([
            'client:id,name,email,phone',
            'species:id,name',
            'breed:id,name',
            'temperament:id,name',
            'coatColor:id,name,hex',
        ]);

        return Inertia::render('Clinic/Patients/Show', [
            'patient' => $patientModel,
        ]);
    }

    public function edit(string $clinic, int $patient): Response
    {
        $patientModel = Patient::withTrashed()->findOrFail($patient);
        $this->authorize('update', $patientModel);

        $patientModel->load(['client:id,name']);

        return Inertia::render('Clinic/Patients/Edit', [
            'patient' => $patientModel,
            ...$this->formProps(),
        ]);
    }

    public function update(
        UpdatePatientRequest $request,
        string $clinic,
        int $patient,
        UpdatePatientAction $action,
        UploadPatientPhotoAction $uploader,
    ): RedirectResponse {
        $patientModel = Patient::withTrashed()->findOrFail($patient);
        $this->authorize('update', $patientModel);

        $updated = $action->handle($patientModel, PatientData::fromRequest($request), $uploader);

        return redirect()
            ->route('clinic.patients.show', ['clinic' => $clinic, 'patient' => $updated])
            ->with('success', 'Datos del paciente actualizados.');
    }

    public function deactivate(string $clinic, int $patient, DeactivatePatientAction $action): RedirectResponse
    {
        $patientModel = Patient::withTrashed()->findOrFail($patient);
        $this->authorize('deactivate', $patientModel);

        $action->handle($patientModel);

        return back()->with('success', 'Paciente desactivado.');
    }

    public function restore(string $clinic, int $patient, RestorePatientAction $action): RedirectResponse
    {
        $restorable = Patient::withTrashed()->findOrFail($patient);
        $this->authorize('restore', $restorable);

        $action->handle($restorable);

        return back()->with('success', 'Paciente reactivado.');
    }

    /** @return array<string, mixed> */
    private function formProps(): array
    {
        return [
            'species' => Species::query()->where('is_active', true)->orderBy('sort_order')->orderBy('name')->get(['id', 'name']),
            'breeds' => Breed::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'species_id']),
            'temperaments' => Temperament::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'coatColors' => PelageColor::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'hex']),
            'sexOptions' => PatientSex::options(),
            'sizeOptions' => PatientSize::options(),
        ];
    }
}
