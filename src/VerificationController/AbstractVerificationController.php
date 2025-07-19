<?php

namespace Pantono\Authentication\VerificationController;

use Pantono\Authentication\Model\UserVerification;
use Pantono\Authentication\Verification;

abstract class AbstractVerificationController
{
    private Verification $verification;

    abstract public function initiate(UserVerification $verification): void;

    abstract public function verify(UserVerification $verification, array $data): void;

    public function getVerification(): Verification
    {
        return $this->verification;
    }

    public function setVerification(Verification $verification): void
    {
        $this->verification = $verification;
    }
}
