<?php

namespace Pantono\Authentication\Event;

use Symfony\Contracts\EventDispatcher\Event;
use Pantono\Authentication\Model\User;
use Pantono\Authentication\Model\LoginProvider;
use Pantono\Authentication\Model\UserTfaAttempt;
use Symfony\Component\HttpFoundation\Session\Session;

class PostUserLoginEvent extends Event
{
    private User $user;
    private LoginProvider $provider;
    private ?UserTfaAttempt $tfaAttempt = null;
    private ?Session $session = null;

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): void
    {
        $this->user = $user;
    }

    public function getProvider(): LoginProvider
    {
        return $this->provider;
    }

    public function setProvider(LoginProvider $provider): void
    {
        $this->provider = $provider;
    }

    public function getTfaAttempt(): ?UserTfaAttempt
    {
        return $this->tfaAttempt;
    }

    public function setTfaAttempt(?UserTfaAttempt $tfaAttempt): void
    {
        $this->tfaAttempt = $tfaAttempt;
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
