<?php

namespace Pantono\Authentication\Event;

use Symfony\Contracts\EventDispatcher\Event;
use Pantono\Authentication\Model\LoginOneTimeLink;

abstract class AbstractLoginOneTimeLinkEvent extends Event
{
    private LoginOneTimeLink $current;
    private ?LoginOneTimeLink $previous = null;

    public function getCurrent(): LoginOneTimeLink
    {
        return $this->current;
    }

    public function setCurrent(LoginOneTimeLink $current): void
    {
        $this->current = $current;
    }

    public function getPrevious(): ?LoginOneTimeLink
    {
        return $this->previous;
    }

    public function setPrevious(?LoginOneTimeLink $previous): void
    {
        $this->previous = $previous;
    }
}
