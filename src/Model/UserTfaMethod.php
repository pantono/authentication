<?php

namespace Pantono\Authentication\Model;

use Pantono\Database\Traits\SavableModel;
use Pantono\Contracts\Attributes\Locator;
use Pantono\Authentication\Users;
use Pantono\Contracts\Attributes\FieldName;
use Pantono\Contracts\Attributes\Lazy;
use Pantono\Authentication\TwoFactorAuth;

#[Locator(methodName: 'getUserMethodById', className: TwoFactorAuth::class)]
class UserTfaMethod
{
    use SavableModel;

    private ?int $id = null;
    private \DateTimeInterface $dateCreated;
    private ?\DateTimeInterface $dateLastUsed = null;
    private int $userId;
    #[Locator(methodName: 'getUserById', className: Users::class), FieldName('user_id'), Lazy]
    private ?User $user = null;
    private TfaType $tfaType;
    /**
     * @var array<string,mixed>
     */
    private array $config = [];
    private bool $enabled = false;
    private bool $verified = false;
    private bool $deleted = false;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    public function getDateCreated(): \DateTimeInterface
    {
        return $this->dateCreated;
    }

    public function setDateCreated(\DateTimeInterface $dateCreated): void
    {
        $this->dateCreated = $dateCreated;
    }

    public function getDateLastUsed(): ?\DateTimeInterface
    {
        return $this->dateLastUsed;
    }

    public function setDateLastUsed(?\DateTimeInterface $dateLastUsed): void
    {
        $this->dateLastUsed = $dateLastUsed;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function setUserId(int $userId): void
    {
        $this->userId = $userId;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): void
    {
        $this->user = $user;
    }

    public function getTfaType(): TfaType
    {
        return $this->tfaType;
    }

    public function setTfaType(TfaType $tfaType): void
    {
        $this->tfaType = $tfaType;
    }

    public function getConfig(): array
    {
        return $this->config;
    }

    public function setConfig(array $config): void
    {
        $this->config = $config;
    }

    public function getConfigField(string $string, mixed $default = null): ?string
    {
        return $this->getConfig()[$string] ?? $default;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): void
    {
        $this->enabled = $enabled;
    }

    public function isVerified(): bool
    {
        return $this->verified;
    }

    public function setVerified(bool $verified): void
    {
        $this->verified = $verified;
    }

    public function isDeleted(): bool
    {
        return $this->deleted;
    }

    public function setDeleted(bool $deleted): void
    {
        $this->deleted = $deleted;
    }
}
