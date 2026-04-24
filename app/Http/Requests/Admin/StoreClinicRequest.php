<?php

namespace App\Http\Requests\Admin;

use App\Domain\Clinic\Enums\FiscalRegime;
use App\Domain\Clinic\Enums\ModuleKey;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreClinicRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->is_super_admin === true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'slug' => [
                'required', 'alpha_dash', 'lowercase', 'min:3', 'max:40',
                Rule::unique('clinics', 'slug'),
                Rule::notIn(['admin', 'www', 'api', 'app', 'portal', 'mail', 'ftp', 'static']),
            ],
            'legal_name' => ['required', 'string', 'max:200'],
            'commercial_name' => ['required', 'string', 'max:200'],
            'rfc' => ['nullable', 'string', 'min:12', 'max:13', 'regex:/^[A-ZÑ&]{3,4}\d{6}[A-Z\d]{2,3}$/i'],
            'fiscal_regime' => ['nullable', Rule::enum(FiscalRegime::class)],
            'tax_address' => ['nullable', 'string', 'max:500'],
            'responsible_vet_name' => ['required', 'string', 'max:200'],
            'responsible_vet_license' => ['required', 'string', 'max:50'],
            'contact_phone' => ['required', 'string', 'max:20'],
            'contact_email' => ['required', 'email', 'max:200'],
            'logo' => ['nullable', 'image', 'mimes:png,jpg,webp,svg', 'max:2048'],
            'primary_color' => ['nullable', 'regex:/^#[0-9a-f]{6}$/i'],
            'main_branch' => ['required', 'array'],
            'main_branch.name' => ['required', 'string', 'max:200'],
            'main_branch.address' => ['required', 'string', 'max:500'],
            'main_branch.phone' => ['nullable', 'string', 'max:20'],
            'modules' => ['required', 'array', 'min:1'],
            'modules.*' => [Rule::enum(ModuleKey::class)],
            'admin' => ['required', 'array'],
            'admin.name' => ['required', 'string', 'max:200'],
            'admin.email' => ['required', 'email', Rule::unique('users', 'email')],
            'admin.phone' => ['nullable', 'string', 'max:20'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('slug')) {
            $this->merge(['slug' => strtolower((string) $this->slug)]);
        }
    }
}
