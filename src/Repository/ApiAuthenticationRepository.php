<?php

namespace Pantono\Authentication\Repository;

use Pantono\Database\Repository\MysqlRepository;
use Pantono\Authentication\Model\ApiToken;

class ApiAuthenticationRepository extends MysqlRepository
{
    public function getApiTokenByToken(string $token): ?array
    {
        return $this->getDb()->fetchRow($this->getDb()->select()->from('api_token')->where('token=?', $token));
    }

    public function updateApiTokenLastSeen(ApiToken $token): void
    {
        $this->getDb()->update('api_token', ['last_used' => $token->getDateLastUsed()->format('Y-m-d H:i:s')], ['id=?' => $token->getId()]);
    }

    public function saveToken(ApiToken $token): void
    {
        $id = $this->insertOrUpdate('api_token', 'id', $token->getId(), $token->getAllData());
        if ($id) {
            $token->setId($id);
        }
    }

    public function getTokenById(int $id): ?array
    {
        return $this->selectSingleRow('api_token', 'id', $id);
    }
}
