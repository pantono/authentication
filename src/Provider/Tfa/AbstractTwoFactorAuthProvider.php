<?php

namespace Pantono\Authentication\Provider\Tfa;

use Pantono\Authentication\Model\UserTfaMethod;
use Pantono\Authentication\Model\UserTfaAttempt;
use Pantono\Authentication\Model\TfaType;
use Pantono\Authentication\Model\User;
use Pantono\Utilities\StringUtilities;

abstract class AbstractTwoFactorAuthProvider
{
    abstract public function initiate(UserTfaMethod $method): UserTfaAttempt;

    abstract public function verify(UserTfaAttempt $attempt, array $data): bool;

    abstract public function setUp(TfaType $type, User $user, array $data = []): UserTfaMethod;

    abstract public function verifySetup(UserTfaMethod $method, array $data): bool;

    public function createAttempt(UserTfaMethod $method): UserTfaAttempt
    {
        $attempt = new UserTfaAttempt();
        $attempt->setMethod($method);
        $attempt->setDateCreated(new \DateTime);
        $attempt->setAttemptCode(StringUtilities::generateRandomString());
        $attempt->setAttemptSlug(StringUtilities::generateRandomString(20));
        $attempt->setDateExpires(new \DateTimeImmutable('+1 hour'));
        $attempt->setVerified(false);
        return $attempt;
    }
}

