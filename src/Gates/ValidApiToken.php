<?php

namespace Pantono\Authentication\Gates;

use Pantono\Contracts\Security\Gate\SecurityGateInterface;
use Pantono\Authentication\ApiAuthentication;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Pantono\Authentication\Exception\AccessDeniedException;
use Pantono\Contracts\Security\SecurityContextInterface;
use Pantono\Authentication\Event\ApiTokenAuthenticatedEvent;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Pantono\Contracts\Endpoint\EndpointDefinitionInterface;
use Symfony\Component\HttpFoundation\ParameterBag;

class ValidApiToken implements SecurityGateInterface
{
    private ApiAuthentication $authentication;
    private SecurityContextInterface $securityContext;
    private EventDispatcher $dispatcher;

    public function __construct(ApiAuthentication $authentication, SecurityContextInterface $securityContext, EventDispatcher $dispatcher)
    {
        $this->authentication = $authentication;
        $this->securityContext = $securityContext;
        $this->dispatcher = $dispatcher;
    }

    public function isValid(Request $request, EndpointDefinitionInterface $endpoint, ParameterBag $options, ?Session $session = null): void
    {
        $tokenString = $request->headers->get('ApiKey');
        if (!$tokenString) {
            throw new AccessDeniedException('ApiKey is required');
        }

        $token = $this->authentication->getApiTokenByToken($tokenString);
        if ($token === null) {
            throw new AccessDeniedException('ApiKey is invalid');
        }

        if ($token->getDateExpires() <= new \DateTime) {
            throw new AccessDeniedException('ApiKey is expired');
        }

        $token->setDateLastUsed(new \DateTime);
        $this->authentication->updateApiTokenLastSeen($token);
        $this->securityContext->set('api_key', $token);
        $event = new ApiTokenAuthenticatedEvent();
        $event->setToken($token);
        $event->setContext($this->securityContext);
        $this->dispatcher->dispatch($event);
    }
}
