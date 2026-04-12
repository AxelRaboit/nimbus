<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\LocaleEnum;
use App\Enum\PlanEnum;
use App\Enum\UserRoleEnum;
use App\Repository\UserRepository;
use App\Trait\TimestampableTrait;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[ORM\HasLifecycleCallbacks]
#[UniqueEntity(fields: ['email'], message: 'auth.register.email_taken')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    private ?string $email = null;

    #[ORM\Column]
    private array $roles = [];

    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(length: 255)]
    private string $name = '';

    #[ORM\Column(length: 10, enumType: PlanEnum::class)]
    private PlanEnum $plan = PlanEnum::Free;

    #[ORM\Column(nullable: true)]
    private ?DateTimeImmutable $trialEndsAt = null;

    #[ORM\Column(length: 5, enumType: LocaleEnum::class)]
    private LocaleEnum $locale = LocaleEnum::French;

    /** Custom file size limit (MB) set by admin. Overrides plan default when set. */
    #[ORM\Column(nullable: true)]
    private ?int $customFileSizeMb = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = UserRoleEnum::User->value;

        return array_unique($roles);
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    public function eraseCredentials(): void {}

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getPlan(): PlanEnum
    {
        return $this->plan;
    }

    public function setPlan(PlanEnum $plan): static
    {
        $this->plan = $plan;

        return $this;
    }

    public function getTrialEndsAt(): ?DateTimeImmutable
    {
        return $this->trialEndsAt;
    }

    public function setTrialEndsAt(?DateTimeImmutable $trialEndsAt): static
    {
        $this->trialEndsAt = $trialEndsAt;

        return $this;
    }

    public function getCustomFileSizeMb(): ?int
    {
        return $this->customFileSizeMb;
    }

    public function setCustomFileSizeMb(?int $customFileSizeMb): static
    {
        $this->customFileSizeMb = $customFileSizeMb;

        return $this;
    }

    public function getLocale(): LocaleEnum
    {
        return $this->locale;
    }

    public function setLocale(LocaleEnum $locale): static
    {
        $this->locale = $locale;

        return $this;
    }
}
