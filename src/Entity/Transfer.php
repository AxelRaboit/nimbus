<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\TransferStatusEnum;
use App\Repository\TransferRepository;
use App\Trait\TimestampableTrait;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Serializer\Attribute\SerializedName;

#[ORM\Entity(repositoryClass: TransferRepository::class)]
#[ORM\Table(name: 'transfers')]
#[ORM\HasLifecycleCallbacks]
#[ORM\Index(name: 'IDX_transfer_status', columns: ['status'])]
#[ORM\Index(name: 'IDX_transfer_expires_at', columns: ['expires_at'])]
class Transfer
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 64, unique: true)]
    private string $token;

    #[ORM\Column(length: 64, unique: true)]
    private string $ownerToken;

    #[ORM\Column(length: 9, unique: true)]
    private string $reference;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $senderEmail = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $senderName = null;

    /**
     * Non persisted plain text message.
     * Used as a buffer for encryption/decryption.
     */
    private ?string $message = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $encryptedMessage = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private DateTimeImmutable $expiresAt;

    #[ORM\Column(length: 20, enumType: TransferStatusEnum::class, options: ['default' => 'pending'])]
    private TransferStatusEnum $status = TransferStatusEnum::Pending;

    #[ORM\Column(length: 64, nullable: true)]
    private ?string $tusUploadKey = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $passwordHash = null;

    #[ORM\Column(options: ['default' => false])]
    private bool $isPublic = false;

    #[ORM\Column(options: ['default' => 0])]
    private int $publicDownloadCount = 0;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $user = null;

    /**
     * @var Collection<int, TransferFile>
     */
    #[ORM\OneToMany(targetEntity: TransferFile::class, mappedBy: 'transfer', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $files;

    /**
     * @var Collection<int, Recipient>
     */
    #[ORM\OneToMany(targetEntity: Recipient::class, mappedBy: 'transfer', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $recipients;

    public function __construct()
    {
        $this->files = new ArrayCollection();
        $this->recipients = new ArrayCollection();
        $this->token = bin2hex(random_bytes(32));
        $this->ownerToken = bin2hex(random_bytes(32));
        $this->reference = mb_strtoupper(bin2hex(random_bytes(2))).'-'.mb_strtoupper(bin2hex(random_bytes(2)));
        // Defensive fallback — always overridden by TransferManager::create() via expiresInHours.
        $this->expiresAt = new DateTimeImmutable('+1 day');
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    #[Groups(['transfer:list'])]
    public function getToken(): string
    {
        return $this->token;
    }

    #[Groups(['transfer:list'])]
    public function getOwnerToken(): string
    {
        return $this->ownerToken;
    }

    #[Groups(['transfer:list', 'transfer:read'])]
    public function getReference(): string
    {
        return $this->reference;
    }

    public function getSenderEmail(): ?string
    {
        return $this->senderEmail;
    }

    public function setSenderEmail(?string $senderEmail): static
    {
        if (null === $senderEmail || '' === mb_trim($senderEmail)) {
            $this->senderEmail = null;

            return $this;
        }

        $this->senderEmail = mb_strtolower(mb_trim($senderEmail));

        return $this;
    }

    public function getSenderName(): ?string
    {
        return $this->senderName;
    }

    public function setSenderName(?string $senderName): static
    {
        $this->senderName = $senderName;

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

    public function getEncryptedMessage(): ?string
    {
        return $this->encryptedMessage;
    }

    public function setEncryptedMessage(?string $encryptedMessage): static
    {
        $this->encryptedMessage = $encryptedMessage;

        return $this;
    }

    #[Groups(['transfer:list', 'transfer:read'])]
    public function getExpiresAt(): DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function setExpiresAt(DateTimeImmutable $expiresAt): static
    {
        $this->expiresAt = $expiresAt;

        return $this;
    }

    public function isExpired(): bool
    {
        return $this->expiresAt < new DateTimeImmutable();
    }

    #[Groups(['transfer:list', 'transfer:read'])]
    public function getStatus(): TransferStatusEnum
    {
        return $this->status;
    }

    public function setStatus(TransferStatusEnum $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function isPending(): bool
    {
        return TransferStatusEnum::Pending === $this->status;
    }

    public function isReady(): bool
    {
        return TransferStatusEnum::Ready === $this->status;
    }

    public function getTusUploadKey(): ?string
    {
        return $this->tusUploadKey;
    }

    public function setTusUploadKey(?string $tusUploadKey): static
    {
        $this->tusUploadKey = $tusUploadKey;

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

    /**
     * @return Collection<int, TransferFile>
     */
    #[Groups(['transfer:list', 'transfer:read'])]
    public function getFiles(): Collection
    {
        return $this->files;
    }

    public function addFile(TransferFile $file): static
    {
        if (!$this->files->contains($file)) {
            $this->files->add($file);
            $file->setTransfer($this);
        }

        return $this;
    }

    public function removeFile(TransferFile $file): static
    {
        $this->files->removeElement($file);

        return $this;
    }

    public function getTotalFilesSize(): int
    {
        $total = 0;
        foreach ($this->files as $file) {
            $total += $file->getFileSize();
        }

        return $total;
    }

    /**
     * @return Collection<int, Recipient>
     */
    #[Groups(['transfer:list'])]
    public function getRecipients(): Collection
    {
        return $this->recipients;
    }

    #[Groups(['transfer:read'])]
    public function getRecipientCount(): int
    {
        return $this->recipients->count();
    }

    public function addRecipient(Recipient $recipient): static
    {
        if (!$this->recipients->contains($recipient)) {
            $this->recipients->add($recipient);
            $recipient->setTransfer($this);
        }

        return $this;
    }

    public function removeRecipient(Recipient $recipient): static
    {
        $this->recipients->removeElement($recipient);

        return $this;
    }

    public function hasRecipients(): bool
    {
        return $this->recipients->count() > 0;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    #[Groups(['transfer:list'])]
    #[SerializedName('isPublic')]
    public function isPublic(): bool
    {
        return $this->isPublic;
    }

    public function setIsPublic(bool $isPublic): static
    {
        $this->isPublic = $isPublic;

        return $this;
    }

    #[Groups(['transfer:list'])]
    public function getPublicDownloadCount(): int
    {
        return $this->publicDownloadCount;
    }

    public function incrementPublicDownloadCount(): static
    {
        ++$this->publicDownloadCount;

        return $this;
    }
}
