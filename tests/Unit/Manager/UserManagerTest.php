<?php

declare(strict_types=1);

namespace App\Tests\Unit\Manager;

use App\Entity\User;
use App\Manager\UserManager;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class UserManagerTest extends TestCase
{
    public function testIsPasswordValidDelegatesToHasher(): void
    {
        $user = new User();

        $hasher = $this->createMock(UserPasswordHasherInterface::class);
        $hasher->expects(self::once())->method('isPasswordValid')->with($user, 'plain')->willReturn(true);

        self::assertTrue($this->buildManager(hasher: $hasher)->isPasswordValid($user, 'plain'));
    }

    public function testIsEmailTakenReturnsFalseWhenNotFound(): void
    {
        $repo = $this->createStub(UserRepository::class);
        $repo->method('findOneBy')->willReturn(null);

        self::assertFalse($this->buildManager(repo: $repo)->isEmailTaken('test@example.com'));
    }

    public function testIsEmailTakenReturnsTrueWhenEmailExists(): void
    {
        $repo = $this->createStub(UserRepository::class);
        $repo->method('findOneBy')->willReturn(new User());

        self::assertTrue($this->buildManager(repo: $repo)->isEmailTaken('test@example.com'));
    }

    public function testIsEmailTakenReturnsFalseWhenMatchingExcludedUser(): void
    {
        $user = new User();
        $prop = new ReflectionProperty(User::class, 'id');
        $prop->setValue($user, 42);

        $repo = $this->createStub(UserRepository::class);
        $repo->method('findOneBy')->willReturn($user);

        self::assertFalse($this->buildManager(repo: $repo)->isEmailTaken('test@example.com', $user));
    }

    public function testIsEmailTakenReturnsTrueWhenDifferentUserHasSameEmail(): void
    {
        $existing = new User();
        $excluded = new User();

        $prop = new ReflectionProperty(User::class, 'id');
        $prop->setValue($existing, 1);
        $prop->setValue($excluded, 2);

        $repo = $this->createStub(UserRepository::class);
        $repo->method('findOneBy')->willReturn($existing);

        self::assertTrue($this->buildManager(repo: $repo)->isEmailTaken('test@example.com', $excluded));
    }

    private function buildManager(
        ?UserRepository $repo = null,
        ?UserPasswordHasherInterface $hasher = null,
    ): UserManager {
        return new UserManager(
            $this->createStub(EntityManagerInterface::class),
            $repo ?? $this->createStub(UserRepository::class),
            $hasher ?? $this->createStub(UserPasswordHasherInterface::class),
        );
    }
}
