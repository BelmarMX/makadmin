<?php

namespace App\Http\Requests\Clinic;

use App\Domain\Clinic\Models\ClinicBranch;
use App\Models\User;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

class SyncBranchPermissionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->route('user');

        return $user instanceof User && $this->user()?->can('managePermissions', $user) === true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'permissions' => ['required', 'array'],
            'permissions.*' => ['array'],
            'permissions.*.*' => ['string', 'in:view,create,update,delete'],
            'branch_id' => [
                'nullable',
                'integer',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (
                        $value !== null
                        && ! ClinicBranch::withoutGlobalScopes()
                            ->where('clinic_id', current_clinic()->id)
                            ->where('id', $value)
                            ->exists()
                    ) {
                        $fail('La sucursal no pertenece a esta clínica.');
                    }
                },
            ],
        ];
    }
}
