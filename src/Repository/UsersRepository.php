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

    public function saveUser(UserInterface $user): void
    {
        $id = $this->insertOrUpdate('user', 'id', $user->getId(), $user->getAllData());
        if ($id) {
            $user->setId($id);
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

}
