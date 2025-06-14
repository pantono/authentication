<?php

namespace Pantono\Authentication\Model;

use Pantono\Contracts\Attributes\Lazy;
use Pantono\Contracts\Attributes\Locator;
use Pantono\Authentication\UserAuthentication;
use Pantono\Contracts\Attributes\FieldName;
use Pantono\Database\Traits\SavableModel;
use Pantono\Contracts\Attributes\Filter;

class LoginProviderUser
{
    use SavableModel;

    private ?int $id = null;
    private ?int $userId = null;
    #[Lazy, Locator(methodName: 'getProviderById', className: UserAuthentication::class), FieldName('provider_id')]
    private ?LoginProvider $provider = null;
    private string $providerUserId;
    private string $accessToken;
    private ?string $refreshToken = null;
    private \DateTimeInterface $tokenExpires;
    private \DateTimeInterface $dateConnected;
    private ?\DateTimeInterface $lastUsed = null;
    #[Filter('json_decode')]
    private array $values = [];

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function setUserId(?int $userId): void
    {
        $this->userId = $userId;
    }

    public function getProvider(): ?LoginProvider
    {
        return $this->provider;
    }

    public function setProvider(?LoginProvider $provider): void
    {
        $this->provider = $provider;
    }

    public function getProviderUserId(): string
    {
        return $this->providerUserId;
    }

    public function setProviderUserId(string $providerUserId): void
    {
        $this->providerUserId = $providerUserId;
    }

    public function getAccessToken(): string
    {
        return $this->accessToken;
    }

    public function setAccessToken(string $accessToken): void
    {
        $this->accessToken = $accessToken;
    }

    public function getRefreshToken(): ?string
    {
        return $this->refreshToken;
    }

    public function setRefreshToken(?string $refreshToken): void
    {
        $this->refreshToken = $refreshToken;
    }

    public function getTokenExpires(): \DateTimeInterface
    {
        return $this->tokenExpires;
    }

    public function setTokenExpires(\DateTimeInterface $tokenExpires): void
    {
        $this->tokenExpires = $tokenExpires;
    }

    public function getDateConnected(): \DateTimeInterface
    {
        return $this->dateConnected;
    }

    public function setDateConnected(\DateTimeInterface $dateConnected): void
    {
        $this->dateConnected = $dateConnected;
    }

    public function getLastUsed(): ?\DateTimeInterface
    {
        return $this->lastUsed;
    }

    public function setLastUsed(?\DateTimeInterface $lastUsed): void
    {
        $this->lastUsed = $lastUsed;
    }

    public function getValues(): array
    {
        return $this->values;
    }

    public function setValues(array $values): void
    {
        $this->values = $values;
    }
}
