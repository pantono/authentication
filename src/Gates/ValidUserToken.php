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
use Pantono\Config\Config;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class ValidUserToken implements SecurityGateInterface
{
    private UserAuthentication $authentication;
    private SecurityContextInterface $securityContext;
    private EventDispatcher $dispatcher;
    private Config $config;

    public function __construct(UserAuthentication $authentication, SecurityContextInterface $securityContext, EventDispatcher $dispatcher, Config $config)
    {
        $this->authentication = $authentication;
        $this->securityContext = $securityContext;
        $this->dispatcher = $dispatcher;
        $this->config = $config;
    }

    public function isValid(Request $request, EndpointDefinitionInterface $endpoint, ParameterBag $options, ?Session $session = null): void
    {
        $tokenString = $request->headers->get('UserToken');
        if (!$tokenString && $session !== null) {
            $tokenString = $session->get('api_token');
        }
        $jwtUserId = null;
        $decoded = new \stdClass();
        if (!$tokenString && $request->headers->has('Authorization')) {
            $tokenString = $request->headers->get('Authorization');
            [, $tokenString] = explode(' ', $tokenString, 2);
            try {
                $decoded = JWT::decode($tokenString, new Key($this->getJwtSecret(), 'HS256'));
                $jwtUserId = $decoded->data->user_id;
            } catch (\Exception $e) {
                $tokenString = null;
            }
        }

        if (!$tokenString) {
            throw new AccessDeniedException('User authentication token is required');
        }

        $token = $this->authentication->getUserTokenByToken($tokenString);
        if ($token === null) {
            throw new AccessDeniedException('User authentication token invalid');
        }

        if ($jwtUserId !== null && $decoded->data->user_id !== $jwtUserId) {
            throw new AccessDeniedException('User authentication mismatch');
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

    private function processJwtLogin(Request $request, EndpointDefinitionInterface $endpoint, ParameterBag $options, ?Session $session = null)
    {
        $header = $request->headers->get('Authorization');
        [$type, $token] = explode(' ', $header, 2);
        if (strtolower($type) === 'bearer') {
            JWT::$leeway = 60;
            $decoded = JWT::decode($token, new Key($this->getJwtSecret(), 'HS256'));
        }
    }

    private function getJwtSecret(): ?string
    {
        $secret = $this->config->getApplicationConfig()->getValue('jwt.secret');
        if (!$secret) {
            throw new \RuntimeException('JWT secret not set');
        }
        return $secret;
    }
}
