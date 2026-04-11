<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\User;
use App\Enum\PlanEnum;
use App\Repository\ApplicationParameterRepository;
use App\Service\PlanService;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

final class PlanServiceTest extends TestCase
{
    public function testIsProReturnsTrueForProUserWithoutExpiry(): void
    {
        $user = new User();
        $user->setPlan(PlanEnum::Pro);

        self::assertTrue($this->buildService()->isPro($user));
    }

    public function testIsProReturnsTrueForProUserWithFutureExpiry(): void
    {
        $user = new User();
        $user->setPlan(PlanEnum::Pro);
        $user->setProUntil(new DateTimeImmutable('+7 days'));

        self::assertTrue($this->buildService()->isPro($user));
    }

    public function testIsProReturnsFalseForProUserWithExpiredTrial(): void
    {
        $user = new User();
        $user->setPlan(PlanEnum::Pro);
        $user->setProUntil(new DateTimeImmutable('-1 day'));

        self::assertFalse($this->buildService()->isPro($user));
    }

    public function testIsProReturnsFalseForFreeUser(): void
    {
        $user = new User();

        self::assertFalse($this->buildService()->isPro($user));
    }

    public function testIsProDoesNotMutateExpiredUser(): void
    {
        $user = new User();
        $user->setPlan(PlanEnum::Pro);
        $user->setProUntil(new DateTimeImmutable('-1 day'));

        $this->buildService()->isPro($user);

        self::assertSame(PlanEnum::Pro, $user->getPlan(), 'isPro() must not mutate the entity');
    }

    public function testUpgradeSetsPlanProAndProUntil(): void
    {
        $user = new User();

        $params = $this->createStub(ApplicationParameterRepository::class);
        $params->method('get')->willReturn('30');

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())->method('flush');

        $this->buildService(params: $params, em: $em)->upgrade($user);

        self::assertSame(PlanEnum::Pro, $user->getPlan());
        self::assertNotNull($user->getProUntil());
        self::assertGreaterThan(new DateTimeImmutable('+29 days'), $user->getProUntil());
    }

    public function testDowngradeSetsPlanFreeAndClearsProUntil(): void
    {
        $user = new User();
        $user->setPlan(PlanEnum::Pro);
        $user->setProUntil(new DateTimeImmutable('+10 days'));

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())->method('flush');

        $this->buildService(em: $em)->downgrade($user);

        self::assertSame(PlanEnum::Free, $user->getPlan());
        self::assertNull($user->getProUntil());
    }

    public function testCanAccessMyTransfersOnlyForPro(): void
    {
        $freeUser = new User();
        $proUser  = new User();
        $proUser->setPlan(PlanEnum::Pro);

        $service = $this->buildService();

        self::assertFalse($service->canAccessMyTransfers($freeUser));
        self::assertTrue($service->canAccessMyTransfers($proUser));
    }

    private function buildService(
        ?ApplicationParameterRepository $params = null,
        ?EntityManagerInterface $em = null,
    ): PlanService {
        return new PlanService(
            $params ?? $this->createStub(ApplicationParameterRepository::class),
            $em ?? $this->createStub(EntityManagerInterface::class),
        );
    }
}
