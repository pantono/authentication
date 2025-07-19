<?php

namespace Pantono\Authentication\Repository;

use Pantono\Database\Repository\MysqlRepository;
use Pantono\Authentication\Model\UserToken;
use Pantono\Contracts\Locator\UserInterface;
use Pantono\Authentication\Model\User;
use Pantono\Authentication\Filter\UserFilter;

class UsersRepository extends MysqlRepository
{
    public function getUserById(int $id): ?array
    {
        return $this->selectSingleRow('user', 'id', $id);
    }

    public function getPermissionsForUser(UserInterface $user): array
    {
        $select = $this->getDb()->select()->from('user_permission', [])
            ->joinInner('permission', 'user_permission.permission_id=permission.id')
            ->where('user_permission.user_id=?', $user->getId());

        return $this->getDb()->fetchAll($select);
    }

    public function getAllPermissions(): array
    {
        return $this->selectAll('permission', 'name');
    }

    public function getGroupsForUser(UserInterface $user): array
    {
        $select = $this->getDb()->select()->from('user_group', [])
            ->joinInner(['g' => '`group`'], 'user_group.group_id=g.id')
            ->where('user_group.user_id=?', $user->getId());
        return $this->getDb()->fetchAll($select);
    }

    public function saveUser(User $user): void
    {
        $id = $this->insertOrUpdate('user', 'id', $user->getId(), $user->getAllData());
        if ($id) {
            $user->setId($id);
        }

        $this->getDb()->delete('user_group', ['user_id=?' => $user->getId()]);
        foreach ($user->getGroups() as $group) {
            $this->getDb()->insert('user_group', ['user_id' => $user->getId(), 'group_id' => $group->getId()]);
        }

        $this->getDb()->delete('user_permission', ['user_id=?' => $user->getId()]);
        foreach ($user->getPermissions() as $permission) {
            $this->getDb()->insert('user_permission', ['user_id' => $user->getId(), 'permission_id' => $permission->getId()]);
        }
    }

    public function getFieldsForUser(User $user): array
    {
        return $this->selectRowsByValues('user_field', ['user_id' => $user->getId()]);
    }

    public function getUserFieldTypeById(int $id): ?array
    {
        return $this->selectSingleRow('user_field_type', 'id', $id);
    }

    public function getUserByEmailAddress(string $emailAddress): ?array
    {
        return $this->selectSingleRow('user', 'email_address', $emailAddress);
    }

    public function getUsersByFilter(UserFilter $filter): array
    {
        $select = $this->getDb()->select()->from('user');

        if ($filter->getSearch()) {
            $select->where('(forename like ?', '%' . $filter->getSearch() . '%')
                ->orWhere('surname like ?', '%' . $filter->getSearch() . '%')
                ->orWhere('email_address like ?)', '%' . $filter->getSearch() . '%');
        }

        if ($filter->getEmailAddress() !== null) {
            $select->where('email_address like ?', '%' . $filter->getEmailAddress() . '%');
        }
        if ($filter->getForename() !== null) {
            $select->where('forename like ?', '%' . $filter->getForename() . '%');
        }
        if ($filter->getSurname() !== null) {
            $select->where('surname like ?', '%' . $filter->getSurname() . '%');
        }

        if ($filter->getPermission() !== null) {
            $select->joinInner('user_permission', 'user_permission.user_id=user.id', [])
                ->where('user_permission.permission_id=?', $filter->getPermission()->getId());
        }

        if ($filter->getDateCreatedStart() !== null) {
            $select->where('date_created >= ?', $filter->getDateCreatedStart()->format('Y-m-d H:i:s'));
        }
        if ($filter->getDateCreatedEnd() !== null) {
            $select->where('date_created <= ?', $filter->getDateCreatedEnd()->format('Y-m-d H:i:s'));
        }

        if ($filter->getDisabled() !== null) {
            $select->where('disabled=?', $filter->getDisabled() ? 1 : 0);
        }
        if ($filter->getDeleted() !== null) {
            $select->where('deleted=?', $filter->getDeleted() ? 1 : 0);
        }

        foreach ($filter->getFields() as $field) {
            $select->joinInner(['field_' . $field => 'user_field'], 'field_' . $field . '.user_id=user.id', [])
                ->joinInner(['field_type_' . $field => 'user_field_type'], 'field_type_.' . $field . '.id=user_field.field_type_id', [])
                ->where('field_type_.' . $field . '.name=?', $field);
        }

        $filter->setTotalResults($this->getCount($select));
        $select->limitPage($filter->getPage(), $filter->getPerPage());

        return $this->getDb()->fetchAll($select);
    }

    public function addHistoryForUser(User $user, string $entry, User $byUser): void
    {
        $this->getDb()->insert('user_history', [
            'target_user_id' => $user->getId(),
            'date' => (new \DateTime())->format('Y-m-d H:i:s'),
            'entry' => $entry,
            'by_user_id' => $byUser->getId()
        ]);
    }

    public function getAllGroups(): array
    {
        return $this->selectAll('group', 'name ASC');
    }

    public function getGroupById(int $id): ?array
    {
        return $this->selectSingleRow('user', 'id', $id);
    }

    public function getPermissionById(int $id): ?array
    {
        return $this->selectSingleRow('permission', 'id', $id);
    }

}
