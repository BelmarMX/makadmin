<?php

namespace App\Domain\User\DataTransferObjects;

use App\Http\Requests\Clinic\StoreUserRequest;
use App\Http\Requests\Clinic\UpdateProfileRequest;
use App\Http\Requests\Clinic\UpdateUserRequest;
use Illuminate\Http\UploadedFile;

final readonly class UserData
{
    /**
     * @param  list<string>  $roles
     */
    public function __construct(
        public string $name,
        public string $email,
        public ?string $phone,
        public ?int $branchId,
        public ?string $professionalLicense,
        public ?string $password,
        public ?UploadedFile $avatar,
        public array $roles = [],
    ) {}

    public static function fromStoreRequest(StoreUserRequest $request): self
    {
        $validated = $request->validated();

        return new self(
            name: $validated['name'],
            email: $validated['email'],
            phone: $validated['phone'] ?? null,
            branchId: (int) $validated['branch_id'],
            professionalLicense: $validated['professional_license'] ?? null,
            password: $validated['password'],
            avatar: $request->file('avatar'),
            roles: $validated['roles'],
        );
    }

    public static function fromUpdateRequest(UpdateUserRequest $request): self
    {
        $validated = $request->validated();

        return new self(
            name: $validated['name'],
            email: $validated['email'],
            phone: $validated['phone'] ?? null,
            branchId: (int) $validated['branch_id'],
            professionalLicense: $validated['professional_license'] ?? null,
            password: $validated['password'] ?? null,
            avatar: $request->file('avatar'),
            roles: $validated['roles'] ?? [],
        );
    }

    public static function fromProfileRequest(UpdateProfileRequest $request): self
    {
        $validated = $request->validated();

        return new self(
            name: $validated['name'],
            email: $request->user()->email,
            phone: $validated['phone'] ?? null,
            branchId: null,
            professionalLicense: null,
            password: $validated['password'] ?? null,
            avatar: $request->file('avatar'),
        );
    }
}
