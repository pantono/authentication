<?php

namespace Pantono\Authentication\Model;

use Pantono\Contracts\Attributes\Filter;
use Pantono\Authentication\UserAuthentication;
use Pantono\Contracts\Attributes\Locator;
use Pantono\Contracts\Attributes\FieldName;

#[Locator(methodName: 'getProviderById', className: UserAuthentication::class)]
class LoginProvider
{
    private ?int $id = null;
    #[Locator(methodName: 'getProviderTypeById', className: UserAuthentication::class), FieldName('type_id')]
    private ?LoginProviderType $type = null;
    /**
     * @var array<string,mixed>
     */
    #[Filter('json_decode')]
    private array $config = [];

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    public function getType(): ?LoginProviderType
    {
        return $this->type;
    }

    public function setType(?LoginProviderType $type): void
    {
        $this->type = $type;
    }

    public function getConfig(): array
    {
        return $this->config;
    }

    public function setConfig(array $config): void
    {
        $this->config = $config;
    }

    public function getConfigField(string $string): ?string
    {
        return $this->getConfig()[$string] ?? null;
    }
}
