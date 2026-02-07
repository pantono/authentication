<?php

namespace Pantono\Authentication\Model;

use Pantono\Contracts\Attributes\Filter;
use Pantono\Contracts\Attributes\FieldName;
use Pantono\Contracts\Attributes\DatabaseTable;
use Pantono\Contracts\Attributes\Database\OneToOne;

#[DatabaseTable('login_provider')]
class LoginProvider
{
    private ?int $id = null;
    #[OneToOne(LoginProviderType::class), FieldName('type_id')]
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

    public function getConfigField(string $string): mixed
    {
        return $this->getConfig()[$string] ?? null;
    }
}
