<?php

namespace Pantono\Authentication\Provider\Tfa;

use Pantono\Email\Email;
use Pantono\Authentication\TwoFactorAuth;
use Pantono\Authentication\Model\UserTfaMethod;
use Pantono\Authentication\Model\UserTfaAttempt;
use Pantono\Utilities\StringUtilities;
use DateTimeImmutable;
use Pantono\Authentication\Model\TfaType;
use Pantono\Authentication\Model\User;
use Pantono\Email\EmailTemplates;

class EmailTfaProvider extends AbstractTwoFactorAuthProvider
{
    private Email $email;
    private TwoFactorAuth $auth;
    private EmailTemplates $templates;

    public function __construct(Email $email, TwoFactorAuth $auth, EmailTemplates $templates)
    {
        $this->email = $email;
        $this->auth = $auth;
        $this->templates = $templates;
    }

    public function initiate(UserTfaMethod $method): UserTfaAttempt
    {
        if (!$method->getTfaType()) {
            throw new \RuntimeException('TFA type is required for email Two Factor Auth');
        }
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
            throw new \RuntimeException('Invalid TFA setup detected');
        }
        $toAddress = $method->getUser()->getEmailAddress();
        if ($method->getTfaType()->getConfigField('custom_email') === true) {
            if ($method->getConfigField('email') && filter_var($method->getConfigField('email'), FILTER_VALIDATE_EMAIL)) {
                $toAddress = $method->getConfigField('email');
            }
        }

        $template = $this->templates->getTemplateForType('tfa');
        if (!$template) {
            throw new \RuntimeException('No e-mail template set for two factor auth');
        }
        $this->email->createMessage()->to($toAddress, $method->getUser()->getName())
            ->addVariable('attempt', $attempt)
            ->subject('Your verification code is ' . $attempt->getAttemptCode())
            ->setTemplate($template)->send();
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
