<?php

namespace Pantono\Authentication\Gates;

use Pantono\Contracts\Security\Gate\SecurityGateInterface;
use Pantono\Authentication\UserAuthentication;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Pantono\Authentication\Exception\AccessDeniedException;
use Pantono\Contracts\Security\SecurityContextInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Pantono\Authentication\Event\UserAuthenticatedEvent;
use Pantono\Contracts\Endpoint\EndpointDefinitionInterface;
use Symfony\Component\HttpFoundation\ParameterBag;

class ValidUserToken implements SecurityGateInterface
{
    private UserAuthentication $authentication;
    private SecurityContextInterface $securityContext;
    private EventDispatcher $dispatcher;

    public function __construct(UserAuthentication $authentication, SecurityContextInterface $securityContext, EventDispatcher $dispatcher)
    {
        $this->authentication = $authentication;
        $this->securityContext = $securityContext;
        $this->dispatcher = $dispatcher;
    }

    public function isValid(Request $request, EndpointDefinitionInterface $endpoint, ParameterBag $options, ?Session $session = null): void
    {
        $tokenString = $request->headers->get('UserToken');
        if (!$tokenString && $session !== null) {
            $tokenString = $session->get('api_token');
        }

        if (!$tokenString) {
            throw new AccessDeniedException('User authentication token is required');
        }

        $token = $this->authentication->getUserTokenByToken($tokenString);
        if ($token === null) {
            throw new AccessDeniedException('User authentication token invalid');
        }

        if ($token->getDateExpires() <= new \DateTime) {
            throw new AccessDeniedException('You have been logged out');
        }
        if (!$token->getUser()) {
            throw new AccessDeniedException('You are not logged in');
        }

        $token->setDateLastUsed(new \DateTime);
        $this->authentication->updateTokenLastSeen($token);
        $this->securityContext->set('user', $token->getUser());
        $this->securityContext->set('user_token', $token);

        $event = new UserAuthenticatedEvent();
        if ($token->getUser()) {
            $event->setUser($token->getUser());
        }
        $event->setSecurityContext($this->securityContext);
        $this->dispatcher->dispatch($event);
    }
}
