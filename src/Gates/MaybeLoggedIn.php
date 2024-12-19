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

class MaybeLoggedIn implements SecurityGateInterface
{
    private SecurityContextInterface $securityContext;
    private Session $session;
    private UserAuthentication $authentication;

    public function __construct(SecurityContextInterface $securityContext, Session $session, UserAuthentication $authentication)
    {
        $this->securityContext = $securityContext;
        $this->session = $session;
        $this->authentication = $authentication;
    }

    public function isValid(Request $request, EndpointDefinitionInterface $endpoint, ParameterBag $options, ?Session $session = null): void
    {
        $userId = $this->session->get('user_id');
        if ($userId === null) {
            return;
        }
        $user = $this->authentication->getUserById($userId);
        if ($user === null) {
            return;
        }
        $this->securityContext->set('user', $user);
    }
}
