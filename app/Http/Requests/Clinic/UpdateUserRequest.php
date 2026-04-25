<?php

namespace App\Http\Requests\Clinic;

use App\Domain\User\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->route('user');

        return $user instanceof User && $this->user()?->can('update', $user) === true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $user = $this->route('user');

        return [
            'name' => ['required', 'string', 'max:200'],
            'email' => ['required', 'email', 'max:200', Rule::unique('users', 'email')->ignore($user?->id)],
            'phone' => ['nullable', 'string', 'max:20'],
            'branch_id' => [
                'required',
                Rule::exists('clinic_branches', 'id')->where('clinic_id', current_clinic()->id),
            ],
            'professional_license' => ['nullable', 'string', 'max:50'],
            'password' => ['nullable', 'string', 'min:10', 'confirmed'],
            'avatar' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
            'roles' => ['nullable', 'array'],
            'roles.*' => [Rule::enum(UserRole::class)],
            'branch_roles' => ['nullable', 'array', 'min:1'],
            'branch_roles.*.branch_id' => [
                'required',
                Rule::exists('clinic_branches', 'id')->where('clinic_id', current_clinic()->id),
            ],
            'branch_roles.*.roles' => ['required', 'array', 'min:1'],
            'branch_roles.*.roles.*' => [Rule::enum(UserRole::class)],
        ];
    }
}
