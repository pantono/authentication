<?php

namespace Pantono\Authentication\Provider;

use Pantono\Authentication\UserAuthentication;
use Pantono\Contracts\Locator\UserInterface;
use Symfony\Component\HttpFoundation\ParameterBag;
use Pantono\Authentication\Exception\AccessDeniedException;
use Pantono\Authentication\Model\LoginProvider;
use Pantono\Authentication\Users;

abstract class AbstractAuthenticationProvider
{
    protected UserAuthentication $authentication;
    protected LoginProvider $providerConfig;
    protected Users $users;

    /**
     * Initiates authentication setup. If a string is returned, it should be a redirect URI to continue
     * this authentication method
     *
     * @return string|null
     */
    abstract public function initiateLogin(array $parameters = []): ?string;

    /**
     * Initiates registration setup. If a string is returned, it should be a redirect URI to continue
     * this authentication method
     *
     * @return string|null
     */
    abstract public function initiateRegister(): ?string;

    /**
     * @param array $options array of options required for completing authentication
     * @return UserInterface
     * @throws AccessDeniedException
     */
    abstract public function authenticate(array $options = []): UserInterface;

    abstract public function registerUser(array $options): UserInterface;

    public function setAuthentication(UserAuthentication $authentication): void
    {
        $this->authentication = $authentication;
    }

    public function getProviderConfig(): LoginProvider
    {
        return $this->providerConfig;
    }

    public function setProviderConfig(LoginProvider $providerConfig): void
    {
        $this->providerConfig = $providerConfig;
    }

    public function getUsers(): Users
    {
        return $this->users;
    }

    public function setUsers(Users $users): void
    {
        $this->users = $users;
    }
}
