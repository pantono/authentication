<?php

namespace Pantono\Authentication\Model;

use Pantono\Database\Traits\SavableModel;

class ApiToken
{
    use SavableModel;

    private ?int $id = null;
    private string $applicationName;
    private string $token;
    private \DateTimeInterface $dateCreated;
    private \DateTimeInterface $dateExpires;
    private \DateTimeInterface $dateLastUsed;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    public function getApplicationName(): string
    {
        return $this->applicationName;
    }

    public function setApplicationName(string $applicationName): void
    {
        $this->applicationName = $applicationName;
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

    public function getDateLastUsed(): \DateTimeInterface
    {
        return $this->dateLastUsed;
    }

    public function setDateLastUsed(\DateTimeInterface $dateLastUsed): void
    {
        $this->dateLastUsed = $dateLastUsed;
    }
}
