<?php

namespace Pantono\Authentication\Events;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Pantono\Authentication\Users;
use Pantono\Authentication\Event\PostUserSaveEvent;
use Pantono\Contracts\Security\SecurityContextInterface;
use Pantono\Authentication\Model\User;

class UserEvents implements EventSubscriberInterface
{
    private Users $users;
    private SecurityContextInterface $securityContext;

    public function __construct(Users $users, SecurityContextInterface $securityContext)
    {
        $this->users = $users;
        $this->securityContext = $securityContext;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            PostUserSaveEvent::class => [
                ['saveHistory', -255]
            ]
        ];
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
                    $this->users->addHistoryForUser($current, 'Changed field ' . $key . ' from ' . $previous->getFieldByName($key) ?? 'N/A' . ' to ' . $value, $this->getLoggedInUser());
                }
            }

            foreach ($previous->getFlatFields() as $key => $value) {
                if ($current->getFieldByName($key) === null && in_array($key, $doneFields) === false) {
                    $this->users->addHistoryForUser($current, 'Changed field' . $key . ' from ' . $value . ' to ' . $current->getFieldByName($key) ?? 'N/A', $this->getLoggedInUser());
                }
            }
        }
    }

    public function getLoggedInUser(): ?User
    {
        return $this->securityContext->get('user');
    }
}
