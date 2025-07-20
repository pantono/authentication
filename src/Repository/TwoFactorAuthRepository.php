<?php

namespace Pantono\Authentication\Repository;

use Pantono\Database\Repository\MysqlRepository;
use Pantono\Authentication\Model\User;
use Pantono\Authentication\Model\UserTfaMethod;
use Pantono\Authentication\Model\UserTfaAttempt;

class TwoFactorAuthRepository extends MysqlRepository
{
    public function getTypeById(int $id): ?array
    {
        return $this->selectSingleRow('tfa_type', 'id', $id);
    }

    public function getTypes(): array
    {
        return $this->selectAll('tfa_type', 'name');
    }

    public function getMethodsForUser(User $user): array
    {
        return $this->selectRowsByValues('user_tfa_method', ['user_id' => $user->getId(), 'deleted' => 0]);
    }

    public function saveUserTfaMethod(UserTfaMethod $method): void
    {
        $id = $this->insertOrUpdateCheck('user_tfa_method', 'id', $method->getId(), $method->getAllData());
        if ($id) {
            $method->setId($id);
        }
    }

    public function getAttemptById(int $id): ?array
    {
        return $this->selectSingleRow('user_tfa_attempt', 'id', $id);
    }

    public function saveAttempt(UserTfaAttempt $attempt): void
    {
        $id = $this->insertOrUpdateCheck('user_tfa_attempt', 'id', $attempt->getId(), $attempt->getAllData());
        if ($id) {
            $attempt->setId($id);
        }
    }

    public function addLogToAttempt(UserTfaAttempt $attempt, string $entry): void
    {
        $this->getDb()->insert('user_tfa_attempt_log', [
            'attempt_id' => $attempt->getId(),
            'date' => (new \DateTime())->format('Y-m-d H:i:s'),
            'entry' => $entry
        ]);
    }

    public function getUserMethodById(int $id): ?array
    {
        return $this->selectSingleRow('user_tfa_method', 'id', $id);
    }

    public function getAttemptBySlug(int $id): ?array
    {
        return $this->selectSingleRow('user_tfa_attempt', 'secret', $id);
    }
}
