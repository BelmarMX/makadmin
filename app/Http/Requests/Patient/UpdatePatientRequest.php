<?php

namespace App\Http\Requests\Patient;

use App\Domain\Patient\Enums\PatientSex;
use App\Domain\Patient\Enums\PatientSize;
use App\Domain\Patient\Models\Patient;
use App\Domain\Patient\Permissions;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;

class UpdatePatientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(Permissions::PATIENTS_UPDATE) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Patient $patient */
        $routePatient = $this->route('patient');
        $patientId = $routePatient instanceof Patient ? $routePatient->id : (int) $routePatient;

        return [
            'name' => ['required', 'string', 'max:100'],
            'sex' => ['required', Rule::enum(PatientSex::class)],
            'species_id' => [$this->catalogExistsRule('species')],
            'breed_id' => [$this->catalogExistsRule('breeds')],
            'temperament_id' => [$this->catalogExistsRule('temperaments')],
            'coat_color_id' => [$this->catalogExistsRule('pelage_colors')],
            'birth_date' => ['nullable', 'date', 'before_or_equal:today'],
            'microchip' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('patients', 'microchip')
                    ->where('clinic_id', current_clinic()->id)
                    ->whereNull('deleted_at')
                    ->ignore($patientId),
            ],
            'size' => ['nullable', Rule::enum(PatientSize::class)],
            'weight_kg' => ['nullable', 'numeric', 'min:0.01', 'max:999.99'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'is_sterilized' => ['nullable', 'boolean'],
            'is_deceased' => ['nullable', 'boolean'],
            'deceased_at' => ['nullable', 'date', 'after_or_equal:birth_date', 'before_or_equal:today'],
            'photo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:4096'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'microchip' => $this->filled('microchip') ? trim((string) $this->input('microchip')) : null,
            'is_sterilized' => $this->boolean('is_sterilized'),
            'is_deceased' => $this->boolean('is_deceased'),
        ]);
    }

    private function catalogExistsRule(string $table): Exists
    {
        return Rule::exists($table, 'id')
            ->where(function ($query): void {
                $query
                    ->where('is_active', true)
                    ->whereNull('deleted_at')
                    ->where(fn ($scope) => $scope
                        ->whereNull('clinic_id')
                        ->orWhere('clinic_id', current_clinic()->id));
            });
    }
}
