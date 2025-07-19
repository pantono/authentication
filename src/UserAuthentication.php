<?php

namespace Pantono\Authentication;

use Pantono\Authentication\Repository\UserAuthenticationRepository;
use Pantono\Hydrator\Hydrator;
use Pantono\Authentication\Model\User;
use Pantono\Authentication\Model\UserToken;
use Pantono\Utilities\StringUtilities;
use Pantono\Authentication\Provider\AbstractAuthenticationProvider;
use Pantono\Authentication\Model\LoginProvider;
use Pantono\Authentication\Model\LoginProviderUser;
use Pantono\Hydrator\Locator\StaticLocator;
use Pantono\Utilities\RequestHelper;
use Pantono\Authentication\Model\LoginProviderType;
use Symfony\Component\HttpFoundation\Session\Session;
use Pantono\Authentication\Exception\TwoFactorAuthRequired;
use Pantono\Authentication\Model\UserTfaAttempt;

class UserAuthentication
{
    private UserAuthenticationRepository $repository;
    private Hydrator $hydrator;
    private Users $users;
    private Session $session;

    public function __construct(UserAuthenticationRepository $repository, Hydrator $hydrator, Users $users, Session $session)
    {
        $this->repository = $repository;
        $this->hydrator = $hydrator;
        $this->users = $users;
        $this->session = $session;
    }

    public function getUserTokenById(int $id): ?UserToken
    {
        return $this->hydrator->hydrate(UserToken::class, $this->repository->getUserTokenById($id));
    }

    public function getUserTokenByToken(string $token): ?UserToken
    {
        return $this->hydrator->hydrate(UserToken::class, $this->repository->getUserTokenByToken($token));
    }

    public function getProviderTypeById(int $id): ?LoginProviderType
    {
        return $this->hydrator->hydrate(LoginProviderType::class, $this->repository->getProviderTypeById($id));
    }

    public function addSuccessfulLoginForUser(User $user, LoginProvider $provider, ?UserTfaAttempt $twoFactorAuthAttempt = null): void
    {
        $isTfa = $twoFactorAuthAttempt && $twoFactorAuthAttempt->isVerified();
        $this->session->set('login_provider', $provider->getId());
        if ($user->isTfaEnabled() && !$isTfa) {
            $this->session->set('tfa_user_id', $user->getId());
            $this->addLogForProvider($provider, 'First stage login completed, Two factor auth required', $user->getId());
            throw new TwoFactorAuthRequired('Two factor auth is required to continue');
        }
        $this->session->set('user_id', $user->getId());
        if ($isTfa) {
            $this->addLogForProvider($provider, 'Successfully logged in with two factor auth');
        }
        $this->addLogForProvider($provider, 'Successfully logged in', $user->getId());
    }

    public function processLogout(): void
    {
        $this->session->remove('user_id');
        $this->session->remove('login_provider');
    }

    public function getAuthenticationProvider(LoginProvider $loginProvider): AbstractAuthenticationProvider
    {
        if (!$loginProvider->getType()) {
            throw new \RuntimeException('Authentication provider does not exist');
        }
        $providerClass = $loginProvider->getType()->getProviderClass();
        if (!class_exists($providerClass)) {
            throw new \RuntimeException('Authentication provider does not exist');
        }
        $provider = StaticLocator::getLocator()->getClassAutoWire($providerClass);
        if (!$provider instanceof AbstractAuthenticationProvider) {
            throw new \RuntimeException('Authentication provider does not exist');
        }
        $provider->setAuthentication($this);
        $provider->setProviderConfig($loginProvider);
        $provider->setUsers($this->users);
        return $provider;
    }


    public function addTokenForUser(User $user, ?\DateTimeImmutable $expires = null, ?int $apiTokenId = null): UserToken
    {
        if ($expires === null) {
            $expires = new \DateTimeImmutable('+1 day');
        }
        $id = $user->getId();
        if ($id === null) {
            throw new \RuntimeException('User must be saved first');
        }
        $token = new UserToken();
        $token->setUser($user);
        $token->setUserId($id);
        $token->setDateCreated(new \DateTimeImmutable());
        $token->setToken($this->getAvailableToken());
        $token->setDateExpires($expires);
        $token->setApiTokenId($apiTokenId);
        $token->setDateLastUsed(new \DateTimeImmutable());
        $this->repository->saveToken($token);
        return $token;
    }

    private function getAvailableToken(): string
    {
        $token = StringUtilities::generateRandomString(200);
        while (!empty($this->repository->getUserByToken($token))) {
            $token = StringUtilities::generateRandomString(200);
        }
        return $token;
    }

    public function getLoginProviderById(int $id): ?LoginProvider
    {
        return $this->hydrator->hydrate(LoginProvider::class, $this->repository->getSocialProviderById($id));
    }

    /**
     * @return LoginProviderUser[]
     */
    public function getUserProviderLogins(User $user): array
    {
        return $this->hydrator->hydrateSet(LoginProviderUser::class, $this->repository->getSocialLoginsForUser($user));
    }

    public function saveLoginProviderUser(LoginProviderUser $loginProviderUser): void
    {
        $this->repository->saveLoginProviderUser($loginProviderUser);
    }

    public function getUserByProviderLogin(LoginProvider $provider, string $userId): ?User
    {
        return $this->hydrator->hydrate(User::class, $this->repository->getUserByProviderLogin($provider, $userId));
    }

    public function updateTokenLastSeen(UserToken $token): void
    {
        $this->repository->updateTokenLastSeen($token);
    }

    public function addLogForProvider(LoginProvider $provider, string $entry, ?int $userId = null, ?string $sessionId = null, ?array $data = null): void
    {
        $ipAddress = RequestHelper::getIp();
        $this->repository->addLogForProvider($provider, $entry, $ipAddress, $userId, $sessionId, $data);
    }

    public function getProviderFromSession(): ?LoginProvider
    {
        if ($this->session->has('login_provider') === false) {
            return null;
        }
        return $this->getLoginProviderById($this->session->get('login_provider'));
    }

    public function getLoginProviderUserById(int $id): ?LoginProviderUser
    {
        return $this->hydrator->hydrate(LoginProviderUser::class, $this->repository->getLoginProviderUserById($id));
    }
}
