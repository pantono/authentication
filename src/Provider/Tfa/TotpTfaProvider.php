<?php

namespace Pantono\Authentication\Provider\Tfa;

use Pantono\Authentication\Provider\AbstractAuthenticationProvider;
use Pantono\Authentication\Model\UserTfaMethod;
use Pantono\Authentication\Model\UserTfaAttempt;
use Pantono\Authentication\Model\TfaType;
use Pantono\Authentication\Model\User;
use OTPHP\TOTP;
use Pantono\Authentication\TwoFactorAuth;

class TotpTfaProvider extends AbstractTwoFactorAuthProvider
{
    private TwoFactorAuth $auth;

    public function __construct(TwoFactorAuth $auth)
    {
        $this->auth = $auth;
    }

    public function initiate(UserTfaMethod $method): UserTfaAttempt
    {
        $attempt = $this->createAttempt($method);
        $this->auth->saveAttempt($attempt);
        return $attempt;
    }

    public function verify(UserTfaAttempt $attempt, array $data): bool
    {
        if (!$attempt->getMethod()) {
            throw new \RuntimeException('Method not set');
        }
        $secret = $attempt->getMethod()->getConfigField('secret');
        if (!$secret) {
            throw new \RuntimeException('Invalid TOTP setup detected');
        }
        $otp = TOTP::createFromSecret($secret);
        if ($otp->verify($data['code'])) {
            $attempt->setVerified(true);
            return true;
        }
        return false;
    }

    public function setUp(TfaType $type, User $user, array $data = []): UserTfaMethod
    {
        $totp = TOTP::generate();
        $totp->setLabel($type->getConfigField('qr_label'));
        $totp->getProvisioningUri();
        $method = new UserTfaMethod();
        $method->setVerified(true);
        $method->setTfaType($type);
        $method->setDateCreated(new \DateTime);
        $method->setDeleted(false);
        $method->setEnabled(false);
        $method->setConfig([
            'secret' => $totp->getSecret(),
            'provision_url' => $totp->getProvisioningUri()
        ]);
        $this->auth->saveMethod($method);
        return $method;
    }

    public function verifySetup(UserTfaMethod $method, array $data): bool
    {
        $secret = $method->getConfigField('secret');
        if (!$secret) {
            throw new \RuntimeException('Invalid TOTP setup detected');
        }
        $otp = TOTP::createFromSecret($secret);
        if ($otp->verify($data['code'])) {
            $method->setVerified(true);
            $this->auth->saveMethod($method);
            return true;
        }
        return false;
    }

}
