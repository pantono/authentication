<?php

namespace Pantono\Authentication\Event;

use Pantono\Authentication\Model\User;
use Pantono\Authentication\Model\LoginProvider;
use Symfony\Component\HttpFoundation\Session\Session;

class PostUserLogoutEvent
{
    private User $user;
    private ?LoginProvider $provider = null;
    private ?Session $session = null;

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): void
    {
        $this->user = $user;
    }

    public function getProvider(): ?LoginProvider
    {
        return $this->provider;
    }

    public function setProvider(?LoginProvider $provider): void
    {
        $this->provider = $provider;
    }

    public function getSession(): ?Session
    {
        return $this->session;
    }

    public function setSession(?Session $session): void
    {
        $this->session = $session;
    }
}
