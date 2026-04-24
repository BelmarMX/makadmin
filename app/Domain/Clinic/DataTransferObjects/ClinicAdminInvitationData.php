<?php

namespace App\Domain\Clinic\DataTransferObjects;

final readonly class ClinicAdminInvitationData
{
    public function __construct(
        public string $name,
        public string $email,
        public ?string $phone,
    ) {}
}
