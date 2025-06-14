<?php

namespace Pantono\Authentication\Gates;

use Pantono\Contracts\Security\Gate\SecurityGateInterface;
use Symfony\Component\HttpFoundation\Request;
use Pantono\Contracts\Endpoint\EndpointDefinitionInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\ParameterBag;
use Pantono\Contracts\Security\SecurityContextInterface;
use Pantono\Authentication\Exception\AccessDeniedException;
use Pantono\Authentication\UserAuthentication;
use Pantono\Authentication\Users;

class NotLoggedIn implements SecurityGateInterface
{
    private Session $session;

    public function __construct(Session $session)
    {
        $this->session = $session;
    }

    public function isValid(Request $request, EndpointDefinitionInterface $endpoint, ParameterBag $options, ?Session $session = null): void
    {
        $userId = $this->session->get('user_id');
        if ($userId !== null) {
            throw new AccessDeniedException('You are not supposed to be logged in');
        }
    }
}
