<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    private ?string $email = null;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(length: 100)]
    private ?string $username = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTime $birthDate = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, options: ['default' => '0.00'])]
    private string $balance = '0.00';

    #[ORM\Column(options: ['default' => false])]
    private bool $isSuspended = false;

    #[ORM\Column(nullable: true)]
    private ?\DateTime $selfExcludedUntil = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $dailyBetLimit = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $weeklyBetLimit = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $dailyDepositLimit = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $weeklyDepositLimit = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $pendingDailyBetLimit = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTime $pendingDailyBetLimitAt = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $pendingWeeklyBetLimit = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTime $pendingWeeklyBetLimitAt = null;

    public function isCurrentlySelfExcluded(): bool
    {
        return $this->selfExcludedUntil !== null && $this->selfExcludedUntil > new \DateTime();
    }

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

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Ensure the session doesn't contain actual password hashes by CRC32C-hashing them, as supported since Symfony 7.3.
     */
    public function __serialize(): array
    {
        $data = (array) $this;
        $data["\0" . self::class . "\0password"] = hash('crc32c', $this->password);
        
        return $data;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): static
    {
        $this->username = $username;

        return $this;
    }

    public function getBirthDate(): ?\DateTime
    {
        return $this->birthDate;
    }

    public function setBirthDate(\DateTime $birthDate): static
    {
        $this->birthDate = $birthDate;

        return $this;
    }

    public function getBalance(): float
    {
        return (float) $this->balance;
    }

    public function setBalance(float $balance): static
    {
        $this->balance = (string) $balance;

        return $this;
    }

    public function isSuspended(): bool
    {
        return $this->isSuspended;
    }

    public function setIsSuspended(bool $isSuspended): static
    {
        $this->isSuspended = $isSuspended;

        return $this;
    }

    public function getSelfExcludedUntil(): ?\DateTime
    {
        return $this->selfExcludedUntil;
    }

    public function setSelfExcludedUntil(?\DateTime $selfExcludedUntil): static
    {
        $this->selfExcludedUntil = $selfExcludedUntil;

        return $this;
    }

    public function getDailyBetLimit(): ?string
    {
        return $this->dailyBetLimit;
    }

    public function setDailyBetLimit(?string $dailyBetLimit): static
    {
        $this->dailyBetLimit = $dailyBetLimit;

        return $this;
    }

    public function getWeeklyBetLimit(): ?string
    {
        return $this->weeklyBetLimit;
    }

    public function setWeeklyBetLimit(?string $weeklyBetLimit): static
    {
        $this->weeklyBetLimit = $weeklyBetLimit;

        return $this;
    }

    public function getDailyDepositLimit(): ?string
    {
        return $this->dailyDepositLimit;
    }

    public function setDailyDepositLimit(?string $dailyDepositLimit): static
    {
        $this->dailyDepositLimit = $dailyDepositLimit;

        return $this;
    }

    public function getWeeklyDepositLimit(): ?string
    {
        return $this->weeklyDepositLimit;
    }

    public function setWeeklyDepositLimit(?string $weeklyDepositLimit): static
    {
        $this->weeklyDepositLimit = $weeklyDepositLimit;

        return $this;
    }

    public function getPendingDailyBetLimit(): ?string
    {
        return $this->pendingDailyBetLimit;
    }

    public function setPendingDailyBetLimit(?string $pendingDailyBetLimit): static
    {
        $this->pendingDailyBetLimit = $pendingDailyBetLimit;

        return $this;
    }

    public function getPendingDailyBetLimitAt(): ?\DateTime
    {
        return $this->pendingDailyBetLimitAt;
    }

    public function setPendingDailyBetLimitAt(?\DateTime $pendingDailyBetLimitAt): static
    {
        $this->pendingDailyBetLimitAt = $pendingDailyBetLimitAt;

        return $this;
    }

    public function getPendingWeeklyBetLimit(): ?string
    {
        return $this->pendingWeeklyBetLimit;
    }

    public function setPendingWeeklyBetLimit(?string $pendingWeeklyBetLimit): static
    {
        $this->pendingWeeklyBetLimit = $pendingWeeklyBetLimit;

        return $this;
    }

    public function getPendingWeeklyBetLimitAt(): ?\DateTime
    {
        return $this->pendingWeeklyBetLimitAt;
    }

    public function setPendingWeeklyBetLimitAt(?\DateTime $pendingWeeklyBetLimitAt): static
    {
        $this->pendingWeeklyBetLimitAt = $pendingWeeklyBetLimitAt;

        return $this;
    }
}
