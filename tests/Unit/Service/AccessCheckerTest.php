<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\User;
use App\Repository\ApplicationParameterRepository;
use App\Service\AccessChecker;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\User\UserInterface;

final class AccessCheckerTest extends TestCase
{
    // isEnabled

    public function testIsEnabledReturnsTrueWhenPasswordSet(): void
    {
        self::assertTrue($this->buildChecker('secret')->isEnabled());
    }

    public function testIsEnabledReturnsFalseWhenPasswordEmpty(): void
    {
        self::assertFalse($this->buildChecker('')->isEnabled());
    }

    // isGranted — password disabled

    public function testIsGrantedReturnsTrueWhenPasswordNotEnabled(): void
    {
        self::assertTrue($this->buildChecker('')->isGranted($this->request(), null));
    }

    // isGranted — admin bypass

    public function testIsGrantedReturnsTrueForAdminUser(): void
    {
        $user = $this->userWithEmail('admin@example.com');

        $checker = $this->buildChecker('secret', adminEmail: 'admin@example.com');

        self::assertTrue($checker->isGranted($this->request(), $user));
    }

    public function testIsGrantedReturnsFalseForNonAdminUser(): void
    {
        $user = $this->userWithEmail('other@example.com');

        $checker = $this->buildChecker('secret', adminEmail: 'admin@example.com');

        self::assertFalse($checker->isGranted($this->request(), $user));
    }

    public function testIsGrantedReturnsFalseForGenericUserInterfaceEvenWithAdminEmail(): void
    {
        $user = $this->createStub(UserInterface::class);
        $user->method('getUserIdentifier')->willReturn('admin@example.com');

        $checker = $this->buildChecker('secret', adminEmail: 'admin@example.com');

        self::assertFalse($checker->isGranted($this->request(), $user));
    }

    // isGranted — session hash

    public function testIsGrantedReturnsTrueWhenSessionHashMatches(): void
    {
        $password = 'secret';
        $request = $this->request(sessionHash: md5($password));

        self::assertTrue($this->buildChecker($password)->isGranted($request, null));
    }

    public function testIsGrantedReturnsFalseWhenSessionHashDoesNotMatch(): void
    {
        $request = $this->request(sessionHash: md5('wrong'));

        self::assertFalse($this->buildChecker('secret')->isGranted($request, null));
    }

    public function testIsGrantedReturnsFalseWhenNoSessionAndNoUser(): void
    {
        self::assertFalse($this->buildChecker('secret')->isGranted($this->request(), null));
    }

    // grant

    public function testGrantSetsCorrectSessionHash(): void
    {
        $password = 'secret';

        $session = $this->createMock(SessionInterface::class);
        $session->expects(self::once())
            ->method('set')
            ->with('access_granted_hash', md5($password));

        $request = $this->createStub(Request::class);
        $request->method('getSession')->willReturn($session);

        $this->buildChecker($password)->grant($request);
    }

    // verify

    public function testVerifyReturnsTrueForCorrectPassword(): void
    {
        self::assertTrue($this->buildChecker('secret')->verify('secret'));
    }

    public function testVerifyReturnsFalseForWrongPassword(): void
    {
        self::assertFalse($this->buildChecker('secret')->verify('wrong'));
    }

    public function testVerifyReturnsFalseForEmptySubmission(): void
    {
        self::assertFalse($this->buildChecker('secret')->verify(''));
    }

    // helpers

    private function buildChecker(string $password, string $adminEmail = 'admin@example.com'): AccessChecker
    {
        $params = $this->createStub(ApplicationParameterRepository::class);
        $params->method('get')->willReturn($adminEmail);

        return new AccessChecker($password, $params);
    }

    private function userWithEmail(string $email): User
    {
        $user = new User();
        $user->setEmail($email);

        return $user;
    }

    private function request(?string $sessionHash = null): Request
    {
        $session = $this->createStub(SessionInterface::class);
        $session->method('get')->willReturnCallback(
            static fn (string $key) => 'access_granted_hash' === $key ? $sessionHash : null,
        );

        $request = $this->createStub(Request::class);
        $request->method('getSession')->willReturn($session);

        return $request;
    }
}
