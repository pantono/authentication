<?php

namespace Pantono\Authentication\Model;

use Pantono\Database\Traits\SavableModel;
use Pantono\Contracts\Attributes\Locator;
use Pantono\Authentication\TwoFactorAuth;

#[Locator(methodName: 'getAttemptById', className: TwoFactorAuth::class)]
class UserTfaAttempt
{
    use SavableModel;

    private ?int $id = null;
    private ?UserTfaMethod $method = null;
    private \DateTimeInterface $dateCreated;
    private \DateTimeInterface $dateExpires;
    private string $attemptCode;
    private string $attemptSecret;
    private bool $verified = false;

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

    public function getAttemptSecret(): string
    {
        return $this->attemptSecret;
    }

    public function setAttemptSecret(string $attemptSecret): void
    {
        $this->attemptSecret = $attemptSecret;
    }

    public function isVerified(): bool
    {
        return $this->verified;
    }

    public function setVerified(bool $verified): void
    {
        $this->verified = $verified;
    }
}
