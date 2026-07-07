<?php

namespace Pantono\Authentication\Model;

use Pantono\Contracts\Attributes\FieldName;
use Pantono\Contracts\Attributes\Locator;
use Pantono\Authentication\Verification;
use Pantono\Database\Traits\SavableModel;
use Pantono\Contracts\Attributes\DatabaseTable;
use Pantono\Contracts\Attributes\Database\OneToOne;
use Pantono\Contracts\Attributes\NoSave;
use Pantono\Contracts\Attributes\Lazy;

#[DatabaseTable('user_verification')]
class UserVerification
{
    use SavableModel;

    private ?int $id = null;
    #[OneToOne(targetModel: User::class), FieldName('user_id'), NoSave, Lazy]
    private User $user;
    #[OneToOne(targetModel: UserVerificationType::class), FieldName('type_id')]
    private ?UserVerificationType $type = null;
    private string $token;
    private string $code;
    private \DateTimeInterface $dateCreated;
    private \DateTimeInterface $dateExpires;
    private bool $verified;
    private string $credential;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): void
    {
        $this->user = $user;
    }

    public function getType(): ?UserVerificationType
    {
        return $this->type;
    }

    public function setType(?UserVerificationType $type): void
    {
        $this->type = $type;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): void
    {
        $this->code = $code;
    }


    public function getToken(): string
    {
        return $this->token;
    }

    public function setToken(string $token): void
    {
        $this->token = $token;
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

    public function isVerified(): bool
    {
        return $this->verified;
    }

    public function setVerified(bool $verified): void
    {
        $this->verified = $verified;
    }

    public function getCredential(): string
    {
        return $this->credential;
    }

    public function setCredential(string $credential): void
    {
        $this->credential = $credential;
    }
}
