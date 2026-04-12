<?php

declare(strict_types=1);

namespace App\Manager;

use App\Entity\AccessRequest;
use App\Enum\AccessRequestStatusEnum;
use App\Enum\ApplicationParameter\NimbusApplicationParameterEnum;
use App\Enum\EmailTypeEnum;
use App\Message\EmailQueueMessage;
use App\Repository\ApplicationParameterRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment as TwigEnvironment;

final readonly class AccessRequestManager
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ApplicationParameterRepository $params,
        private MessageBusInterface $messageBus,
        private TwigEnvironment $twig,
        private UrlGeneratorInterface $urlGenerator,
        private LoggerInterface $logger,
    ) {}

    public function create(string $email, ?string $name, ?string $message, ?int $requestedFileSizeMb = null): AccessRequest
    {
        $ttl = (int) $this->params->get(NimbusApplicationParameterEnum::AccessRequestTtlHours->value, '24');
        $expiresAt = new DateTimeImmutable(sprintf('+%d hours', $ttl));

        $request = new AccessRequest($email, $expiresAt);
        $request->setRequesterName($name ?: null);
        $request->setMessage($message ?: null);
        $request->setRequestedFileSizeMb($requestedFileSizeMb);

        $this->entityManager->persist($request);
        $this->entityManager->flush();

        $this->logger->info('Access request created', [
            'email' => $email,
            'requestedFileSizeMb' => $requestedFileSizeMb,
        ]);

        $this->sendAdminNotification($request);

        return $request;
    }

    public function approve(AccessRequest $request, ?int $grantedFileSizeMb = null): void
    {
        $ttl = (int) $this->params->get(NimbusApplicationParameterEnum::AccessRequestTtlHours->value, '24');

        $request->setStatus(AccessRequestStatusEnum::Approved);
        $request->setAccessToken(bin2hex(random_bytes(32)));
        $request->setAccessTokenExpiresAt(new DateTimeImmutable(sprintf('+%d hours', $ttl)));
        $request->setGrantedFileSizeMb($grantedFileSizeMb);

        $this->entityManager->flush();

        $this->logger->info('Access request approved', [
            'id' => $request->getId(),
            'email' => $request->getRequesterEmail(),
            'grantedFileSizeMb' => $grantedFileSizeMb,
        ]);

        $this->sendRequesterApproval($request);
    }

    public function consume(AccessRequest $request): void
    {
        $request->setAccessToken(null);
        $this->entityManager->flush();
    }

    public function reject(AccessRequest $request): void
    {
        $request->setStatus(AccessRequestStatusEnum::Rejected);
        $this->entityManager->flush();

        $this->logger->info('Access request rejected', [
            'id' => $request->getId(),
            'email' => $request->getRequesterEmail(),
        ]);

        $this->sendRequesterRejection($request);
    }

    private function sendRequesterRejection(AccessRequest $request): void
    {
        $body = $this->twig->render('email/access_request_rejected.html.twig', [
            'request' => $request,
        ]);

        $this->messageBus->dispatch(new EmailQueueMessage(
            EmailTypeEnum::AccessRequestRejected->value,
            $request->getRequesterEmail(),
            "Votre demande d'accès",
            $body,
        ));
    }

    private function sendAdminNotification(AccessRequest $request): void
    {
        $adminEmail = $this->params->get(NimbusApplicationParameterEnum::AdminEmail->value, 'axel.raboit@gmail.com'); // Defensive fallback — value should always exist after nimbus:application-parameter is run.

        $approveUrl = $this->urlGenerator->generate(
            'access_request_approve',
            ['token' => $request->getToken()],
            UrlGeneratorInterface::ABSOLUTE_URL,
        );

        $body = $this->twig->render('email/access_request_admin.html.twig', [
            'request' => $request,
            'approveUrl' => $approveUrl,
        ]);

        $this->messageBus->dispatch(new EmailQueueMessage(
            EmailTypeEnum::AccessRequestAdmin->value,
            $adminEmail,
            sprintf("Demande d'accès de %s", $request->getRequesterName() ?? $request->getRequesterEmail()),
            $body,
        ));
    }

    private function sendRequesterApproval(AccessRequest $request): void
    {
        $grantUrl = $this->urlGenerator->generate(
            'access_request_grant',
            ['accessToken' => $request->getAccessToken()],
            UrlGeneratorInterface::ABSOLUTE_URL,
        );

        $body = $this->twig->render('email/access_request_approved.html.twig', [
            'request' => $request,
            'grantUrl' => $grantUrl,
        ]);

        $this->messageBus->dispatch(new EmailQueueMessage(
            EmailTypeEnum::AccessRequestApproved->value,
            $request->getRequesterEmail(),
            "Votre demande d'accès a été approuvée",
            $body,
        ));
    }
}
