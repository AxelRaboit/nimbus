<?php

declare(strict_types=1);

namespace App\Contract;

use App\Entity\User;

interface UserManagerInterface
{
    public function create(string $name, string $email, string $password): User;

    public function update(User $user, string $name, string $email): void;

    public function changePassword(User $user, string $newPassword): void;

    public function delete(User $user): void;

    public function toggleDevRole(User $user): void;

    public function save(User $user): void;

    public function isPasswordValid(User $user, string $plainPassword): bool;

    public function isEmailTaken(string $email, ?User $excludeUser = null): bool;
}
