<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\StorageBackendEnum;
use App\Repository\TransferFileRepository;
use App\Trait\TimestampableTrait;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Serializer\Attribute\SerializedName;

#[ORM\Entity(repositoryClass: TransferFileRepository::class)]
#[ORM\Table(name: 'transfer_files')]
#[ORM\HasLifecycleCallbacks]
class TransferFile
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Transfer::class, inversedBy: 'files')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Transfer $transfer;

    #[ORM\Column(length: 255)]
    private string $originalName;

    #[ORM\Column(length: 255)]
    private string $filename;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $mimeType = null;

    #[ORM\Column(type: 'bigint')]
    private int $fileSize = 0;

    #[ORM\Column(length: 20)]
    private string $storageBackend = StorageBackendEnum::Local->value;

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

    #[Groups(['transfer:list', 'transfer:read'])]
    #[SerializedName('name')]
    public function getOriginalName(): string
    {
        return $this->originalName;
    }

    public function setOriginalName(string $originalName): static
    {
        $this->originalName = $originalName;

        return $this;
    }

    public function getFilename(): string
    {
        return $this->filename;
    }

    public function setFilename(string $filename): static
    {
        $this->filename = $filename;

        return $this;
    }

    #[Groups(['transfer:read'])]
    public function getMimeType(): ?string
    {
        return $this->mimeType;
    }

    public function setMimeType(?string $mimeType): static
    {
        $this->mimeType = $mimeType;

        return $this;
    }

    #[Groups(['transfer:list', 'transfer:read'])]
    #[SerializedName('size')]
    public function getFileSize(): int
    {
        return $this->fileSize;
    }

    public function setFileSize(int $fileSize): static
    {
        $this->fileSize = $fileSize;

        return $this;
    }

    public function getStorageBackend(): StorageBackendEnum
    {
        return StorageBackendEnum::from($this->storageBackend);
    }

    public function setStorageBackend(StorageBackendEnum $backend): static
    {
        $this->storageBackend = $backend->value;

        return $this;
    }
}
