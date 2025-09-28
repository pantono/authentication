<?php

namespace Pantono\Authentication;

use Pantono\Authentication\Repository\UserAuthenticationRepository;
use Pantono\Hydrator\Hydrator;
use Pantono\Authentication\Model\User;
use Pantono\Authentication\Model\UserToken;
use Pantono\Authentication\Provider\AbstractAuthenticationProvider;
use Pantono\Authentication\Model\LoginProvider;
use Pantono\Authentication\Model\LoginProviderUser;
use Pantono\Hydrator\Locator\StaticLocator;
use Pantono\Utilities\RequestHelper;
use Pantono\Authentication\Model\LoginProviderType;
use Symfony\Component\HttpFoundation\Session\Session;
use Pantono\Authentication\Exception\TwoFactorAuthRequired;
use Pantono\Authentication\Model\UserTfaAttempt;
use Firebase\JWT\JWT;
use Pantono\Config\Config;
use Pantono\Contracts\Security\SecurityContextInterface;
use Pantono\Authentication\Model\UserPasswordReset;
use Pantono\Authentication\Event\PreUserPasswordResetSaveSaveEvent;
use Pantono\Authentication\Event\PostUserPasswordResetSaveSaveEvent;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Pantono\Authentication\Exception\PasswordReset\PasswordResetAlreadyCompleted;
use Pantono\Authentication\Exception\PasswordReset\PasswordResetExpired;
use Pantono\Utilities\StringUtilities;

class UserAuthentication
{
    private UserAuthenticationRepository $repository;
    private Hydrator $hydrator;
    private Users $users;
    private Session $session;
    private Config $config;
    private SecurityContextInterface $securityContext;

    public const COOKIE_NAME = 'pnto-token';
    private EventDispatcher $dispatcher;

    public function __construct(
        UserAuthenticationRepository $repository,
        Hydrator                     $hydrator,
        Users                        $users,
        Session                      $session,
        Config                       $config,
        SecurityContextInterface     $securityContext,
        EventDispatcher              $dispatcher
    )
    {
        $this->repository = $repository;
        $this->hydrator = $hydrator;
        $this->users = $users;
        $this->session = $session;
        $this->config = $config;
        $this->securityContext = $securityContext;
        $this->dispatcher = $dispatcher;
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
            $this->addLogForProvider($provider, 'First stage login completed, Two factor auth required', $user->getId(), $this->session->getId());
            throw new TwoFactorAuthRequired();
        }
        $this->session->set('user_id', $user->getId());
        $token = $this->addTokenForUser($user, new \DateTimeImmutable('+1 year'));
        $this->securityContext->set('user_token', $token);
        setcookie(self::COOKIE_NAME, $token->getToken(), time() + (3600 * 24 * 365), '/', '', true, true);
        if ($isTfa) {
            $this->session->set('tfa_attempt_id', $twoFactorAuthAttempt->getId());
            $this->addLogForProvider($provider, 'Successfully logged in with two factor auth', $user->getId(), $this->session->getId());
        }
        $this->addLogForProvider($provider, 'Successfully logged in', $user->getId(), $this->session->getId());
    }

    public function processLogout(): void
    {
        $userId = $this->session->get('user_id');
        $loginProvider = $this->session->get('login_provider');
        $provider = null;
        if ($loginProvider) {
            $provider = $this->getLoginProviderById($loginProvider);
        }
        if ($userId) {
            $this->addLogForProvider($provider, 'Logged out', $userId, $this->session->getId());;
        }
        setcookie(self::COOKIE_NAME, '', time() - 3600, '/', '', true, true);
        $this->session->remove('user_id');
        $this->session->remove('login_provider');
        $this->session->remove('tfa_user_id');
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
        $token->setToken($this->getAvailableToken($user, $expires));
        $token->setDateExpires($expires);
        $token->setApiTokenId($apiTokenId);
        $token->setDateLastUsed(new \DateTimeImmutable());
        $this->repository->saveToken($token);
        return $token;
    }

    private function getAvailableToken(User $user, ?\DateTimeInterface $expiry = null): string
    {
        if ($expiry === null) {
            $expiry = new \DateTimeImmutable('+1 day');
        }
        return JWT::encode([
            'iss' => 'https://pantono.com',
            'aud' => 'https://pantono.com/aud',
            'sub' => $user->getId(),
            'iat' => (new \DateTime)->format('U'),
            'exp' => $expiry->format('U'),
            'roles' => $user->getPermissions(),
            'name' => $user->getName(),
            'groups' => $user->getGroups()
        ], $this->getJwtSecret(), 'HS256');
    }

    private function getAvailablePasswordResetTaken(): string
    {
        $token = StringUtilities::generateRandomToken();
        while ($this->repository->getPasswordResetByToken($token)) {
            $token = StringUtilities::generateRandomToken();
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

    public function addLogForProvider(?LoginProvider $provider, string $entry, ?int $userId = null, ?string $sessionId = null, array $data = []): void
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

    public function getPasswordResetByToken(string $token): ?UserPasswordReset
    {
        return $this->hydrator->hydrate(UserpasswordReset::class, $this->repository->getPasswordResetByToken($token));
    }

    public function getPasswordResetById(int $id): ?UserPasswordReset
    {
        return $this->hydrator->hydrate(UserpasswordReset::class, $this->repository->getPasswordResetById($id));
    }

    public function createPasswordResetForUser(User $user): UserPasswordReset
    {
        $reset = new UserPasswordReset();
        $reset->setUser($user);
        $reset->setDateCreated(new \DateTimeImmutable('now'));
        $reset->setToken($this->getAvailablePasswordResetTaken());
        $reset->setDateExpires(new \DateTime('+1 hour'));
        $this->savePasswordReset($reset);
        return $reset;
    }

    public function processPasswordReset(UserPasswordReset $passwordReset, string $newPassword): void
    {
        if ($passwordReset->isCompleted() === true) {
            throw new PasswordResetAlreadyCompleted('Password reset has already been completed');
        }

        if ($passwordReset->isExpired()) {
            throw new PasswordResetExpired('Password reset has expired');
        }
        if (!$passwordReset->getUser()) {
            throw new \RuntimeException('User does not exist on password reset');
        }
        $this->users->updateUserPassword($passwordReset->getUser(), $newPassword);
        $passwordReset->setCompleted(true);
        $this->savePasswordReset($passwordReset);
    }

    public function savePasswordReset(UserPasswordReset $passwordReset): void
    {
        $event = new PreUserPasswordResetSaveSaveEvent();
        $previous = $passwordReset->getId() ? $this->getPasswordResetById($passwordReset->getId()) : null;
        $event->setCurrent($passwordReset);
        $event->setPrevious($previous);
        $this->dispatcher->dispatch($event);

        $this->repository->savePasswordReset($passwordReset);

        $event = new PostUserPasswordResetSaveSaveEvent();
        $event->setCurrent($passwordReset);
        $event->setPrevious($previous);
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
