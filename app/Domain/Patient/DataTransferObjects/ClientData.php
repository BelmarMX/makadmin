<?php

namespace App\Domain\Patient\DataTransferObjects;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;

final readonly class ClientData
{
    public function __construct(
        public string $name,
        public ?string $email,
        public ?string $phone,
        public ?string $phoneAlt,
        public ?string $address,
        public ?string $colonia,
        public ?string $city,
        public ?string $state,
        public ?string $postalCode,
        public ?string $curp,
        public ?string $rfc,
        public ?string $notes,
    ) {}

    public static function fromRequest(FormRequest|Request $request): self
    {
        $validated = method_exists($request, 'validated')
            ? $request->validated()
            : $request->all();

        return new self(
            name: $validated['name'],
            email: $validated['email'] ?? null,
            phone: $validated['phone'] ?? null,
            phoneAlt: $validated['phone_alt'] ?? null,
            address: $validated['address'] ?? null,
            colonia: $validated['colonia'] ?? null,
            city: $validated['city'] ?? null,
            state: $validated['state'] ?? null,
            postalCode: $validated['postal_code'] ?? null,
            curp: $validated['curp'] ?? null,
            rfc: $validated['rfc'] ?? null,
            notes: $validated['notes'] ?? null,
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'phone_alt' => $this->phoneAlt,
            'address' => $this->address,
            'colonia' => $this->colonia,
            'city' => $this->city,
            'state' => $this->state,
            'postal_code' => $this->postalCode,
            'curp' => $this->curp,
            'rfc' => $this->rfc,
            'notes' => $this->notes,
        ];
    }
}
