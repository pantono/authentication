<?php

namespace Pantono\Authentication\Model;

use Pantono\Database\Traits\SavableModel;
use Pantono\Contracts\Attributes\FieldName;
use Pantono\Contracts\Attributes\NoSave;
use Pantono\Contracts\Attributes\DatabaseTable;
use Pantono\Contracts\Attributes\Database\OneToOne;

#[DatabaseTable('user_tfa_attempt')]
class UserTfaAttempt
{
    use SavableModel;

    private ?int $id = null;
    #[OneToOne(UserTfaMethod::class), FieldName('method_id')]
    private ?UserTfaMethod $method = null;
    private \DateTimeInterface $dateCreated;
    private \DateTimeInterface $dateExpires;
    private string $attemptCode;
    private string $attemptSlug;
    private bool $verified = false;
    #[NoSave]
    private bool $dummy = false;
    private bool $remember = false;
    private ?\DateTimeInterface $rememberExpires = null;

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

    public function isRemember(): bool
    {
        return $this->remember;
    }

    public function setRemember(bool $remember): void
    {
        $this->remember = $remember;
    }

    public function getRememberExpires(): ?\DateTimeInterface
    {
        return $this->rememberExpires;
    }

    public function setRememberExpires(?\DateTimeInterface $rememberExpires): void
    {
        $this->rememberExpires = $rememberExpires;
    }
}
