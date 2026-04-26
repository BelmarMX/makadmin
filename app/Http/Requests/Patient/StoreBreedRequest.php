<?php

namespace App\Http\Requests\Patient;

use App\Domain\Patient\Permissions;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBreedRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(Permissions::PATIENTS_CREATE) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'species_id' => ['required', 'integer', 'exists:species,id'],
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('breeds', 'name')->where(function ($query): void {
                    $query
                        ->where('species_id', (int) $this->input('species_id'))
                        ->where('clinic_id', current_clinic()->id)
                        ->whereNull('deleted_at');
                }),
            ],
        ];
    }
}
