<?php

namespace Pantono\Authentication\Filter;

use Pantono\Contracts\Filter\PageableInterface;
use Pantono\Database\Traits\Pageable;
use Pantono\Authentication\Model\Permission;
use Pantono\Authentication\Model\Group;

class UserFilter implements PageableInterface
{
    use Pageable;

    private ?string $emailAddress = null;
    private ?string $forename = null;
    private ?string $surname = null;
    private ?string $search = null;
    private ?Permission $permission = null;
    private ?bool $disabled = null;
    private ?bool $deleted = null;
    private ?Group $group = null;
    private ?\DateTimeInterface $dateCreatedStart = null;
    private ?\DateTimeInterface $dateCreatedEnd = null;
    /**
     * @var array<string,mixed>
     */
    private array $fields = [];
    private string $orderColumn = 'id';
    private string $orderDir = 'asc';

    public function getEmailAddress(): ?string
    {
        return $this->emailAddress;
    }

    public function setEmailAddress(?string $emailAddress): void
    {
        $this->emailAddress = $emailAddress;
    }

    public function getForename(): ?string
    {
        return $this->forename;
    }

    public function setForename(?string $forename): void
    {
        $this->forename = $forename;
    }

    public function getSurname(): ?string
    {
        return $this->surname;
    }

    public function setSurname(?string $surname): void
    {
        $this->surname = $surname;
    }

    public function getSearch(): ?string
    {
        return $this->search;
    }

    public function setSearch(?string $search): void
    {
        $this->search = $search;
    }

    public function getPermission(): ?Permission
    {
        return $this->permission;
    }

    public function setPermission(?Permission $permission): void
    {
        $this->permission = $permission;
    }

    public function getDisabled(): ?bool
    {
        return $this->disabled;
    }

    public function setDisabled(?bool $disabled): void
    {
        $this->disabled = $disabled;
    }

    public function getDeleted(): ?bool
    {
        return $this->deleted;
    }

    public function setDeleted(?bool $deleted): void
    {
        $this->deleted = $deleted;
    }

    public function getGroup(): ?Group
    {
        return $this->group;
    }

    public function setGroup(?Group $group): void
    {
        $this->group = $group;
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

    public function getFields(): array
    {
        return $this->fields;
    }

    public function addField(string $field, mixed $value): void
    {
        $this->fields[$field] = $value;
    }

    public function getOrderColumn(): string
    {
        return $this->orderColumn;
    }

    public function setOrderColumn(string $orderColumn): void
    {
        $allowed = ['email_address', 'date_created', 'date_last_login', 'forename', 'surname', 'id', 'deleted', 'disabled', 'tfa_enabled', 'system_user', 'verified'];
        $orderColumn = strtolower($orderColumn);
        if (!in_array($orderColumn, $allowed)) {
            throw new \RuntimeException('Order by column not allowed');
        }
        $this->orderColumn = $orderColumn;
    }

    public function getOrderDir(): string
    {
        return $this->orderDir;
    }

    public function setOrderDir(string $orderDir): void
    {
        $orderDir = strtolower($orderDir);
        if ($orderDir !== 'asc' && $orderDir !== 'desc') {
            throw new \RuntimeException('Invalid order by direction');
        }
        $this->orderDir = $orderDir;
    }
}
