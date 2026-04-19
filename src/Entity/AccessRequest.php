<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\AccessRequestStatusEnum;
use App\Repository\AccessRequestRepository;
use App\Trait\TimestampableTrait;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AccessRequestRepository::class)]
#[ORM\Table(name: 'access_requests')]
#[ORM\HasLifecycleCallbacks]
#[ORM\Index(name: 'IDX_access_request_token', columns: ['token'])]
#[ORM\Index(name: 'IDX_access_request_status', columns: ['status'])]
class AccessRequest
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\Column]
    private ?int $id = null;

    /** Token sent to the admin in the approval email link. */
    #[ORM\Column(length: 64, unique: true)]
    private string $token;

    /** One-time token sent to the requester after admin approval. Nulled out after use. */
    #[ORM\Column(length: 64, unique: true, nullable: true)]
    private ?string $accessToken = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $requesterName = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $message = null;

    #[ORM\Column(length: 20, enumType: AccessRequestStatusEnum::class, options: ['default' => 'pending'])]
    private AccessRequestStatusEnum $status = AccessRequestStatusEnum::Pending;

    /** When the requester magic link expires (set on approval). */
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?DateTimeImmutable $accessTokenExpiresAt = null;

    /** File size limit requested by the user (MB). */
    #[ORM\Column(nullable: true)]
    private ?int $requestedFileSizeMb = null;

    /** File size limit granted by the admin (MB). Overrides plan default. */
    #[ORM\Column(nullable: true)]
    private ?int $grantedFileSizeMb = null;

    public function __construct(#[ORM\Column(length: 255)]
        private string $requesterEmail, /** When the admin approval link expires. */
        #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
        private DateTimeImmutable $expiresAt)
    {
        $this->token = bin2hex(random_bytes(32));
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function getAccessToken(): ?string
    {
        return $this->accessToken;
    }

    public function setAccessToken(?string $accessToken): static
    {
        $this->accessToken = $accessToken;

        return $this;
    }

    public function getRequesterEmail(): string
    {
        return $this->requesterEmail;
    }

    public function getRequesterName(): ?string
    {
        return $this->requesterName;
    }

    public function setRequesterName(?string $requesterName): static
    {
        $this->requesterName = $requesterName;

        return $this;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(?string $message): static
    {
        $this->message = $message;

        return $this;
    }

    public function getStatus(): AccessRequestStatusEnum
    {
        return $this->status;
    }

    public function setStatus(AccessRequestStatusEnum $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getExpiresAt(): DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function getAccessTokenExpiresAt(): ?DateTimeImmutable
    {
        return $this->accessTokenExpiresAt;
    }

    public function setAccessTokenExpiresAt(?DateTimeImmutable $accessTokenExpiresAt): static
    {
        $this->accessTokenExpiresAt = $accessTokenExpiresAt;

        return $this;
    }

    public function isPending(): bool
    {
        return AccessRequestStatusEnum::Pending === $this->status;
    }

    public function isApproved(): bool
    {
        return AccessRequestStatusEnum::Approved === $this->status;
    }

    public function isRejected(): bool
    {
        return AccessRequestStatusEnum::Rejected === $this->status;
    }

    public function getRequestedFileSizeMb(): ?int
    {
        return $this->requestedFileSizeMb;
    }

    public function setRequestedFileSizeMb(?int $requestedFileSizeMb): static
    {
        $this->requestedFileSizeMb = $requestedFileSizeMb;

        return $this;
    }

    public function getGrantedFileSizeMb(): ?int
    {
        return $this->grantedFileSizeMb;
    }

    public function setGrantedFileSizeMb(?int $grantedFileSizeMb): static
    {
        $this->grantedFileSizeMb = $grantedFileSizeMb;

        return $this;
    }

    public function isAdminLinkExpired(): bool
    {
        return $this->expiresAt < new DateTimeImmutable();
    }

    public function isAccessTokenExpired(): bool
    {
        return !$this->accessTokenExpiresAt instanceof DateTimeImmutable || $this->accessTokenExpiresAt < new DateTimeImmutable();
    }
}
