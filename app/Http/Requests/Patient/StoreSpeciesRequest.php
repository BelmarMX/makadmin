<?php

namespace App\Http\Requests\Patient;

use App\Domain\Patient\Permissions;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSpeciesRequest extends FormRequest
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
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('species', 'name')->where(function ($query): void {
                    $query
                        ->where('clinic_id', current_clinic()->id)
                        ->whereNull('deleted_at');
                }),
            ],
        ];
    }
}
