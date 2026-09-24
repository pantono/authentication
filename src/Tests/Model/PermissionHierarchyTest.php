<?php

namespace Pantono\Authentication\Tests\Model;

use Pantono\Authentication\Model\Permission;
use Pantono\Authentication\Model\User;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PermissionHierarchyTest extends TestCase
{
    public function testParentGrantsDescendantsButNotUnrelatedPermissions(): void
    {
        $leaf = $this->permission('edit_title', 3);
        $child = $this->permission('edit', 2, [$leaf]);
        $root = $this->permission('manage', 1, [$child]);
        $user = new User();
        $user->setPermissions([$root]);

        self::assertTrue($user->hasPermission('manage'));
        self::assertTrue($user->hasPermission('edit'));
        self::assertTrue($user->hasPermission('edit_title'));
        self::assertFalse($user->hasPermission('unrelated'));
        self::assertFalse($user->hasPermission('EDIT'));
        self::assertFalse($root->containsChild('manage'));
        self::assertSame(['manage', 'edit', 'edit_title'], $user->getPermissionList());
    }

    public function testChildDoesNotGrantParentOrSibling(): void
    {
        $child = $this->permission('edit', 2);
        $sibling = $this->permission('delete', 3);
        $root = $this->permission('manage', 1, [$child, $sibling]);
        $child->setParentId($root->getId());
        $user = new User();
        $user->setPermissions([$child]);

        self::assertTrue($user->hasPermission('edit'));
        self::assertFalse($user->hasPermission('manage'));
        self::assertFalse($user->hasPermission('delete'));
        self::assertSame(['edit'], $user->getPermissionList());
    }

    public function testUserWithoutPermissionsHasNoAccess(): void
    {
        $user = new User();

        self::assertFalse($user->hasPermission('manage'));
        self::assertSame([], $user->getPermissionList());
    }

    public function testOverlappingAssignmentsProduceUniqueNames(): void
    {
        $leaf = $this->permission('edit_title', 3);
        $child = $this->permission('edit', 2, [$leaf]);
        $root = $this->permission('manage', 1, [$child]);
        $other = $this->permission('audit', 4);
        $user = new User();
        $user->setPermissions([$root, $child, $leaf, $other, $root]);

        self::assertSame(['manage', 'edit', 'edit_title', 'audit'], $user->getPermissionList());
        self::assertTrue($user->hasPermission('audit'));
        // Effective permissions must not replace the assignments used for saving.
        self::assertSame([$root, $child, $leaf, $other, $root], $user->getPermissions());
    }

    public function testNamesPreserveDepthFirstOrderAcrossBranches(): void
    {
        $leaf = $this->permission('edit_title', 3);
        $child = $this->permission('edit', 2, [$leaf]);
        $sibling = $this->permission('delete', 4);
        $root = $this->permission('manage', 1, [$child, $sibling]);

        self::assertSame(['manage', 'edit', 'edit_title', 'delete'], $root->getHierarchicalNames());
        self::assertTrue($root->containsChild('delete'));
    }

    /**
     * @return array<string, array{bool}>
     */
    public static function persistenceStates(): array
    {
        return ['saved' => [true], 'unsaved' => [false]];
    }

    #[DataProvider('persistenceStates')]
    public function testSelfCycleTerminates(bool $saved): void
    {
        $root = $this->permission('manage', $saved ? 1 : null);
        $root->setChildren([$root]);
        $user = new User();
        $user->setPermissions([$root]);

        self::assertFalse($root->containsChild('manage'));
        self::assertFalse($root->containsChild('missing'));
        self::assertSame(['manage'], $root->getHierarchicalNames());
        self::assertTrue($user->hasPermission('manage'));
        self::assertFalse($user->hasPermission('missing'));
        self::assertSame(['manage'], $user->getPermissionList());
    }

    #[DataProvider('persistenceStates')]
    public function testCycleDoesNotHideLaterBranches(bool $saved): void
    {
        $root = $this->permission('manage', $saved ? 1 : null);
        $child = $this->permission('edit', $saved ? 2 : null, [$root]);
        $sibling = $this->permission('delete', $saved ? 3 : null);
        $root->setChildren([$child, $sibling]);
        $user = new User();
        $user->setPermissions([$root]);

        self::assertFalse($root->containsChild('missing'));
        self::assertFalse($root->containsChild('manage'));
        self::assertTrue($root->containsChild('edit'));
        self::assertTrue($root->containsChild('delete'));
        self::assertSame(['manage', 'edit', 'delete'], $root->getHierarchicalNames());
        self::assertFalse($user->hasPermission('missing'));
        self::assertTrue($user->hasPermission('delete'));
        self::assertSame(['manage', 'edit', 'delete'], $user->getPermissionList());
    }

    public function testRepeatedDatabaseIdIsSkippedBeforeLoadingItsChildren(): void
    {
        $root = $this->permission('manage', 1);
        // A hydrator can return a new instance when a cycle reaches the same row.
        $reloadedRoot = $this->getMockBuilder(Permission::class)
            ->onlyMethods(['getChildren'])->getMock();
        $reloadedRoot->setId(1);
        $reloadedRoot->setName('manage');
        $reloadedRoot->expects($this->never())->method('getChildren');
        $child = $this->permission('edit', 2, [$reloadedRoot]);
        $root->setChildren([$child]);

        self::assertFalse($root->containsChild('missing'));
        self::assertFalse($root->containsChild('manage'));
        self::assertSame(['manage', 'edit'], $root->getHierarchicalNames());
    }

    public function testSuccessfulChecksDoNotLoadUnneededDescendants(): void
    {
        $child = $this->getMockBuilder(Permission::class)
            ->onlyMethods(['getChildren'])->getMock();
        $child->setId(2);
        $child->setName('edit');
        $child->expects($this->never())->method('getChildren');
        $root = $this->permission('manage', 1, [$child]);

        self::assertTrue($root->containsChild('edit'));
    }

    public function testTraversalStateIsFreshForEachCall(): void
    {
        $root = $this->permission('manage', 1);
        self::assertFalse($root->containsChild('edit'));
        self::assertSame(['manage'], $root->getHierarchicalNames());

        $root->setChildren([$this->permission('edit', 2)]);

        self::assertTrue($root->containsChild('edit'));
        self::assertSame(['manage', 'edit'], $root->getHierarchicalNames());
        self::assertTrue($root->containsChild('edit'));
    }

    public function testDeepHierarchyDoesNotRequireRecursiveCalls(): void
    {
        $root = $this->permission('permission_0', 0);
        $current = $root;
        $expected = ['permission_0'];
        for ($id = 1; $id <= 2000; $id++) {
            $child = $this->permission('permission_' . $id, $id);
            $current->setChildren([$child]);
            $current = $child;
            $expected[] = 'permission_' . $id;
        }
        $current->setChildren([$root]);

        self::assertTrue($root->containsChild('permission_2000'));
        self::assertFalse($root->containsChild('missing'));
        self::assertSame($expected, $root->getHierarchicalNames());
    }

    /**
     * @param Permission[] $children
     */
    private function permission(string $name, ?int $id, array $children = []): Permission
    {
        $permission = new Permission();
        $permission->setId($id);
        $permission->setName($name);
        $permission->setChildren($children);
        return $permission;
    }
}
