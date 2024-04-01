<?php

namespace Pantono\Authentication\Event;

use Symfony\Contracts\EventDispatcher\Event;
use Pantono\Authentication\Model\ApiToken;
use Pantono\Contracts\Security\SecurityContextInterface;

class ApiTokenAuthenticatedEvent extends Event
{
    private ApiToken $token;
    private SecurityContextInterface $context;

    public function getToken(): ApiToken
    {
        return $this->token;
    }

    public function setToken(ApiToken $token): void
    {
        $this->token = $token;
    }

    public function getContext(): SecurityContextInterface
    {
        return $this->context;
    }

    public function setContext(SecurityContextInterface $context): void
    {
        $this->context = $context;
    }
}
