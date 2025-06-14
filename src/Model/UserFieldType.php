<?php

namespace Pantono\Authentication\Model;

use Pantono\Contracts\Attributes\Locator;
use Pantono\Authentication\Users;

#[Locator(methodName: 'getUserFieldTypeById', className: Users::class)]
class UserFieldType
{
    private ?int $id = null;
    private string $name;
    private string $type;
    private bool $required;

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

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function isRequired(): bool
    {
        return $this->required;
    }

    public function setRequired(bool $required): void
    {
        $this->required = $required;
    }
}
