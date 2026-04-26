<?php

namespace App\Http\Requests\Patient;

use App\Domain\Patient\Permissions;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMunicipalityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(Permissions::CLIENTS_CREATE) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'state_id' => ['required', 'integer', 'exists:states,id'],
            'name' => [
                'required',
                'string',
                'max:150',
                Rule::unique('municipalities', 'name')->where(function ($query): void {
                    $query
                        ->where('state_id', (int) $this->input('state_id'))
                        ->where('clinic_id', current_clinic()->id);
                }),
            ],
        ];
    }
}
