<?php

namespace App\Http\Requests\Patient;

use Illuminate\Validation\Rule;

class StoreQuickPatientRequest extends StorePatientRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'client_id' => [
                'required',
                'integer',
                Rule::exists('clients', 'id')
                    ->where('clinic_id', current_clinic()->id)
                    ->whereNull('deleted_at'),
            ],
            ...parent::rules(),
        ];
    }
}
