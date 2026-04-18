<?php

declare(strict_types=1);

namespace App\Manager;

use App\Contract\UserManagerInterface;
use App\Entity\User;
use App\Enum\UserRoleEnum;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final readonly class UserManager implements UserManagerInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserRepository $userRepository,
        private UserPasswordHasherInterface $passwordHasher,
    ) {}

    public function create(string $name, string $email, string $password): User
    {
        $user = new User();
        $user->setName($name);
        $user->setEmail($email);
        $user->setPassword($this->passwordHasher->hashPassword($user, $password));

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    public function update(User $user, string $name, string $email): void
    {
        $user->setName($name);
        $user->setEmail($email);

        $this->entityManager->flush();
    }

    public function changePassword(User $user, string $newPassword): void
    {
        $user->setPassword($this->passwordHasher->hashPassword($user, $newPassword));
        $this->entityManager->flush();
    }

    public function delete(User $user): void
    {
        $this->entityManager->remove($user);
        $this->entityManager->flush();
    }

    public function toggleDevRole(User $user): void
    {
        $roles = array_values(array_filter(
            $user->getRoles(),
            fn (string $r): bool => $r !== UserRoleEnum::User->value,
        ));

        if (in_array(UserRoleEnum::Dev->value, $roles, true)) {
            $user->setRoles(array_values(array_filter($roles, fn (string $r): bool => $r !== UserRoleEnum::Dev->value)));
        } else {
            $user->setRoles([...$roles, UserRoleEnum::Dev->value]);
        }

        $this->entityManager->flush();
    }

    public function save(User $user): void
    {
        $this->entityManager->flush();
    }

    public function isPasswordValid(User $user, string $plainPassword): bool
    {
        return $this->passwordHasher->isPasswordValid($user, $plainPassword);
    }

    public function isEmailTaken(string $email, ?User $excludeUser = null): bool
    {
        $existing = $this->userRepository->findOneBy(['email' => $email]);

        if (null === $existing) {
            return false;
        }

        return !$excludeUser instanceof User || $existing->getId() !== $excludeUser->getId();
    }
}
