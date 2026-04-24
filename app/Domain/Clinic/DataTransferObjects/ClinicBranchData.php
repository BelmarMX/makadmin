<?php

namespace App\Domain\Clinic\DataTransferObjects;

final readonly class ClinicBranchData
{
    public function __construct(
        public string $name,
        public string $address,
        public ?string $phone,
        public bool $isMain,
    ) {}
}
