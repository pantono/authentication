<?php

namespace Pantono\Authentication\Provider\Tfa;

use Pantono\Authentication\Provider\AbstractAuthenticationProvider;
use Pantono\Authentication\Model\TfaType;
use Pantono\Authentication\Model\User;
use Pantono\Authentication\Model\UserTfaMethod;
use Pantono\Authentication\Model\UserTfaAttempt;
use Twilio\Rest\Client;
use Pantono\Authentication\TwoFactorAuth;
use Pantono\Utilities\StringUtilities;

class SmsTfaProvider extends AbstractTwoFactorAuthProvider
{
    private TwoFactorAuth $auth;

    public function __construct(TwoFactorAuth $auth)
    {
        $this->auth = $auth;
    }

    public function initiate(UserTfaMethod $method): UserTfaAttempt
    {
        $attempt = $this->createAttempt($method);
        $number = $method->getConfigField('number');
        if (!$number) {
            throw new \RuntimeException('Phone number is required for SMS Two Factor Auth');
        }
        if (!$method->getTfaType()) {
            throw new \RuntimeException('TFA type is required for SMS Two Factor Auth');
        }
        $this->auth->saveAttempt($attempt);
        if (!$fromNumber = $method->getTfaType()->getConfigField('from_number')) {
            throw new \RuntimeException('From number is required for SMS Two Factor Auth');
        }
        $this->createClient($method->getTfaType())->messages->create($number, [
            'from' => $fromNumber,
            'body' => 'Your verification code is ' . $attempt->getAttemptCode()
        ]);
        $this->auth->addLogToAttempt($attempt, 'SMS sent to ' . $number);
        return $attempt;
    }

    public function verify(UserTfaAttempt $attempt, array $data): bool
    {
        $code = $data['code'] ?? null;
        if ($attempt->getAttemptSecret() === $code) {
            $this->auth->completeTwoFactorAuth($attempt);
            return true;
        }
        return false;
    }

    public function setUp(TfaType $type, User $user, array $data = []): UserTfaMethod
    {
        if (!isset($data['phone_number'])) {
            throw new \RuntimeException('Phone number is required to setup SMS Two Factor Auth');
        }
        $method = new UserTfaMethod();
        $method->setVerified(true);
        $method->setTfaType($type);
        $method->setDateCreated(new \DateTime);
        $method->setDeleted(false);
        $method->setEnabled(false);
        $config = [
            'setup_code' => StringUtilities::generateRandomString(6),
            'number' => $data['phone_number']
        ];
        $method->setConfig($config);
        if ($type->getConfigField('verification_required') === false) {
            $method->setVerified(true);
        } else {
            $this->createClient($type)->messages->create($type->getConfigField('from_number'), [
                'from' => $type->getConfigField('from_number'),
                'body' => 'Your verification code is ' . $config['setup_code']
            ]);
        }
        $this->auth->saveMethod($method);
        return $method;
    }

    public function verifySetup(UserTfaMethod $method, array $data): bool
    {
        if ($method->getConfigField('setup_code') === $data['code']) {
            $method->setVerified(true);
            $method->setDateLastUsed(new \DateTime);
            $this->auth->saveMethod($method);
            return true;
        }
        return false;
    }

    private function createClient(TfaType $type): Client
    {
        return new \Twilio\Rest\Client($type->getConfigField('sid'), $type->getConfigField('token'));
    }
}
