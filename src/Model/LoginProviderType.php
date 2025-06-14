<?php

namespace Pantono\Authentication\Model;

class LoginProviderType
{
    private ?int $id = null;
    private string $name;
    private string $providerClass;
    private bool $allowsRegistration = false;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getProviderClass(): string
    {
        return $this->providerClass;
    }

    public function setProviderClass(string $providerClass): void
    {
        $this->providerClass = $providerClass;
    }

    public function isAllowsRegistration(): bool
    {
        return $this->allowsRegistration;
    }

    public function setAllowsRegistration(bool $allowsRegistration): void
    {
        $this->allowsRegistration = $allowsRegistration;
    }
}
