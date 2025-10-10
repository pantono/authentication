<?php

namespace Pantono\Authentication\Filter;

use Pantono\Authentication\Model\User;
use Pantono\Database\Traits\Pageable;
use Pantono\Contracts\Filter\PageableInterface;

class UserHistoryFilter implements PageableInterface
{
    use Pageable;

    private ?User $user = null;
    private ?\DateTimeInterface $startDate = null;
    private ?\DateTimeInterface $endDate = null;
    /**
     * @var array<string, string|int>
     */
    private array $fields = [];

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): void
    {
        $this->user = $user;
    }

    public function getStartDate(): ?\DateTimeInterface
    {
        return $this->startDate;
    }

    public function setStartDate(?\DateTimeInterface $startDate): void
    {
        $this->startDate = $startDate;
    }

    public function getEndDate(): ?\DateTimeInterface
    {
        return $this->endDate;
    }

    public function setEndDate(?\DateTimeInterface $endDate): void
    {
        $this->endDate = $endDate;
    }

    public function addContextField(string $field, string|int $value): void
    {
        $this->fields[$field] = $value;
    }

    public function getFields(): array
    {
        return $this->fields;
    }
}
