<?php

namespace Pantono\Authentication\Repository;

use Pantono\Database\Repository\MysqlRepository;
use Pantono\Authentication\Model\UserToken;
use Pantono\Authentication\Model\LoginProviderUser;
use Pantono\Contracts\Locator\UserInterface;
use Pantono\Authentication\Model\LoginProvider;

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

    public function saveSocialLogin(LoginProviderUser $socialLogin): void
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

    public function addLogForProvider(LoginProvider $provider, string $entry, ?string $ipAddress, ?int $userId, ?string $sessionId = null, ?array $data = null): void
    {
        $this->getDb()->insert('authentication_log', [
            'provider_id' => $provider->getId(),
            'entry' => $entry,
            'ip_address' => $ipAddress,
            'user_id' => $userId,
            'session_id' => $sessionId,
            'data' => json_encode($data)
        ]);
    }
}
