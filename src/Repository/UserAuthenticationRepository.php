<?php

namespace Pantono\Authentication\Repository;

use Pantono\Database\Repository\MysqlRepository;
use Pantono\Authentication\Model\UserToken;
use Pantono\Contracts\Locator\UserInterface;
use Pantono\Authentication\Model\User;

class UserAuthenticationRepository extends MysqlRepository
{
    public function getUserByToken(string $token): ?array
    {
        $select = $this->getDb()->select()->from('user_token', [])
            ->joinInner('user', 'user.id=user_token.user_id')
            ->where('user_token.token=?', $token);

        return $this->getDb()->fetchRow($select);
    }

    public function getUserTokenByToken(string $token): ?array
    {
        $select = $this->getDb()->select()->from('user_token')
            ->where('token=?', $token);

        return $this->getDb()->fetchRow($select);
    }

    public function getUserById(int $id): ?array
    {
        return $this->selectSingleRow('user', 'id', $id);
    }

    public function updateTokenLastSeen(UserToken $token): void
    {
        $this->getDb()->update('user_token', [
            'date_last_used' => $token->getDateLastUsed()->format('Y-m-d H:i:s')
        ], ['id=?' => $token->getId()]);
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

    public function getGroupsForUser(User $user): array
    {
        $select = $this->getDb()->select()->from('user_group', [])
            ->joinInner('group', 'user_group.group_id=group.id')
            ->where('user_group.user_id=?', $user->getId());
        return $this->getDb()->fetchAll($select);
    }

    public function saveUser(User $user): void
    {
        $id = $this->insertOrUpdate('user', 'id', $user->getId(), $user->getAllData());
        if ($id) {
            $user->setId($id);
        }
    }

    public function getUserByEmailAddress(string $emailAddress): ?array
    {
        return $this->selectSingleRow('user', 'email_address', $emailAddress);
    }
}
