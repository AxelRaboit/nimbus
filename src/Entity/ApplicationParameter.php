<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ApplicationParameterRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ApplicationParameterRepository::class)]
#[ORM\Table(name: 'application_parameter')]
class ApplicationParameter
{
    public function __construct(
        #[ORM\Id]
        #[ORM\Column(length: 100)]
        private string $key,
        #[ORM\Column(type: 'text', nullable: true)]
        private ?string $value = null,
        #[ORM\Column(length: 255, nullable: true)]
        private ?string $description = null
    ) {}

    public function getKey(): string
    {
        return $this->key;
    }

    public function getValue(): ?string
    {
        return $this->value;
    }

    public function setValue(?string $value): static
    {
        $this->value = $value;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }
}
