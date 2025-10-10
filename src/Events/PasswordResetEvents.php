<?php

namespace Pantono\Authentication\Events;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Pantono\Authentication\Event\PostUserPasswordResetSaveSaveEvent;
use Pantono\Email\Email;
use Pantono\Email\EmailTemplates;
use Pantono\Authentication\UserAuthentication;
use Pantono\Authentication\Users;

class PasswordResetEvents implements EventSubscriberInterface
{
    private Email $email;
    private EmailTemplates $templates;
    private UserAuthentication $authentication;
    private Users $users;

    public function __construct(Email $email, EmailTemplates $templates, UserAuthentication $authentication, Users $users)
    {
        $this->email = $email;
        $this->templates = $templates;
        $this->authentication = $authentication;
        $this->users = $users;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            PostUserPasswordResetSaveSaveEvent::class => [
                ['sendEmail', -255],
                ['addUserHistory', -255],
                ['expirePrevious', -255]
            ]
        ];
    }

    public function expirePrevious(PostUserPasswordResetSaveSaveEvent $event): void
    {
        if (!$event->getPrevious()) {
            $this->authentication->expirePreviousPasswordResets($event->getCurrent());
        }
    }

    public function sendEmail(PostUserPasswordResetSaveSaveEvent $event): void
    {
        if (!$event->getPrevious()) {
            $template = $this->templates->getTemplateForType('password_reset');
            if ($template && $event->getCurrent()->getUser()) {
                $this->email->createMessage()
                    ->addVariable('reset', $event->getCurrent())
                    ->setTemplate($template)
                    ->subject('Your password reset')
                    ->to($event->getCurrent()->getUser()->getEmailAddress(), $event->getCurrent()->getUser()->getFullName())
                    ->send();
            }
        }
    }

    public function addUserHistory(PostUserPasswordResetSaveSaveEvent $event): void
    {
        if (!$event->getCurrent()->getUser()) {
            return;
        }
        if ($event->getPrevious() === null) {
            $this->users->addHistoryForUser($event->getCurrent()->getUser(), 'Initiated new password reset');
            return;
        }

        if ($event->getPrevious()->getDateExpires()->format('Y-m-d H:i:s') !== $event->getCurrent()->getDateExpires()->format('Y-m-d H:i:s')) {
            $this->users->addHistoryForUser($event->getCurrent()->getUser(), 'Changed password reset expiry date from ' . $event->getPrevious()->getDateExpires()->format('d/m/Y H:i:s') . ' to ' . $event->getCurrent()->getDateExpires()->format('d/m/Y H:i:s'));
        }
    }
}
