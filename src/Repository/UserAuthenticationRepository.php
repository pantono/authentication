<?php

namespace Pantono\Authentication\Repository;

use Pantono\Authentication\Model\UserToken;
use Pantono\Authentication\Model\LoginProviderUser;
use Pantono\Contracts\Locator\UserInterface;
use Pantono\Authentication\Model\LoginProvider;
use Pantono\Authentication\Model\UserPasswordReset;
use Pantono\Authentication\Model\LoginOneTimeLink;
use Pantono\Database\Repository\DefaultRepository;
use Pantono\Authentication\Filter\PasswordResetFilter;

class UserAuthenticationRepository extends DefaultRepository
{
    public function getUserByToken(string $token): ?array
    {
        $select = $this->getDb()->select('user.*')->from($this->pt('user_token'), 'u')
            ->innerJoin('u', 'user_token', 't', 'u.id=t.user_id')
            ->where('t.token=:token')
            ->setParameter('token', $token);

        return $this->getDb()->fetchRow($select);
    }

    public function getUserTokenByToken(string $token): ?array
    {
        return $this->selectSingleRow($this->pt('user_token'), 'token', $token);
    }

    public function saveToken(UserToken $token): void
    {
        $id = $this->insertOrUpdate($this->pt('user_token'), 'id', $token->getId(), $token->getAllData());
        if ($id) {
            $token->setId($id);
        }
    }

    public function getSocialProviderById(int $id): ?array
    {
        return $this->selectSingleRow($this->pt('login_provider'), 'id', $id);
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
        $select = $this->getDb()->select('lp.*')->from($this->pt('login_provider_user'), 'lp')
            ->innerJoin('lp', 'user', 'u', 'u.id=lp.user_id')
            ->where('lp.provider_id=:provider_id')
            ->where('lp.provider_user_id=:provider_user_id')
            ->setParameter('provider_id', $provider->getId())
            ->setParameter('provider_user_id', $providerUserId);

        return $this->getDb()->fetchRow($select);
    }

    public function updateTokenLastSeen(UserToken $token): void
    {
        $this->getDb()->update($this->pt('user_token'), [
            'date_last_used' => $token->getDateLastUsed()->format('Y-m-d H:i:s')
        ], ['id' => $token->getId()]);
    }

    public function addLogForProvider(?LoginProvider $provider, string $entry, ?string $ipAddress, ?int $userId, ?string $sessionId = null, ?array $data = null): void
    {
        $this->getDb()->insert($this->pt('authentication_log'), [
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
        return $this->selectSingleRow($this->pt('login_provider_type'), 'id', $id);
    }

    public function getUserTokenById(int $id): ?array
    {
        return $this->selectSingleRow($this->pt('user_token'), 'id', $id);
    }

    public function getLoginProviderUserById(int $id): ?array
    {
        return $this->selectSingleRow($this->pt('login_provider_user'), 'id', $id);
    }

    public function getPasswordResetByToken(string $token): ?array
    {
        return $this->selectSingleRow($this->pt('user_password_reset'), 'token', $token);
    }

    public function getPasswordResetById(int $id): ?array
    {
        return $this->selectSingleRow($this->pt('user_password_reset'), 'id', $id);
    }

    public function savePasswordReset(UserPasswordReset $passwordReset): void
    {
        $id = $this->insertOrUpdate($this->pt('user_password_reset'), 'id', $passwordReset->getId(), $passwordReset->getAllData());
        if ($id) {
            $passwordReset->setId($id);
        }
    }

    public function getPasswordResetsByFilter(PasswordResetFilter $filter): array
    {
        $select = $this->getDb()->select('upr.*')->from($this->pt('user_password_reset'), 'upr');

        if ($filter->getUser()) {
            $select->andWhere('upr.user_id=:user_id')
                ->setParameter('user_id', $filter->getUser()->getId());
        }
        if ($filter->getCompleted() !== null) {
            $select->andWhere('upr.completed=:completed')
                ->setParameter('completed', $filter->getCompleted() ? 1 : 0);
        }
        if ($filter->getDateCreatedStart() !== null) {
            $select->andWhere('date_created >= :date_created_start')
                ->set('date_created_start', $filter->getDateCreatedStart()->format('Y-m-d H:i:s'));
        }
        if ($filter->getDateCreatedEnd() !== null) {
            $select->andWhere('date_created <= :date_created_end')
                ->setParameter('date_created_end', $filter->getDateCreatedEnd()->format('Y-m-d H:i:s'));
        }
        if ($filter->getDateExpiresStart() !== null) {
            $select->andWhere('date_expires >= :date_expires_start')
                ->setParameter('date_expires_start', $filter->getDateExpiresStart()->format('Y-m-d H:i:s'));
        }
        if ($filter->getDateExpiresEnd() !== null) {
            $select->andWhere('date_expires <= :date_expires_end')
                ->setParameter('date_expires_end', $filter->getDateExpiresEnd()->format('Y-m-d H:i:s'));
        }
        $this->applyCountAndLimit($select, $filter);

        return $this->getDb()->fetchAll($select);
    }

    public function getOneTimeLinkByToken(string $token): ?array
    {
        return $this->selectSingleRow($this->pt('login_one_time_link'), 'token', $token);
    }

    public function getOneTimeLinkById(int $id): ?array
    {
        return $this->selectSingleRow($this->pt('login_one_time_link'), 'id', $id);
    }

    public function saveOneTimeLink(LoginOneTimeLink $link): void
    {
        $id = $this->insertOrUpdate($this->pt('login_one_time_link'), 'id', $link->getId(), $link->getAllData());
        if ($id) {
            $link->setId($id);
        }
    }
}
