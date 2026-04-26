<?php

namespace App\Http\Requests\Patient;

use App\Domain\Patient\Models\Client;
use App\Domain\Patient\Permissions;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(Permissions::CLIENTS_UPDATE) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Client $client */
        $routeClient = $this->route('client');
        $clientId = $routeClient instanceof Client ? $routeClient->id : (int) $routeClient;

        return [
            'name' => ['required', 'string', 'max:200'],
            'email' => [
                'nullable',
                'email',
                'max:200',
                Rule::unique('clients', 'email')
                    ->where('clinic_id', current_clinic()->id)
                    ->whereNull('deleted_at')
                    ->ignore($clientId),
            ],
            'phone' => ['nullable', 'string', 'max:30'],
            'phone_alt' => ['nullable', 'string', 'max:30'],
            'avatar' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:4096'],
            'address' => ['nullable', 'string', 'max:255'],
            'colonia' => ['nullable', 'string', 'max:100'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:10'],
            'curp' => ['nullable', 'string', 'max:20', 'regex:/^[A-Z]{4}[0-9]{6}[HM][A-Z]{5}[A-Z0-9]{2}$/i'],
            'rfc' => ['nullable', 'string', 'max:13'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => $this->filled('email') ? mb_strtolower(trim((string) $this->input('email'))) : null,
            'curp' => $this->filled('curp') ? mb_strtoupper(trim((string) $this->input('curp'))) : null,
            'rfc' => $this->filled('rfc') ? mb_strtoupper(trim((string) $this->input('rfc'))) : null,
        ]);
    }
}
