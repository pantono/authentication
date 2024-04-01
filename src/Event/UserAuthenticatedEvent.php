<?php

namespace Pantono\Authentication\Event;

use Symfony\Contracts\EventDispatcher\Event;
use Pantono\Authentication\Model\User;
use Pantono\Contracts\Security\SecurityContextInterface;

class UserAuthenticatedEvent extends Event
{
    private User $user;
    private SecurityContextInterface $securityContext;

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): void
    {
        $this->user = $user;
    }

    public function getSecurityContext(): SecurityContextInterface
    {
        return $this->securityContext;
    }

    public function setSecurityContext(SecurityContextInterface $securityContext): void
    {
        $this->securityContext = $securityContext;
    }
}
