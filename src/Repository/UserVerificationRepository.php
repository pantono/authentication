<?php

namespace Pantono\Authentication\Repository;

use Pantono\Database\Repository\MysqlRepository;
use Pantono\Authentication\Model\User;
use Pantono\Authentication\Model\UserVerification;

class UserVerificationRepository extends MysqlRepository
{
    public function getVerificationsForUser(User $user): ?array
    {
        return $this->selectRowsByValues('user_verification', ['user_id' => $user->getId()]);
    }

    public function getVerificationTypeById(int $id): ?array
    {
        return $this->selectSingleRow('user_verification_type', 'id', $id);
    }

    public function getAllVerificationTypes(): array
    {
        return $this->selectAll('user_verification_type');
    }

    public function getVerificationByCode(string $code): ?array
    {
        return $this->selectSingleRow('user_verification', 'code', $code);
    }

    public function getVerificationById(int $id): ?array
    {
        return $this->selectSingleRow('user_verification', 'id', $id);
    }

    public function saveVerification(UserVerification $verification): void
    {
        $id = $this->insertOrUpdate('user_verification', 'id', $verification->getId(), $verification->getAllData());
        if ($id) {
            $verification->setId($id);
        }
    }

    public function getVerificationByToken(string $token): ?array
    {
        return $this->selectSingleRow('user_verification', 'token', $token);
    }

    public function addHistoryToVerification(UserVerification $verification, string $entry): void
    {
        $this->getDb()->insert('user_verification_log', [
            'verification_id' => $verification->getId(),
            'date' => (new \DateTime())->format('Y-m-d H:i:s'),
            'entry' => $entry
        ]);
    }
}
