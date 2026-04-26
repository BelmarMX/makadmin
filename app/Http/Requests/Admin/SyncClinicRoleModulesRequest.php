<?php

namespace App\Http\Requests\Admin;

use App\Domain\Clinic\Enums\ModuleKey;
use App\Domain\User\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SyncClinicRoleModulesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->is_super_admin;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'role' => ['required', Rule::enum(UserRole::class)],
            'enabled_modules' => ['required', 'array'],
            'enabled_modules.*' => [Rule::enum(ModuleKey::class)],
        ];
    }
}
