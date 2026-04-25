<?php

namespace App\Domain\Clinic\Actions;

use App\Domain\Clinic\DataTransferObjects\ClinicData;
use App\Domain\Clinic\Enums\ModuleKey;
use App\Domain\Clinic\Events\ClinicCreated;
use App\Domain\Clinic\Models\Clinic;
use App\Domain\Clinic\Models\ClinicBranch;
use App\Domain\Clinic\Models\ClinicModule;
use App\Support\Tenancy\Scopes\ClinicScope;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CreateClinicAction
{
    public function __construct(
        private readonly InviteClinicAdminAction $inviteAdmin,
        private readonly UploadClinicLogoAction $uploadLogo,
    ) {}

    public function handle(ClinicData $data): Clinic
    {
        return DB::transaction(function () use ($data): Clinic {
            $clinic = Clinic::create([
                'slug' => $data->slug,
                'legal_name' => $data->legalName,
                'commercial_name' => $data->commercialName,
                'rfc' => $data->rfc,
                'fiscal_regime' => $data->fiscalRegime?->value,
                'tax_address' => $data->taxAddress,
                'responsible_vet_name' => $data->responsibleVetName,
                'responsible_vet_license' => $data->responsibleVetLicense,
                'contact_phone' => $data->contactPhone,
                'contact_email' => $data->contactEmail,
                'primary_color' => $data->primaryColor,
                'logo_path' => null,
                'settings' => [],
                'is_active' => false,
            ]);

            if ($data->logo) {
                $this->uploadLogo->handle($clinic, $data->logo);
            }

            $mainBranch = ClinicBranch::withoutGlobalScope(ClinicScope::class)->create([
                'clinic_id' => $clinic->id,
                'name' => $data->mainBranch->name,
                'address' => $data->mainBranch->address,
                'phone' => $data->mainBranch->phone,
                'is_main' => true,
                'is_active' => true,
            ]);

            $this->activateModules($clinic, $data->modules, $mainBranch);

            $this->inviteAdmin->handle($data->admin, $clinic, $mainBranch);

            ClinicCreated::dispatch($clinic, auth()->user());

            Log::channel('security')->info('clinic_created', [
                'clinic_id' => $clinic->id,
                'slug' => $clinic->slug,
                'by_user_id' => auth()->id(),
            ]);

            return $clinic->load(['branches', 'modules']);
        });
    }

    /** @param ModuleKey[] $modules */
    private function activateModules(Clinic $clinic, array $modules, ClinicBranch $mainBranch): void
    {
        $toActivate = $this->resolveDependencies($modules);

        foreach ($toActivate as $moduleKey) {
            ClinicModule::updateOrCreate(
                ['clinic_id' => $clinic->id, 'module_key' => $moduleKey->value],
                [
                    'is_active' => true,
                    'activated_at' => now(),
                    'activated_by' => auth()->id(),
                ],
            );
        }
    }

    /**
     * @param  ModuleKey[]  $modules
     * @return ModuleKey[]
     */
    private function resolveDependencies(array $modules): array
    {
        $resolved = [];

        foreach ($modules as $module) {
            foreach ($module->dependsOn() as $dep) {
                if (! in_array($dep, $resolved, true)) {
                    $resolved[] = $dep;
                }
            }
            if (! in_array($module, $resolved, true)) {
                $resolved[] = $module;
            }
        }

        return $resolved;
    }
}
