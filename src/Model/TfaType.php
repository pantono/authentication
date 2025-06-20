<?php

namespace Pantono\Authentication\Model;

use Pantono\Database\Traits\SavableModel;

class TfaType
{
    use SavableModel;

    private ?int $id = null;
    private string $name;
    private string $description;
    private string $controller;
    /**
     * @var array<string,mixed>
     */
    private ?array $config = null;

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

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function getController(): string
    {
        return $this->controller;
    }

    public function setController(string $controller): void
    {
        $this->controller = $controller;
    }

    public function getConfig(): ?array
    {
        return $this->config;
    }

    public function setConfig(?array $config): void
    {
        $this->config = $config;
    }

    public function getConfigField(string $string, mixed $default = null): mixed
    {
        return $this->config[$string] ?? $default;
    }
}
