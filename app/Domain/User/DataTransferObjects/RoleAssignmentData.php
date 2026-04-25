<?php

namespace App\Domain\User\DataTransferObjects;

final readonly class RoleAssignmentData
{
    public function __construct(
        public int $userId,
        public string $role,
    ) {}
}
