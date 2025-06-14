<?php

namespace Pantono\Authentication\Event;

use Symfony\Contracts\EventDispatcher\Event;
use Pantono\Authentication\Model\User;

abstract class AbstractUserEvent extends Event
{
    private User $current;
    private ?User $previous = null;

    public function getCurrent(): User
    {
        return $this->current;
    }

    public function setCurrent(User $current): void
    {
        $this->current = $current;
    }

    public function getPrevious(): ?User
    {
        return $this->previous;
    }

    public function setPrevious(?User $previous): void
    {
        $this->previous = $previous;
    }
}
