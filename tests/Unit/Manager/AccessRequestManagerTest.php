<?php

declare(strict_types=1);

namespace App\Tests\Unit\Manager;

use App\Entity\AccessRequest;
use App\Enum\AccessRequestStatusEnum;
use App\Manager\AccessRequestManager;
use App\Repository\ApplicationParameterRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use stdClass;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment as TwigEnvironment;

final class AccessRequestManagerTest extends TestCase
{
    public function testCreatePersistsAndReturnsRequest(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())->method('persist')->with(self::isInstanceOf(AccessRequest::class));
        $entityManager->expects(self::once())->method('flush');

        $request = $this->buildManager(entityManager: $entityManager)->create('user@example.com', 'John', 'Need access');

        self::assertInstanceOf(AccessRequest::class, $request);
        self::assertSame('user@example.com', $request->getRequesterEmail());
        self::assertSame('John', $request->getRequesterName());
        self::assertSame('Need access', $request->getMessage());
        self::assertTrue($request->isPending());
    }

    public function testCreateSetsExpiryFromParam(): void
    {
        $before = new DateTimeImmutable('+23 hours');
        $request = $this->buildManager()->create('user@example.com', null, null);
        $after = new DateTimeImmutable('+25 hours');

        self::assertGreaterThan($before, $request->getExpiresAt());
        self::assertLessThan($after, $request->getExpiresAt());
    }

    public function testCreateDispatchesAdminEmail(): void
    {
        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects(self::once())->method('dispatch')->willReturn(new Envelope(new stdClass()));

        $this->buildManager(bus: $bus)->create('user@example.com', null, null);
    }

    public function testCreateWithNullNameAndMessageStoresNull(): void
    {
        $request = $this->buildManager()->create('user@example.com', '', '');

        self::assertNull($request->getRequesterName());
        self::assertNull($request->getMessage());
    }

    public function testCreateStoresRequestedFileSizeMb(): void
    {
        $request = $this->buildManager()->create('user@example.com', null, null, 1000);

        self::assertSame(1000, $request->getRequestedFileSizeMb());
    }

    public function testCreateWithNullRequestedFileSizeMbStoresNull(): void
    {
        $request = $this->buildManager()->create('user@example.com', null, null, null);

        self::assertNull($request->getRequestedFileSizeMb());
    }

    public function testApproveSetsStatusAndGeneratesAccessToken(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())->method('flush');

        $accessRequest = new AccessRequest('user@example.com', new DateTimeImmutable('+24 hours'));

        $this->buildManager(entityManager: $entityManager)->approve($accessRequest);

        self::assertTrue($accessRequest->isApproved());
        self::assertNotNull($accessRequest->getAccessToken());
        self::assertSame(64, mb_strlen($accessRequest->getAccessToken()));
        self::assertFalse($accessRequest->isAccessTokenExpired());
    }

    public function testApproveDispatchesRequesterEmail(): void
    {
        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects(self::once())->method('dispatch')->willReturn(new Envelope(new stdClass()));

        $accessRequest = new AccessRequest('user@example.com', new DateTimeImmutable('+24 hours'));

        $this->buildManager(bus: $bus)->approve($accessRequest);
    }

    public function testApproveSetsGrantedFileSizeMb(): void
    {
        $accessRequest = new AccessRequest('user@example.com', new DateTimeImmutable('+24 hours'));

        $this->buildManager()->approve($accessRequest, 500);

        self::assertSame(500, $accessRequest->getGrantedFileSizeMb());
    }

    public function testApproveWithNullGrantedFileSizeMbLeavesNull(): void
    {
        $accessRequest = new AccessRequest('user@example.com', new DateTimeImmutable('+24 hours'));

        $this->buildManager()->approve($accessRequest, null);

        self::assertNull($accessRequest->getGrantedFileSizeMb());
    }

    public function testRejectSetsStatusRejected(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())->method('flush');

        $accessRequest = new AccessRequest('user@example.com', new DateTimeImmutable('+24 hours'));

        $this->buildManager(entityManager: $entityManager)->reject($accessRequest);

        self::assertSame(AccessRequestStatusEnum::Rejected, $accessRequest->getStatus());
        self::assertFalse($accessRequest->isPending());
        self::assertFalse($accessRequest->isApproved());
    }

    public function testRejectDispatchesRejectionEmail(): void
    {
        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects(self::once())->method('dispatch')->willReturn(new Envelope(new stdClass()));

        $accessRequest = new AccessRequest('user@example.com', new DateTimeImmutable('+24 hours'));

        $this->buildManager(bus: $bus)->reject($accessRequest);
    }

    private function buildManager(
        ?EntityManagerInterface $entityManager = null,
        ?MessageBusInterface $bus = null,
    ): AccessRequestManager {
        $params = $this->createStub(ApplicationParameterRepository::class);
        $params->method('get')->willReturn('24');

        $twig = $this->createStub(TwigEnvironment::class);
        $twig->method('render')->willReturn('<html></html>');

        $urlGenerator = $this->createStub(UrlGeneratorInterface::class);
        $urlGenerator->method('generate')->willReturn('http://localhost/some-url');

        $bus ??= $this->createStub(MessageBusInterface::class);
        $bus->method('dispatch')->willReturn(new Envelope(new stdClass()));

        return new AccessRequestManager(
            $entityManager ?? $this->createStub(EntityManagerInterface::class),
            $params,
            $bus,
            $twig,
            $urlGenerator,
            $this->createStub(LoggerInterface::class),
        );
    }
}
