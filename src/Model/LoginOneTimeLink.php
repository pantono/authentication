<?php

namespace Pantono\Authentication\Model;

use Pantono\Contracts\Attributes\Locator;
use Pantono\Authentication\Users;
use Pantono\Contracts\Attributes\FieldName;
use Pantono\Database\Traits\SavableModel;

class LoginOneTimeLink
{
    use SavableModel;

    private ?int $id = null;
    #[Locator(methodName: 'getUserById', className: Users::class), FieldName('user_id')]
    private ?User $user = null;
    private \DateTimeInterface $dateCreated;
    private \DateTimeInterface $dateExpires;
    private ?\DateTimeInterface $dateLoggedIn = null;
    private string $token;
    private bool $deleted = false;

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

    public function getDateLoggedIn(): ?\DateTimeInterface
    {
        return $this->dateLoggedIn;
    }

    public function setDateLoggedIn(?\DateTimeInterface $dateLoggedIn): void
    {
        $this->dateLoggedIn = $dateLoggedIn;
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function setToken(string $token): void
    {
        $this->token = $token;
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
