<?php

namespace Pantono\Authentication\Gates;

use Pantono\Contracts\Security\Gate\SecurityGateInterface;
use Pantono\Authentication\UserAuthentication;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Pantono\Contracts\Security\SecurityContextInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Pantono\Authentication\Event\UserAuthenticatedEvent;
use Pantono\Contracts\Endpoint\EndpointDefinitionInterface;
use Symfony\Component\HttpFoundation\ParameterBag;
use Pantono\Config\Config;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Pantono\Authentication\Exception\InvalidCredentials;

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
        $requestHeaders = new ParameterBag(getallheaders());
        if (!$tokenString && $requestHeaders->has('Authorization')) {
            $tokenString = $requestHeaders->get('Authorization');
            [, $tokenString] = explode(' ', $tokenString, 2);
            try {
                $secret = $this->getJwtSecret();
                if ($secret) {
                    $decoded = JWT::decode($tokenString, new Key($secret, 'HS256'));
                    $jwtUserId = $decoded->data->user_id;
                } else {
                    $tokenString = null;
                }
            } catch (\Exception $e) {
                $tokenString = null;
            }
        }

        if (!$tokenString && $request->cookies->get(UserAuthentication::COOKIE_NAME)) {
            $tokenString = $request->cookies->get(UserAuthentication::COOKIE_NAME);
        }

        if (!$tokenString) {
            throw new InvalidCredentials('User authentication token is required');
        }

        $token = $this->authentication->getUserTokenByToken($tokenString);
        if ($token === null) {
            throw new InvalidCredentials('User authentication token invalid');
        }

        if ($jwtUserId !== null && $decoded->data->user_id !== $jwtUserId) {
            throw new InvalidCredentials('User authentication mismatch');
        }

        if ($token->getDateExpires() <= new \DateTime) {
            throw new InvalidCredentials('You have been logged out');
        }
        if (!$token->getUser()) {
            throw new InvalidCredentials('You are not logged in');
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

    private function getJwtSecret(): string
    {
        $secret = $this->config->getApplicationConfig()->getValue('jwt.secret');
        if (!$secret) {
            throw new \RuntimeException('JWT secret not set');
        }
        return $secret;
    }
}
