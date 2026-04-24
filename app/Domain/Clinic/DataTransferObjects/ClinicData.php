<?php

namespace App\Domain\Clinic\DataTransferObjects;

use App\Domain\Clinic\Enums\FiscalRegime;
use App\Domain\Clinic\Enums\ModuleKey;
use App\Http\Requests\Admin\StoreClinicRequest;
use App\Http\Requests\Admin\UpdateClinicRequest;
use Illuminate\Http\UploadedFile;

final readonly class ClinicData
{
    /** @param ModuleKey[] $modules */
    public function __construct(
        public string $slug,
        public string $legalName,
        public string $commercialName,
        public ?string $rfc,
        public ?FiscalRegime $fiscalRegime,
        public ?string $taxAddress,
        public string $responsibleVetName,
        public string $responsibleVetLicense,
        public string $contactPhone,
        public string $contactEmail,
        public ?string $primaryColor,
        public ?UploadedFile $logo,
        public ClinicBranchData $mainBranch,
        public array $modules,
        public ClinicAdminInvitationData $admin,
    ) {}

    public static function fromRequest(StoreClinicRequest $request): self
    {
        $validated = $request->validated();

        return new self(
            slug: $validated['slug'],
            legalName: $validated['legal_name'],
            commercialName: $validated['commercial_name'],
            rfc: $validated['rfc'] ?? null,
            fiscalRegime: isset($validated['fiscal_regime']) ? FiscalRegime::from($validated['fiscal_regime']) : null,
            taxAddress: $validated['tax_address'] ?? null,
            responsibleVetName: $validated['responsible_vet_name'],
            responsibleVetLicense: $validated['responsible_vet_license'],
            contactPhone: $validated['contact_phone'],
            contactEmail: $validated['contact_email'],
            primaryColor: $validated['primary_color'] ?? null,
            logo: $request->file('logo'),
            mainBranch: new ClinicBranchData(
                name: $validated['main_branch']['name'],
                address: $validated['main_branch']['address'],
                phone: $validated['main_branch']['phone'] ?? null,
                isMain: true,
            ),
            modules: array_map(fn (string $key) => ModuleKey::from($key), $validated['modules']),
            admin: new ClinicAdminInvitationData(
                name: $validated['admin']['name'],
                email: $validated['admin']['email'],
                phone: $validated['admin']['phone'] ?? null,
            ),
        );
    }

    public static function fromUpdateRequest(UpdateClinicRequest $request): self
    {
        $validated = $request->validated();

        return new self(
            slug: $validated['slug'],
            legalName: $validated['legal_name'],
            commercialName: $validated['commercial_name'],
            rfc: $validated['rfc'] ?? null,
            fiscalRegime: isset($validated['fiscal_regime']) ? FiscalRegime::from($validated['fiscal_regime']) : null,
            taxAddress: $validated['tax_address'] ?? null,
            responsibleVetName: $validated['responsible_vet_name'],
            responsibleVetLicense: $validated['responsible_vet_license'],
            contactPhone: $validated['contact_phone'],
            contactEmail: $validated['contact_email'],
            primaryColor: $validated['primary_color'] ?? null,
            logo: $request->file('logo'),
            mainBranch: new ClinicBranchData('', '', null, false),
            modules: [],
            admin: new ClinicAdminInvitationData('', '', null),
        );
    }
}
