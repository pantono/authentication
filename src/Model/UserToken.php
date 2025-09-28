<?php

namespace Pantono\Authentication\Model;

use Pantono\Contracts\Attributes\Locator;
use Pantono\Contracts\Attributes\FieldName;
use Pantono\Contracts\Attributes\NoSave;
use Pantono\Database\Traits\SavableModel;
use Pantono\Contracts\Attributes\Lazy;
use Pantono\Authentication\UserAuthentication;
use Pantono\Authentication\Users;

#[Locator(methodName: 'getUserTokenById', className: UserAuthentication::class)]
class UserToken
{
    use SavableModel;

    private ?int $id = null;
    private int $userId;
    private ?int $apiTokenId = null;
    #[NoSave]
    private ?string $userName = null;
    private string $token;
    private \DateTimeInterface $dateCreated;
    private \DateTimeInterface $dateExpires;
    private \DateTimeInterface $dateLastUsed;
    #[Locator(methodName: 'getUserById', className: Users::class), FieldName('user_id'), NoSave, Lazy]
    private ?User $user = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function setUserId(int $userId): void
    {
        $this->userId = $userId;
    }

    public function getUserName(): ?string
    {
        return $this->userName;
    }

    public function setUserName(?string $userName): void
    {
        $this->userName = $userName;
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function setToken(string $token): void
    {
        $this->token = $token;
    }

    public function getApiTokenId(): ?int
    {
        return $this->apiTokenId;
    }

    public function setApiTokenId(?int $apiTokenId = null): void
    {
        $this->apiTokenId = $apiTokenId;
    }

    public function getDateExpires(): \DateTimeInterface
    {
        return $this->dateExpires;
    }

    public function setDateExpires(\DateTimeInterface $dateExpires): void
    {
        $this->dateExpires = $dateExpires;
    }

    public function getDateCreated(): \DateTimeInterface
    {
        return $this->dateCreated;
    }

    public function setDateCreated(\DateTimeInterface $dateCreated): void
    {
        $this->dateCreated = $dateCreated;
    }

    public function getDateLastUsed(): \DateTimeInterface
    {
        return $this->dateLastUsed;
    }

    public function setDateLastUsed(\DateTimeInterface $dateLastUsed): void
    {
        $this->dateLastUsed = $dateLastUsed;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user): void
    {
        $this->user = $user;
    }
}
