<?php

namespace Pantono\Authentication\VerificationController;

use Pantono\Email\Email;
use Pantono\Authentication\Model\UserVerification;
use Pantono\Email\EmailTemplates;

class EmailVerificationController extends AbstractVerificationController
{
    private Email $email;
    private EmailTemplates $templates;

    public function __construct(Email $email, EmailTemplates $templates)
    {
        $this->email = $email;
        $this->templates = $templates;
    }

    public function initiate(UserVerification $verification): void
    {
        $verification->setCredential($verification->getUser()->getEmailAddress());
        $template = $this->templates->getTemplateForType('email_verification');
        if (!$template) {
            throw new \RuntimeException('E-mail verification template not set');
        }
        $content = $this->templates->renderTemplate($template, ['verification' => $verification]);
        $message = $this->email->createMessage()
            ->setRenderedHtml($content)
            ->to($verification->getUser()->getEmailAddress(), $verification->getUser()->getName())
            ->subject('Please verify your e-mail address');

        $this->email->sendEmail($message);
    }

    public function verify(UserVerification $verification, array $data): void
    {
        if ($verification->getDateExpires() <= new \DateTimeImmutable()) {
            throw new \RuntimeException('Verification has expired, please try again');
        }
        if ($data['code'] === $verification->getCode()) {
            $verification->setVerified(true);
            $this->getVerification()->addHistoryToVerification($verification, 'Verified email address ' . $verification->getCredential());
            return;
        }
        $this->getVerification()->addHistoryToVerification($verification, 'Invalid verification code provided for e-mail ' . $verification->getCredential());
    }
}
