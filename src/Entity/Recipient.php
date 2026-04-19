<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\RecipientRepository;
use App\Trait\TimestampableTrait;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RecipientRepository::class)]
#[ORM\Table(name: 'recipients')]
#[ORM\HasLifecycleCallbacks]
class Recipient
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Transfer::class, inversedBy: 'recipients')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Transfer $transfer;

    #[ORM\Column(length: 64, unique: true)]
    private string $token;

    #[ORM\Column(type: Types::TEXT)]
    private string $email;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?DateTimeImmutable $downloadedAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?DateTimeImmutable $lastReminderSentAt = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $passwordHash = null;

    public function __construct()
    {
        $this->token = bin2hex(random_bytes(32));
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTransfer(): Transfer
    {
        return $this->transfer;
    }

    public function setTransfer(Transfer $transfer): static
    {
        $this->transfer = $transfer;

        return $this;
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = mb_strtolower(mb_trim($email));

        return $this;
    }

    public function hasDownloaded(): bool
    {
        return $this->downloadedAt instanceof DateTimeImmutable;
    }

    public function getDownloadedAt(): ?DateTimeImmutable
    {
        return $this->downloadedAt;
    }

    public function markAsDownloaded(): static
    {
        $this->downloadedAt = new DateTimeImmutable();

        return $this;
    }

    public function getLastReminderSentAt(): ?DateTimeImmutable
    {
        return $this->lastReminderSentAt;
    }

    public function setLastReminderSentAt(DateTimeImmutable $lastReminderSentAt): static
    {
        $this->lastReminderSentAt = $lastReminderSentAt;

        return $this;
    }

    public function getPasswordHash(): ?string
    {
        return $this->passwordHash;
    }

    public function setPasswordHash(?string $passwordHash): static
    {
        $this->passwordHash = $passwordHash;

        return $this;
    }

    public function isPasswordProtected(): bool
    {
        return null !== $this->passwordHash;
    }
}
