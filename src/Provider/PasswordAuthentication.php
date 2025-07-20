<?php

namespace Pantono\Authentication\Provider;

use Pantono\Authentication\Exception\UserDoesNotExistException;
use Pantono\Authentication\Exception\InvalidPasswordException;
use Pantono\Authentication\Model\User;
use Symfony\Component\HttpFoundation\ParameterBag;
use Pantono\Contracts\Locator\UserInterface;
use Pantono\Authentication\Exception\EmailAlreadyExists;
use Symfony\Component\HttpFoundation\Session\Session;
use Pantono\Hydrator\Locator\StaticLocator;
use Pantono\Authentication\Exception\PasswordAuthNotAvailableException;

class PasswordAuthentication extends AbstractAuthenticationProvider
{
    public function initiateLogin(): ?string
    {
        //No initiate needed for password auth
        return null;
    }

    public function initiateRegister(): ?string
    {
        //No initiate needed for password auth
        return null;
    }

    public function authenticate(array $options = []): User
    {
        $password = $options['password'] ?? null;
        $username = $options['username'] ?? null;
        if (!$username) {
            throw new \InvalidArgumentException('Username is required');
        }
        if ($password === null) {
            throw new InvalidPasswordException('Invalid password');
        }
        $user = $this->users->getUserByEmailAddress($username);
        if ($user === null) {
            $this->authentication->addLogForProvider($this->getProviderConfig(), 'User not found', null, $this->getSession()->getId(), ['username' => $username]);
            throw new UserDoesNotExistException('User does not exist');
        }
        if (!$user->getPassword()) {
            throw new PasswordAuthNotAvailableException('Password authentication not available for this user');
        }
        if (password_verify($password, $user->getPassword()) === false) {
            $this->authentication->addLogForProvider($this->getProviderConfig(), 'User not found', $user->getId(), $this->getSession()->getId(), ['username' => $username]);
            throw new InvalidPasswordException('Password is incorrect');
        }
        if (password_needs_rehash($user->getPassword(), PASSWORD_DEFAULT) === true) {
            $this->authentication->addLogForProvider($this->getProviderConfig(), 'Password re-hashed', $user->getId(), $this->getSession()->getId());
            $user->setPassword(password_hash($password, PASSWORD_DEFAULT));
        }
        $user->setDateLastLogin(new \DateTimeImmutable());
        $this->users->saveUser($user);
        $this->authentication->addSuccessfulLoginForUser($user, $this->getProviderConfig());
        return $user;
    }

    public function registerUser(array $options): UserInterface
    {
        $parameters = new ParameterBag($options);
        $required = ['email_address', 'password', 'forename', 'surname'];
        foreach ($required as $field) {
            if ($parameters->has($field) === false) {
                throw new \InvalidArgumentException('Missing required field ' . $field);
            }
        }
        $current = $this->users->getUserByEmailAddress($parameters->get('email_address'));
        if ($current !== null) {
            throw new EmailAlreadyExists('E-mail address already exists');
        }
        $user = new User();
        $user->setEmailAddress($parameters->get('email_address'));
        $user->setForename($parameters->get('forename'));
        $user->setSurname($parameters->get('surname'));
        $user->setPassword(password_hash($parameters->get('password'), PASSWORD_DEFAULT));
        $user->setDateCreated(new \DateTimeImmutable());
        $this->users->saveUser($user);
        return $user;
    }

    private function getSession(): Session
    {
        $session = StaticLocator::getLocator()->loadDependency('@Session');
        if (!$session) {
            throw new \RuntimeException('Session provider not setup');
        }
        return $session;
    }
}
