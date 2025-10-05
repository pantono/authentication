<?php

namespace Pantono\Authentication\Events;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Pantono\Authentication\Event\PostUserPasswordResetSaveSaveEvent;
use Pantono\Email\Email;
use Pantono\Email\EmailTemplates;

class PasswordResetEvents implements EventSubscriberInterface
{
    private Email $email;
    private EmailTemplates $templates;

    public function __construct(Email $email, EmailTemplates $templates)
    {
        $this->email = $email;
        $this->templates = $templates;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            PostUserPasswordResetSaveSaveEvent::class => [
                ['sendEmail', -255]
            ]
        ];
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
}
