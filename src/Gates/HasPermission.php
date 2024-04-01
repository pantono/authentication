<?php

namespace Pantono\Authentication\Gates;

use Pantono\Contracts\Security\Gate\SecurityGateInterface;
use Symfony\Component\HttpFoundation\Request;
use Pantono\Contracts\Endpoint\EndpointDefinitionInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\ParameterBag;
use Pantono\Contracts\Security\SecurityContextInterface;
use Pantono\Authentication\Model\User;
use Pantono\Authentication\Exception\AccessDeniedException;

class HasPermission implements SecurityGateInterface
{
    private SecurityContextInterface $securityContext;

    public function __construct(SecurityContextInterface $securityContext)
    {
        $this->securityContext = $securityContext;
    }

    public function isValid(Request $request, EndpointDefinitionInterface $endpoint, ParameterBag $options, ?Session $session = null): void
    {
        if ($options->has('permission') === false) {
            throw new \RuntimeException('Permission is required');
        }
        /**
         * @var User $user
         */
        $user = $this->securityContext->get('user');
        if ($user->hasPermission($options->get('permission')) === false) {
            throw new AccessDeniedException('You are not authorised to perform this action');
        }
    }
}
