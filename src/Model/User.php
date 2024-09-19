<?php

namespace Pantono\Authentication\Model;

use Pantono\Contracts\Locator\UserInterface;
use Pantono\Authentication\Exception\PasswordNeedsRehashException;
use Pantono\Database\Traits\SavableModel;

class User implements UserInterface
{
    use SavableModel;

    private ?int $id = null;
    private string $emailAddress;
    private string $forename;
    private string $surname;
    private string $password;
    private bool $deleted;
    private bool $disabled;
    /**
     * @var Permission[]
     */
    private array $permissions;
    /**
     * @var Group[]
     */
    private array $groups;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    public function getEmailAddress(): string
    {
        return $this->emailAddress;
    }

    public function setEmailAddress(string $emailAddress): void
    {
        $this->emailAddress = $emailAddress;
    }

    public function getForename(): string
    {
        return $this->forename;
    }

    public function setForename(string $forename): void
    {
        $this->forename = $forename;
    }

    public function getSurname(): string
    {
        return $this->surname;
    }

    public function setSurname(string $surname): void
    {
        $this->surname = $surname;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): void
    {
        $this->password = $password;
    }

    public function isDeleted(): bool
    {
        return $this->deleted;
    }

    public function setDeleted(bool $deleted): void
    {
        $this->deleted = $deleted;
    }

    public function isDisabled(): bool
    {
        return $this->disabled;
    }

    public function setDisabled(bool $disabled): void
    {
        $this->disabled = $disabled;
    }

    public function authenticate(string $password): bool
    {
        if (password_needs_rehash($this->getPassword(), PASSWORD_DEFAULT)) {
            throw new PasswordNeedsRehashException('Password needs rehashing');
        }
        return password_verify($password, $this->getPassword());
    }

    public function getName(): string
    {
        return sprintf('%s %s', $this->getForename(), $this->getSurname());
    }

    public function getGroups(): array
    {
        return $this->groups;
    }

    public function setGroups(array $groups): void
    {
        $this->groups = $groups;
    }

    public function getPermissions(): array
    {
        return $this->permissions;
    }

    public function setPermissions(array $permissions): void
    {
        $this->permissions = $permissions;
    }

    public function hasPermission(string $permissionName): bool
    {
        foreach ($this->getPermissions() as $permission) {
            if ($permission->getName() === $permissionName) {
                return true;
            }
        }
        return false;
    }
}
