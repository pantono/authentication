<?php

namespace Pantono\Authentication\VerificationController;

use Pantono\Email\Email;
use Pantono\Authentication\Model\UserVerification;
use Pantono\Email\EmailTemplates;

class SmsVerificationController extends AbstractVerificationController
{
    public function initiate(UserVerification $verification): void
    {
        throw new \RuntimeException('Not yet implemented');
    }

    public function verify(UserVerification $verification, array $data): void
    {
        throw new \RuntimeException('Not yet implemented');
    }
}
