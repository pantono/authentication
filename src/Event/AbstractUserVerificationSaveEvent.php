<?php

namespace Pantono\Authentication\Event;

use Symfony\Contracts\EventDispatcher\Event;
use Pantono\Authentication\Model\UserVerification;

abstract class AbstractUserVerificationSaveEvent extends Event
{
    private UserVerification $current;
    private ?UserVerification $previous = null;

    public function getCurrent(): UserVerification
    {
        return $this->current;
    }

    public function setCurrent(UserVerification $current): void
    {
        $this->current = $current;
    }

    public function getPrevious(): ?UserVerification
    {
        return $this->previous;
    }

    public function setPrevious(?UserVerification $previous): void
    {
        $this->previous = $previous;
    }
}
