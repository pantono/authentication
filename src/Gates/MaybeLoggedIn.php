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

class MaybeLoggedIn implements SecurityGateInterface
{
    private SecurityContextInterface $securityContext;
    private Session $session;
    private Users $users;

    public function __construct(SecurityContextInterface $securityContext, Session $session, Users $users)
    {
        $this->securityContext = $securityContext;
        $this->session = $session;
        $this->users = $users;
    }

    public function isValid(Request $request, EndpointDefinitionInterface $endpoint, ParameterBag $options, ?Session $session = null): void
    {
        $userId = $this->session->get('user_id');
        if ($userId === null) {
            return;
        }
        $user = $this->users->getUserById($userId);
        if ($user === null) {
            return;
        }
        $this->securityContext->set('user', $user);
    }
}
