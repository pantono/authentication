<?php

namespace Pantono\Authentication\Provider\Tfa;

use Pantono\Email\Email;
use Pantono\Authentication\TwoFactorAuth;
use Pantono\Authentication\Model\UserTfaMethod;
use Pantono\Authentication\Model\UserTfaAttempt;
use Pantono\Utilities\StringUtilities;
use DateTimeImmutable;
use DateTime;
use Pantono\Authentication\Model\TfaType;
use Pantono\Authentication\Model\User;

class EmailTfaProvider extends AbstractTwoFactorAuthProvider
{
    private Email $email;
    private TwoFactorAuth $auth;

    public function __construct(Email $email, TwoFactorAuth $auth)
    {
        $this->email = $email;
        $this->auth = $auth;
    }

    public function initiate(UserTfaMethod $method): UserTfaAttempt
    {
        $attempt = $this->createAttempt($method);
        $expiryTimeStr = $method->getConfigField('expiry_time');
        if ($expiryTimeStr) {
            try {
                $expiryTime = new DateTimeImmutable($expiryTimeStr);
                $attempt->setDateExpires($expiryTime);
            } catch (\Exception $e) {
            }
        }

        $this->auth->saveAttempt($attempt);

        if (!$method->getUser()) {
            throw new \RuntimeException('Invalid TOTP setup detected');
        }
        $toAddress = $method->getUser()->getEmailAddress();
        if ($method->getTfaType()->getConfigField('custom_email') === true) {
            if ($method->getConfigField('email') && filter_var($method->getConfigField('email'), FILTER_VALIDATE_EMAIL)) {
                $toAddress = $method->getConfigField('email');
            }
        }

        $copy = $method->getTfaType()->getConfig()['email_copy'] ?? null;
        if (!$copy) {
            throw new \RuntimeException('Invalid config for email provider, missing copy');
        }
        $message = $this->email->createMessage();
        $message->to($toAddress, $method->getUser()->getName())->setRenderedHtml($copy);
        $this->email->sendEmail($message);

        $this->auth->addLogToAttempt($attempt, 'Email sent to ' . $toAddress);

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
        $method = new UserTfaMethod();
        $method->setVerified(true);
        $method->setTfaType($type);
        $method->setDateCreated(new \DateTime);
        $method->setDeleted(false);
        $method->setEnabled(false);
        $config = [
            'setup_code' => StringUtilities::generateRandomString(6)
        ];
        if ($type->getConfigField('custom_email')) {
            if (isset($data['email'])) {
                $config['email'] = $user->getEmailAddress();
            }
        }
        $method->setConfig($config);
        if ($type->getConfigField('verification_required')) {
            $copy = $type->getConfig()['email_verification_copy'] ?? null;
            if (!$copy) {
                throw new \RuntimeException('Invalid email tfa setup, missing email_verifcation_copy');
            }
            $method->setVerified(false);
            $toAddress = $config['email'] ?? $user->getEmailAddress();
            $message = $this->email->createMessage();
            $message->to($toAddress, $user->getName());
            $message->setRenderedHtml($copy);
            $this->email->sendEmail($message);
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
}
