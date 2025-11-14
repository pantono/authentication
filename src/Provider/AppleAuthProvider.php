<?php

namespace Pantono\Authentication\Provider;

use Symfony\Component\HttpFoundation\ParameterBag;
use Pantono\Contracts\Locator\UserInterface;
use League\OAuth2\Client\Provider\GenericProvider;
use Pantono\Hydrator\Locator\StaticLocator;
use Symfony\Component\HttpFoundation\Session\Session;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use GuzzleHttp\Exception\GuzzleException;
use Pantono\Authentication\Exception\GenericLoginProviderException;
use Pantono\Authentication\Exception\UserDoesNotExistException;
use Pantono\Authentication\Model\LoginProviderUser;
use League\OAuth2\Client\Token\AccessToken;
use Pantono\Utilities\DateTimeParser;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use Pantono\Authentication\Model\User;

class AppleAuthProvider extends AbstractAuthenticationProvider
{
    private ?GenericProvider $client = null;
    /**
     * @var string[]
     */
    private array $scopes = ['name', 'email'];

    public function initiateLogin(array $parameters = []): ?string
    {
        $state = $this->getAppleClient()->getState();
        $this->authentication->addLogForProvider($this->getProviderConfig(), 'Initiating login with apple', null, $this->getSession()->getId(), ['state' => $state]);
        $this->getSession()->set('apple_oauth_state', $state);
        return $this->getAppleClient()->getAuthorizationUrl([
            'scope' => $this->scopes
        ]);
    }

    public function authenticate(array $options = []): UserInterface
    {
        $this->authentication->addLogForProvider($this->getProviderConfig(), 'Processing apple response data', null, $this->getSession()->getId(), ['data' => $options]);
        $token = $this->getTokenFromOptions($options);
        $resource = $this->getAppleClient()->getResourceOwner($token);
        $resourceData = $resource->toArray();
        $user = $this->users->getUserByEmailAddress($resourceData['email']);
        $this->authentication->addLogForProvider($this->getProviderConfig(), 'Processed apple data', null, $this->getSession()->getId(), ['resource_data' => $resource]);
        if ($user === null) {
            throw new UserDoesNotExistException('User does not exist');
        }

        $userLogin = new LoginProviderUser();
        $userLogin->setAccessToken($token->getToken());
        $userLogin->setDateConnected(new \DateTime);
        $userLogin->setLastUsed(new \DateTime);
        $userLogin->setProvider($this->getProviderConfig());
        $userLogin->setValues($token->getValues());
        if ($token->getRefreshToken()) {
            $userLogin->setRefreshToken($token->getRefreshToken());
        }
        if ($token->getResourceOwnerId()) {
            $userLogin->setProviderUserId($token->getResourceOwnerId());
        }
        $expiry = null;
        if ($token->getExpires()) {
            $expiry = \DateTimeImmutable::createFromFormat('U', (string)$token->getExpires());
        }
        if (!$expiry) {
            $expiry = new \DateTime('+1 day');
        }
        $userLogin->setTokenExpires($expiry);

        $this->authentication->saveLoginProviderUser($userLogin);
        $this->authentication->addSuccessfulLoginForUser($user, $this->getProviderConfig());
        $this->authentication->addLogForProvider($this->getProviderConfig(), 'Successfully authenticated with apple', null, $this->getSession()->getId(), ['user_login' => $userLogin->getId()]);

        return $user;
    }

    public function initiateRegister(): ?string
    {
        $this->getSession()->set('apple_oauth_register', 'yes');
        return $this->initiateLogin();
    }

    public function registerUser(array $options): UserInterface
    {
        $token = $this->getTokenFromOptions($options);
        $resource = $this->getAppleClient()->getResourceOwner($token);
        $user = new User();
        $user->setDateCreated(new \DateTime);
        foreach ($resource->toArray() as $key => $value) {
            if ($key === 'email') {
                $user->setEmailAddress($value);
            }
            if ($key === 'firstName') {
                $user->setForename($value);
            }
            if ($key === 'lastName') {
                $user->setSurname($value);
            }
        }
        return $user;
    }

    private function getAppleClient(): GenericProvider
    {
        if (!$this->client) {
            $this->client = new GenericProvider([
                'clientId' => $this->getProviderConfig()->getConfigField('client_id'),
                'clientSecret' => $this->getProviderConfig()->getConfigField('client_secret'),
                'redirectUri' => $this->getProviderConfig()->getConfigField('redirect_uri'),
                'urlAuthorize' => 'https://appleid.apple.com/auth/authorize',
                'urlAccessToken' => 'https://appleid.apple.com/auth/token',
                'urlResourceOwnerDetails' => 'https://appleid.apple.com/auth/userinfo',
                'scopes' => $this->scopes
            ]);
        }
        return $this->client;
    }

    private function getSession(): Session
    {
        $session = StaticLocator::getLocator()->getClassAutoWire(Session::class);
        if (!$session) {
            throw new \RuntimeException('Session provider not setup');
        }
        return $session;
    }

    private function getTokenFromOptions(array $options): AccessToken
    {
        try {
            /**
             * @var AccessToken $token
             */
            $token = $this->getAppleClient()->getAccessToken('authorization_code', [
                'code' => $options['code']
            ]);
        } catch (IdentityProviderException $e) {
            throw new GenericLoginProviderException($e->getMessage(), $e->getCode(), $e);
        } catch (GuzzleException|\Exception $e) {
            $this->authentication->addLogForProvider($this->getProviderConfig(), 'Unhandled error', null, $this->getSession()->getId(), ['message' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine(), 'code' => $e->getCode()]);
            throw new \RuntimeException('An authentication error has occurred');
        }

        return $token;
    }
}
