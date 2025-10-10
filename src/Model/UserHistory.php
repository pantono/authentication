<?php

namespace Pantono\Authentication\Model;

use Pantono\Contracts\Attributes\NoSave;
use Pantono\Contracts\Attributes\Filter;

class UserHistory
{
    private ?int $id = null;
    private int $targetUserId;
    private ?int $byUserId = null;
    #[NoSave]
    private ?string $byUserName = null;
    private \DateTimeInterface $date;
    private string $entry;
    /**
     * @var array<string, mixed>
     */
    #[Filter('json_decode')]
    private array $context = [];

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    public function getTargetUserId(): int
    {
        return $this->targetUserId;
    }

    public function setTargetUserId(int $targetUserId): void
    {
        $this->targetUserId = $targetUserId;
    }

    public function getByUserId(): ?int
    {
        return $this->byUserId;
    }

    public function setByUserId(?int $byUserId): void
    {
        $this->byUserId = $byUserId;
    }

    public function getByUserName(): ?string
    {
        return $this->byUserName;
    }

    public function setByUserName(?string $byUserName): void
    {
        $this->byUserName = $byUserName;
    }

    public function getDate(): \DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): void
    {
        $this->date = $date;
    }

    public function getEntry(): string
    {
        return $this->entry;
    }

    public function setEntry(string $entry): void
    {
        $this->entry = $entry;
    }

    public function getContext(): array
    {
        return $this->context;
    }

    public function setContext(array $context): void
    {
        $this->context = $context;
    }
}
