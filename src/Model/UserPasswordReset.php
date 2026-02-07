<?php

namespace Pantono\Authentication\Model;

use Pantono\Database\Traits\SavableModel;
use Pantono\Contracts\Attributes\FieldName;
use Pantono\Contracts\Attributes\DatabaseTable;
use Pantono\Contracts\Attributes\Database\OneToOne;

#[DatabaseTable('user_password_reset')]
class UserPasswordReset
{
    use SavableModel;

    private ?int $id = null;
    #[OneToOne(User::class), FieldName('user_id')]
    private ?User $user = null;
    private string $token;
    private \DateTimeInterface $dateCreated;
    private \DateTimeInterface $dateExpires;
    private bool $completed;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): void
    {
        $this->user = $user;
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

    public function isCompleted(): bool
    {
        return $this->completed;
    }

    public function setCompleted(bool $completed): void
    {
        $this->completed = $completed;
    }

    public function isExpired(): bool
    {
        return $this->getDateExpires() <= new \DateTime();
    }
}
