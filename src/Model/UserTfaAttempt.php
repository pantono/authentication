<?php

namespace Pantono\Authentication\Model;

use Pantono\Database\Traits\SavableModel;
use Pantono\Contracts\Attributes\Locator;
use Pantono\Authentication\TwoFactorAuth;
use Pantono\Contracts\Attributes\FieldName;
use Pantono\Contracts\Attributes\NoSave;

#[Locator(methodName: 'getAttemptById', className: TwoFactorAuth::class)]
class UserTfaAttempt
{
    use SavableModel;

    private ?int $id = null;
    #[Locator(methodName: 'getMethodById', className: TwoFactorAuth::class), FieldName('method_id')]
    private ?UserTfaMethod $method = null;
    private \DateTimeInterface $dateCreated;
    private \DateTimeInterface $dateExpires;
    private string $attemptCode;
    private string $attemptSlug;
    private bool $verified = false;
    #[NoSave]
    private bool $dummy = false;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    public function getMethod(): ?UserTfaMethod
    {
        return $this->method;
    }

    public function setMethod(?UserTfaMethod $method): void
    {
        $this->method = $method;
    }

    public function getDateCreated(): \DateTimeInterface
    {
        return $this->dateCreated;
    }

    public function setDateCreated(\DateTimeInterface $dateCreated): void
    {
        $this->dateCreated = $dateCreated;
    }

    public function getDateExpires(): \DateTimeInterface
    {
        return $this->dateExpires;
    }

    public function setDateExpires(\DateTimeInterface $dateExpires): void
    {
        $this->dateExpires = $dateExpires;
    }

    public function getAttemptCode(): string
    {
        return $this->attemptCode;
    }

    public function setAttemptCode(string $attemptCode): void
    {
        $this->attemptCode = $attemptCode;
    }

    public function getAttemptSlug(): string
    {
        return $this->attemptSlug;
    }

    public function setAttemptSlug(string $attemptSlug): void
    {
        $this->attemptSlug = $attemptSlug;
    }

    public function isVerified(): bool
    {
        return $this->verified;
    }

    public function setVerified(bool $verified): void
    {
        $this->verified = $verified;
    }

    public function isDummy(): bool
    {
        return $this->dummy;
    }

    public function setDummy(bool $dummy): void
    {
        $this->dummy = $dummy;
    }
}
