<?php

namespace App\Domain\User\DataTransferObjects;

final readonly class PermissionAssignmentData
{
    /**
     * @param  array<string, list<string>>  $permissionsByModule
     */
    public function __construct(
        public int $userId,
        public array $permissionsByModule,
    ) {}
}
