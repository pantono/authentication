<?php

namespace Pantono\Authentication\Events;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Pantono\Authentication\Users;
use Pantono\Authentication\Event\PostUserSaveEvent;
use Pantono\Contracts\Security\SecurityContextInterface;
use Pantono\Authentication\Model\User;
use Pantono\Queue\QueueManager;
use Pantono\Authentication\Event\PreUserSaveEvent;
use Pantono\Email\Email;
use Pantono\Utilities\StringUtilities;

class UserEvents implements EventSubscriberInterface
{
    private Users $users;
    private SecurityContextInterface $securityContext;
    private QueueManager $queueManager;
    private Email $email;

    public function __construct(Users $users, SecurityContextInterface $securityContext, QueueManager $queueManager, Email $email)
    {
        $this->users = $users;
        $this->securityContext = $securityContext;
        $this->queueManager = $queueManager;
        $this->email = $email;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            PostUserSaveEvent::class => [
                ['saveHistory', -255],
                ['createQueueTask', -255]
            ],
            PreUserSaveEvent::class => [
                ['checkPassword', 255]
            ]
        ];
    }

    public function checkPassword(PreUserSaveEvent $event): void
    {
        if (!$event->getPrevious() && !$event->getCurrent()->getPassword()) {
            $message = $this->email->createMessageForType('new_password');
            if ($message) {
                $password = StringUtilities::generateRandomString(10);
                $event->getCurrent()->setPassword(password_hash($password, PASSWORD_DEFAULT));
                $message->setVariables(['user' => $event->getCurrent(), 'password' => $password])
                    ->subject('Your new password')
                    ->to($event->getCurrent()->getEmailAddress(), $event->getCurrent()->getForename() . ' ' . $event->getCurrent()->getSurname());

                $message->send();
            }
        }
    }

    public function saveHistory(PostUserSaveEvent $event): void
    {
        $previous = $event->getPrevious();
        $current = $event->getCurrent();
        if (!$previous) {
            $this->users->addHistoryForUser($current, 'Added new user record', $this->getLoggedInUser());
        } else {
            if ($previous->getSurname() !== $current->getSurname()) {
                $this->users->addHistoryForUser($current, 'Changed surname from ' . $previous->getSurname() . ' to ' . $current->getSurname(), $this->getLoggedInUser());
            }
            if ($previous->getForename() !== $current->getForename()) {
                $this->users->addHistoryForUser($current, 'Changed forename from ' . $previous->getForename() . ' to ' . $current->getForename(), $this->getLoggedInUser());
            }
            if ($previous->getEmailAddress() !== $current->getEmailAddress()) {
                $this->users->addHistoryForUser($current, 'Changed email address from ' . $previous->getEmailAddress() . ' to ' . $current->getEmailAddress(), $this->getLoggedInUser());
            }
            foreach ($current->getPermissions() as $permission) {
                if ($previous->hasPermission($permission->getName()) === false) {
                    $this->users->addHistoryForUser($current, 'Added permission ' . $permission->getName(), $this->getLoggedInUser());
                }
            }
            foreach ($previous->getPermissions() as $permission) {
                if ($current->hasPermission($permission->getName()) === false) {
                    $this->users->addHistoryForUser($current, 'Removed permission ' . $permission->getName(), $this->getLoggedInUser());
                }
            }
            foreach ($current->getGroups() as $group) {
                if ($previous->hasGroup($group->getName()) === false) {
                    $this->users->addHistoryForUser($current, 'Added group ' . $group->getName(), $this->getLoggedInUser());
                }
            }
            foreach ($previous->getGroups() as $group) {
                if ($current->hasGroup($group->getName()) === false) {
                    $this->users->addHistoryForUser($current, 'Removed group ' . $group->getName(), $this->getLoggedInUser());
                }
            }
            $doneFields = [];
            foreach ($current->getFlatFields() as $key => $value) {
                if ($previous->getFieldByName($key) !== $value) {
                    $doneFields[] = $key;
                    $prevValue = $previous->getFieldByName($key);
                    if (!$prevValue) {
                        $prevValue = 'N/A';
                    }
                    $this->users->addHistoryForUser($current, 'Changed field ' . $key . ' from ' . $prevValue . ' to ' . $value, $this->getLoggedInUser());
                }
            }

            foreach ($previous->getFlatFields() as $key => $value) {
                if ($current->getFieldByName($key) === null && in_array($key, $doneFields) === false) {
                    $currentValue = $current->getFieldByName($key);
                    if ($currentValue === null) {
                        $currentValue = 'N/A';
                    }
                    $this->users->addHistoryForUser($current, 'Changed field' . $key . ' from ' . $value . ' to ' . $currentValue, $this->getLoggedInUser());
                }
            }
        }
    }


    public function createQueueTask(PostUserSaveEvent $event): void
    {
        if ($event->getPrevious() === null) {
            $this->queueManager->createTask('user_create', ['user' => $event->getCurrent()->getAllData()]);
            return;
        }
        $this->queueManager->createTask('user_update', ['user' => $event->getCurrent()->getAllData(), 'previous' => $event->getPrevious()->getAllData()]);
    }

    public function getLoggedInUser(): ?User
    {
        return $this->securityContext->get('user');
    }

}
