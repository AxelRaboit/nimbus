<?php

declare(strict_types=1);

namespace App\Contract;

use App\Entity\AccessRequest;

interface AccessRequestManagerInterface
{
    public function create(string $email, ?string $name, ?string $message, ?int $requestedFileSizeMb = null): AccessRequest;

    public function approve(AccessRequest $request, ?int $grantedFileSizeMb = null): void;

    public function consume(AccessRequest $request): void;

    public function reject(AccessRequest $request): void;
}
