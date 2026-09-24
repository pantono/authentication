<?php

namespace Pantono\Authentication\Model;

use Pantono\Contracts\Attributes\DatabaseTable;
use Pantono\Contracts\Attributes\Database\OneToMany;

#[DatabaseTable('permission')]
class Permission
{
    private ?int $id = null;
    private string $name;
    private string $description;
    /**
     * @var Permission[]
     */
    #[OneToMany(targetModel: Permission::class, mappedBy: 'parent_id')]
    private array $children = [];
    private ?int $parentId = null;

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

    /**
     * @return list<string>
     */
    public function getHierarchicalNames(): array
    {
        $names = [];
        foreach ($this->iterateHierarchy() as $permission) {
            $names[] = $permission->getName();
        }
        return $names;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function getChildren(): array
    {
        return $this->children;
    }

    public function setChildren(array $children): void
    {
        $this->children = $children;
    }

    public function getParentId(): ?int
    {
        return $this->parentId;
    }

    public function setParentId(?int $parentId): void
    {
        $this->parentId = $parentId;
    }

    public function containsChild(string $name): bool
    {
        foreach ($this->iterateHierarchy() as $permission) {
            if ($permission !== $this && $permission->getName() === $name) {
                return true;
            }
        }
        return false;
    }

    /**
     * @return \Generator<int, Permission>
     */
    private function iterateHierarchy(): \Generator
    {
        $pending = [$this];
        $visitedIds = [];
        /** @var \SplObjectStorage<Permission, null> $visitedObjects */
        $visitedObjects = new \SplObjectStorage();

        while ($pending !== []) {
            $permission = array_pop($pending);
            $id = $permission->getId();
            // Hydration may return different objects for the same database row.
            if ($id !== null) {
                if (isset($visitedIds[$id])) {
                    continue;
                }
                $visitedIds[$id] = true;
            } else {
                if (isset($visitedObjects[$permission])) {
                    continue;
                }
                $visitedObjects[$permission] = null;
            }

            yield $permission;

            // Preserve depth-first child order and only load children when needed.
            foreach (array_reverse($permission->getChildren()) as $child) {
                $pending[] = $child;
            }
        }
    }
}
