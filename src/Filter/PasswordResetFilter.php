<?php

namespace Pantono\Authentication\Filter;

use Pantono\Contracts\Filter\PageableInterface;
use Pantono\Database\Traits\Pageable;
use Pantono\Authentication\Model\User;

class PasswordResetFilter implements PageableInterface
{
    use Pageable;

    private ?User $user = null;
    private ?bool $completed = null;
    private ?\DateTimeInterface $dateExpiresStart = null;
    private ?\DateTimeInterface $dateExpiresEnd = null;
    private ?\DateTimeInterface $dateCreatedStart = null;
    private ?\DateTimeInterface $dateCreatedEnd = null;

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): void
    {
        $this->user = $user;
    }

    public function getCompleted(): ?bool
    {
        return $this->completed;
    }

    public function setCompleted(?bool $completed): void
    {
        $this->completed = $completed;
    }

    public function getDateExpiresStart(): ?\DateTimeInterface
    {
        return $this->dateExpiresStart;
    }

    public function setDateExpiresStart(?\DateTimeInterface $dateExpiresStart): void
    {
        $this->dateExpiresStart = $dateExpiresStart;
    }

    public function getDateExpiresEnd(): ?\DateTimeInterface
    {
        return $this->dateExpiresEnd;
    }

    public function setDateExpiresEnd(?\DateTimeInterface $dateExpiresEnd): void
    {
        $this->dateExpiresEnd = $dateExpiresEnd;
    }

    public function getDateCreatedStart(): ?\DateTimeInterface
    {
        return $this->dateCreatedStart;
    }

    public function setDateCreatedStart(?\DateTimeInterface $dateCreatedStart): void
    {
        $this->dateCreatedStart = $dateCreatedStart;
    }

    public function getDateCreatedEnd(): ?\DateTimeInterface
    {
        return $this->dateCreatedEnd;
    }

    public function setDateCreatedEnd(?\DateTimeInterface $dateCreatedEnd): void
    {
        $this->dateCreatedEnd = $dateCreatedEnd;
    }
}
