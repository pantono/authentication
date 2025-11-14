<?php

namespace Pantono\Authentication\Repository;

use Pantono\Database\Repository\MysqlRepository;
use Pantono\Authentication\Model\UserToken;
use Pantono\Authentication\Model\LoginProviderUser;
use Pantono\Contracts\Locator\UserInterface;
use Pantono\Authentication\Model\LoginProvider;
use Pantono\Authentication\Model\UserPasswordReset;
use Pantono\Authentication\Model\User;
use Pantono\Authentication\Model\LoginOneTimeLink;

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

    public function saveToken(UserToken $token): void
    {
        $id = $this->insertOrUpdate('user_token', 'id', $token->getId(), $token->getAllData());
        if ($id) {
            $token->setId($id);
        }
    }

    public function getSocialProviderById(int $id): ?array
    {
        return $this->selectSingleRow('login_provider', 'id', $id);
    }

    public function getSocialLoginsForUser(UserInterface $user): array
    {
        return $this->selectRowsByValues('login_provider_user', ['user_id' => $user->getId()]);
    }

    public function saveLoginProviderUser(LoginProviderUser $socialLogin): void
    {
        $data = $socialLogin->getAllData();
        $id = $this->insertOrUpdate('login_provider_user', 'id', $socialLogin->getId(), $data);
        if ($id) {
            $socialLogin->setId($id);
        }
    }

    public function getUserByProviderLogin(LoginProvider $provider, string $providerUserId): ?array
    {
        $select = $this->getDb()->select()->from('login_provider_user', [])
            ->joinInner('user', 'user.id=login_provider_user.user_id')
            ->where('login_provider_user.provider_id=?', $provider->getId())
            ->where('login_provider_user.provider_user_id=?', $providerUserId);

        return $this->getDb()->fetchRow($select);
    }

    public function updateTokenLastSeen(UserToken $token): void
    {
        $this->getDb()->update('user_token', [
            'date_last_used' => $token->getDateLastUsed()->format('Y-m-d H:i:s')
        ], ['id=?' => $token->getId()]);
    }

    public function addLogForProvider(?LoginProvider $provider, string $entry, ?string $ipAddress, ?int $userId, ?string $sessionId = null, ?array $data = null): void
    {
        $this->getDb()->insert('authentication_log', [
            'provider_id' => $provider?->getId(),
            'date' => (new \DateTime())->format('Y-m-d H:i:s'),
            'entry' => $entry,
            'ip_address' => $ipAddress,
            'user_id' => $userId,
            'session_id' => $sessionId,
            'data' => json_encode($data)
        ]);
    }

    public function getProviderTypeById(int $id): ?array
    {
        return $this->selectSingleRow('login_provider_type', 'id', $id);
    }

    public function getUserTokenById(int $id): ?array
    {
        return $this->selectSingleRow('user_token', 'id', $id);
    }

    public function getLoginProviderUserById(int $id): ?array
    {
        return $this->selectSingleRow('login_provider_user', 'id', $id);
    }

    public function getPasswordResetByToken(string $token): ?array
    {
        return $this->selectSingleRow('user_password_reset', 'token', $token);
    }

    public function getPasswordResetById(int $id): ?array
    {
        return $this->selectSingleRow('user_password_reset', 'id', $id);
    }

    public function savePasswordReset(UserPasswordReset $passwordReset): void
    {
        $id = $this->insertOrUpdate('user_password_reset', 'id', $passwordReset->getId(), $passwordReset->getAllData());
        if ($id) {
            $passwordReset->setId($id);
        }
    }

    public function getPasswordResetsByFilter(\Pantono\Authentication\Filter\PasswordResetFilter $filter): array
    {
        $select = $this->getDb()->select()->from('user_password_reset');

        if ($filter->getUser()) {
            $select->where('user_id=?', $filter->getUser()->getId());
        }
        if ($filter->getCompleted() !== null) {
            $select->where('completed=?', $filter->getCompleted() ? 1 : 0);
        }
        if ($filter->getDateCreatedStart() !== null) {
            $select->where('date_created >= ?', $filter->getDateCreatedStart()->format('Y-m-d H:i:s'));
        }
        if ($filter->getDateCreatedEnd() !== null) {
            $select->where('date_created <= ?', $filter->getDateCreatedEnd()->format('Y-m-d H:i:s'));
        }
        if ($filter->getDateExpiresStart() !== null) {
            $select->where('date_expires >= ?', $filter->getDateExpiresStart()->format('Y-m-d H:i:s'));
        }
        if ($filter->getDateExpiresEnd() !== null) {
            $select->where('date_expires <= ?', $filter->getDateExpiresEnd()->format('Y-m-d H:i:s'));
        }
        $filter->setTotalResults($this->getCount($select));
        $select->limitPage($filter->getPage(), $filter->getPerPage());

        return $this->getDb()->fetchAll($select);
    }

    public function getOneTimeLinkByToken(string $token): ?array
    {
        return $this->selectSingleRow('login_one_time_link', 'token', $token);
    }

    public function getOneTimeLinkById(int $id): ?array
    {
        return $this->selectSingleRow('login_one_time_link', 'id', $id);
    }

    public function saveOneTimeLink(LoginOneTimeLink $link): void
    {
        $id = $this->insertOrUpdate('login_one_time_link', 'id', $link->getId(), $link->getAllData());
        if ($id) {
            $link->setId($id);
        }
    }
}
