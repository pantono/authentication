<?php

namespace Pantono\Authentication\Model;

use Pantono\Contracts\Attributes\Locator;
use Pantono\Authentication\Users;

#[Locator(methodName: 'getPermissionById', className: Users::class)]
class Permission
{
    private ?int $id = null;
    private string $name;
    private string $description;

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
}
