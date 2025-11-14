<?php

namespace Pantono\Authentication\Provider;

use Pantono\Contracts\Locator\UserInterface;
use Pantono\Authentication\Exception\UserDoesNotExistException;
use Pantono\Authentication\Exception\GenericLoginProviderException;
use Pantono\Authentication\Exception\IdentifierRequiredForMagicLink;
use Pantono\Authentication\Exception\MagicLinkDoesNotExist;
use Pantono\Authentication\Exception\MagicLinkExpired;
use Pantono\Authentication\Model\UserTfaAttempt;

class MagicLinkProvider extends AbstractAuthenticationProvider
{
    public function initiateLogin(array $parameters = []): ?string
    {
        $emailAddress = $parameters['email'] ?? null;
        $id = $parameters['user_id'] ?? null;
        $expiry = $parameters['expiry'] ?? null;
        if ($id) {
            $user = $this->users->getUserById($id);
        } else if ($emailAddress) {
            $user = $this->users->getUserByEmailAddress($emailAddress);
        } else {
            throw new IdentifierRequiredForMagicLink('Either an id or an email address must be provided for magic link authentication');
        }
        if (!$user) {
            throw new UserDoesNotExistException('User does not exist');
        }
        if ($expiry !== null) {
            try {
                $expiry = new \DateTimeImmutable($expiry);
            } catch (\Exception $e) {
                $expiry = null;
            }
        }
        if ($expiry !== null) {
            $this->authentication->createOneTimeLinkForUser($user, $expiry);
        } else {
            $this->authentication->createOneTimeLinkForUser($user);
        }
        return null;
    }

    public function initiateRegister(): ?string
    {
        throw new \RuntimeException('Cannot register users with this method');
    }

    public function authenticate(array $options = []): UserInterface
    {
        $tokenString = $options['token'] ?? null;
        if (!$tokenString) {
            throw new GenericLoginProviderException('Token is required for one time login link');
        }
        $token = $this->authentication->getOneTimeLinkByToken($tokenString);
        if ($token === null) {
            throw new MagicLinkDoesNotExist('Magic link does not exist');
        }
        if ($token->getDateExpires() <= new \DateTimeImmutable()) {
            throw new MagicLinkExpired('Magic link has expired');
        }
        $user = $token->getUser();
        if (!$user) {
            throw new UserDoesNotExistException('User does not exist');
        }
        $attempt = new UserTfaAttempt();
        $attempt->setVerified(true);
        $attempt->setDummy(true);
        $this->authentication->addSuccessfulLoginForUser($user, $this->getProviderConfig(), $attempt);
        $token->setDateLoggedIn(new \DateTimeImmutable());
        $this->authentication->saveOneTomeLink($token);
        return $user;
    }

    public function registerUser(array $options): UserInterface
    {
        throw new \RuntimeException('Cannot register users with this method');
    }
}
