<?php

namespace Pantono\Authentication\Event;

use Symfony\Contracts\EventDispatcher\Event;
use Pantono\Authentication\Model\UserPasswordReset;

abstract class AbstractUserPasswordResetSaveEvent extends Event
{
    private UserPasswordReset $current;
    private ?UserPasswordReset $previous = null;

    public function getCurrent(): UserPasswordReset
    {
        return $this->current;
    }

    public function setCurrent(UserPasswordReset $current): void
    {
        $this->current = $current;
    }

    public function getPrevious(): ?UserPasswordReset
    {
        return $this->previous;
    }

    public function setPrevious(?UserPasswordReset $previous): void
    {
        $this->previous = $previous;
    }
}
