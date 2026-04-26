<?php

namespace App\Http\Requests\Patient;

use App\Domain\Patient\Permissions;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePelageColorRequest extends FormRequest
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
                'max:150',
                Rule::unique('pelage_colors', 'name')->where(function ($query): void {
                    $query
                        ->where('clinic_id', current_clinic()->id)
                        ->whereNull('deleted_at');
                }),
            ],
            'hex' => ['nullable', 'regex:/^#?[0-9A-Fa-f]{6}$/'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $hex = $this->input('hex');

        $this->merge([
            'hex' => is_string($hex) && $hex !== ''
                ? '#'.ltrim($hex, '#')
                : null,
        ]);
    }
}
