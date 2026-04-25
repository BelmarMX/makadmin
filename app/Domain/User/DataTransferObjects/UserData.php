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
     * @param  list<array{branch_id: int, roles: list<string>}>  $branchRoles
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
        public array $branchRoles = [],
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
            branchRoles: self::branchRoles($validated),
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
            branchRoles: self::branchRoles($validated),
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

    /**
     * @param  array<string, mixed>  $validated
     * @return list<array{branch_id: int, roles: list<string>}>
     */
    private static function branchRoles(array $validated): array
    {
        if (isset($validated['branch_roles']) && is_array($validated['branch_roles'])) {
            return collect($validated['branch_roles'])
                ->map(fn (array $assignment): array => [
                    'branch_id' => (int) $assignment['branch_id'],
                    'roles' => array_values($assignment['roles'] ?? []),
                ])
                ->filter(fn (array $assignment): bool => $assignment['roles'] !== [])
                ->values()
                ->all();
        }

        return [[
            'branch_id' => (int) $validated['branch_id'],
            'roles' => array_values($validated['roles'] ?? []),
        ]];
    }
}
