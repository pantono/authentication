<?php

namespace Pantono\Authentication\Repository;

use Pantono\Database\Repository\DefaultRepository;
use Pantono\Contracts\Locator\UserInterface;
use Pantono\Authentication\Model\User;
use Pantono\Authentication\Filter\UserFilter;
use Pantono\Authentication\Filter\UserHistoryFilter;

class UsersRepository extends DefaultRepository
{
    public function getUserById(int $id): ?array
    {
        return $this->selectSingleRow($this->pt('user'), 'id', $id);
    }

    public function getPermissionsForUser(UserInterface $user): array
    {
        $select = $this->getDb()->select('p.*')->from($this->pt('user_permission'), 'up')
            ->innerJoin('up', $this->pt('permission'), 'p', 'up.permission_id=p.id')
            ->andWhere('up.user_id=:id')
            ->setParameter('id', $user->getId());

        return $this->getDb()->fetchAll($select);
    }

    public function getAllPermissions(): array
    {
        return $this->selectAll($this->pt('permission'), 'name');
    }

    public function getGroupsForUser(UserInterface $user): array
    {
        $select = $this->getDb()->select('g.*')->from($this->pt('user_group'), 'ug')
            ->innerJoin('ug', $this->pt('group'), 'g', 'ug.group_id=g.id')
            ->andWhere('ug.user_id=:id')
            ->setParameter('id', $user->getId());

        return $this->getDb()->fetchAll($select);
    }

    public function saveUser(User $user): void
    {
        $id = $this->insertOrUpdate($this->pt('user'), 'id', $user->getId(), $user->getAllData());
        if ($id) {
            $user->setId($id);
        }

        $this->getDb()->delete($this->pt('user_group'), ['user_id' => $user->getId()]);
        foreach ($user->getGroups() as $group) {
            $this->getDb()->insert($this->pt('user_group'), ['user_id' => $user->getId(), 'group_id' => $group->getId()]);
        }

        $this->getDb()->delete($this->pt('user_permission'), ['user_id' => $user->getId()]);
        foreach ($user->getPermissions() as $permission) {
            $this->getDb()->insert($this->pt('user_permission'), ['user_id' => $user->getId(), 'permission_id' => $permission->getId()]);
        }

        $ids = [];
        foreach ($user->getFields() as $field) {
            $id = $this->insertOrUpdate($this->pt('user_field'), 'id', $field->getId(), [
                'user_id' => $user->getId(),
                'field_type_id' => $field->getType()->getId(),
                'value' => $field->getValue()
            ]);
            if ($id) {
                $field->setId($id);
            }
            $ids[] = $id;
        }
        $this->deleteNotIn($this->pt('user_field'), 'user_id', $user->getId(), $ids);
    }

    public function getFieldsForUser(User $user): array
    {
        return $this->selectRowsByValues($this->pt('user_field'), ['user_id' => $user->getId()]);
    }

    public function getUserFieldTypeById(int $id): ?array
    {
        return $this->selectSingleRow($this->pt('user_field_type'), 'id', $id);
    }

    public function getUserFieldTypeByName(string $name): ?array
    {
        return $this->selectSingleRow($this->pt('user_field_type'), 'name', $name);
    }

    public function getUserByEmailAddress(string $emailAddress): ?array
    {
        return $this->selectSingleRow($this->pt('user'), 'email_address', $emailAddress);
    }

