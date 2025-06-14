<?php

namespace Pantono\Authentication\Provider;

use Pantono\Authentication\Exception\UserDoesNotExistException;
use Pantono\Authentication\Exception\InvalidPasswordException;
use Pantono\Authentication\Model\User;
use Symfony\Component\HttpFoundation\ParameterBag;
use Pantono\Contracts\Locator\UserInterface;
use Pantono\Authentication\Exception\EmailAlreadyExists;

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
            throw new UserDoesNotExistException('User does not exist');
        }
        if (password_verify($password, $user->getPassword()) === false) {
            throw new InvalidPasswordException('Password is incorrect');
        }
        if (password_needs_rehash($user->getPassword(), PASSWORD_DEFAULT) === true) {
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
}