    public function getUsersByFilter(UserFilter $filter): array
    {
        $select = $this->getDb()->select('u.*')->from($this->pt('user'), 'u');

        if ($filter->getSearch()) {
            $select->andWhere('(u.forename like :search OR u.surname like :search or u.email_address like :search)')
                ->setParameter('search', '%' . $filter->getSearch() . '%');
        }

        if ($filter->getEmailAddress() !== null) {
            $select->andWhere('u.email_address like :email_address')
                ->setParameter('email_address', '%' . $filter->getEmailAddress() . '%');
        }
        if ($filter->getForename() !== null) {
            $select->andWhere('u.forename like :forename')
                ->setParameter('forename', '%' . $filter->getForename() . '%');
        }
        if ($filter->getSurname() !== null) {
            $select->andWhere('u.surname like :surname')
                ->setParameter('surname', '%' . $filter->getSurname() . '%');
        }

        if ($filter->getPermission() !== null) {
            $select->innerJoin('u', 'user_permission', 'up', 'u.id=up.user_id')
                ->andWhere('up_permission_id=:permission_id')
                ->setParameter('permission_id', $filter->getPermission()->getId());
        }

        if ($filter->getDateCreatedStart() !== null) {
            $select->andWhere('date_created >= :date_created_start')
                ->setParameter('date_created_start', $filter->getDateCreatedStart()->format('Y-m-d H:i:s'));
        }
        if ($filter->getDateCreatedEnd() !== null) {
            $select->andWhere('date_created <= :date_created_end')
                ->setParameter('date_created_end', $filter->getDateCreatedEnd()->format('Y-m-d H:i:s'));
        }

        if ($filter->getDisabled() !== null) {
            $select->andWhere('disabled=:disabled')
                ->setParameter('disabled', $filter->getDisabled() ? 1 : 0);
        }
        if ($filter->getDeleted() !== null) {
            $select->andWhere('deleted=:deleted')
                ->setParameter('deleted', $filter->getDeleted() ? 1 : 0);
        }

        foreach ($filter->getFields() as $field) {
            $fieldTable = 'field_' . $field;
            $fieldTypTable = 'field_type_' . $field;
            $select->innerJoin('u', 'user_field', $fieldTable, $fieldTable . '.user_id=u.id')
                ->innerJoin($fieldTable, 'field_type', $fieldTypTable, $fieldTypTable . '.id=' . $fieldTable . '.field_type_id')
                ->andWhere($fieldTypTable . '.name=:field_name')
                ->setParameter('field_name', $field);
        }

        $this->applyCountAndLimit($select, $filter);

        return $this->getDb()->fetchAll($select);
    }

    public function addHistoryForUser(User $user, string $entry, User $byUser, array $context = []): void
    {
        $this->getDb()->insert($this->pt('user_history'), [
            'target_user_id' => $user->getId(),
            'date' => (new \DateTime())->format('Y-m-d H:i:s'),
            'entry' => $entry,
            'by_user_id' => $byUser->getId(),
            'context' => json_encode($context)
        ]);
    }

    public function getAllGroups(): array
    {
        return $this->selectAll($this->pt('group'), 'name ASC');
    }

    public function getGroupById(int $id): ?array
    {
        return $this->selectSingleRow($this->pt('user'), 'id', $id);
    }

    public function getPermissionById(int $id): ?array
    {
        return $this->selectSingleRow($this->pt('permission'), 'id', $id);
    }

    public function getUserByField(string $field, mixed $value): ?array
    {
        $select = $this->getDb()->select('u.*')->from($this->pt('user'), 'u')
            ->innerJoin('u', $this->pt('user_field'), 'uf', 'u.id=uf.user_id')
            ->innerJoin('uf', $this->pt('user_field_type'), 'ut', 'uf.field_type_id=ut.id')
            ->andWhere('uf.value=:value')
            ->andWhere('ut.name=:field')
            ->setParameter('value', $value)
            ->setParameter('field', $field);

        return $this->getDb()->fetchRow($select);
    }


    public function getUserHistoryByFilter(UserHistoryFilter $filter): array
    {
        $select = $this->getDb()->select('uh.*')->from($this->pt('user_history'), 'uh');

        if ($filter->getUser() !== null) {
            $select->andWhere('uh.target_user_id=:target_user_id')
                ->setParameter('target_user_id', $filter->getUser()->getId());
        }
        if ($filter->getStartDate() !== null) {
            $select->andWhere('uh.date >= :date_start')
                ->setParameter('date_start', $filter->getStartDate()->format('Y-m-d H:i:s'));
        }
        if ($filter->getEndDate() !== null) {
            $select->andWhere('uh.date <= :date_end')
                ->setParameter('date_end', $filter->getEndDate()->format('Y-m-d H:i:s'));
        }
        foreach ($filter->getFields() as $field) {
            $param = 'field_' . $field['name'];
            $select->andWhere('uh.context->>' . $field['name'] . ' = :' . $param)
                ->setParameter($param, $field['value']);
        }

        $this->applyCountAndLimit($select, $filter);

        return $this->getDb()->fetchAll($select);
    }
}
