<?php

namespace Pantono\Authentication;

use Pantono\Authentication\Repository\TwoFactorAuthRepository;
use Pantono\Hydrator\Hydrator;
use Pantono\Contracts\Locator\LocatorInterface;
use Pantono\Authentication\Model\TfaType;
use Pantono\Authentication\Model\User;
use Pantono\Authentication\Model\UserTfaMethod;
use Pantono\Authentication\Provider\Tfa\AbstractTwoFactorAuthProvider;
use Pantono\Authentication\Model\UserTfaAttempt;

class TwoFactorAuth
{
    private TwoFactorAuthRepository $repository;
    private Hydrator $hydrator;
    private LocatorInterface $locator;
    private UserAuthentication $userAuthentication;

    public function __construct(TwoFactorAuthRepository $repository, Hydrator $hydrator, LocatorInterface $locator, UserAuthentication $userAuthentication)
    {
        $this->repository = $repository;
        $this->hydrator = $hydrator;
        $this->locator = $locator;
        $this->userAuthentication = $userAuthentication;
    }

    public function getTypeById(int $id): ?TfaType
    {
        return $this->hydrator->hydrate(TfaType::class, $this->repository->getTypeById($id));
    }

    /**
     * @return TfaType[]
     */
    public function getTypes(): array
    {
        return $this->hydrator->hydrateSet(TfaType::class, $this->repository->getTypes());
    }

    /**
     * @param User $user
     * @return UserTfaMethod[]
     */
    public function getMethodsForUser(User $user): array
    {
        return $this->hydrator->hydrateSet(UserTfaMethod::class, $this->repository->getMethodsForUser($user));
    }

    public function getUserMethodById(int $id): ?UserTfaMethod
    {
        return $this->hydrator->hydrate(UserTfaMethod::class, $this->repository->getUserMethodById($id));
    }

    public function saveMethod(UserTfaMethod $method): void
    {
        $this->repository->saveUserTfaMethod($method);
    }

    public function getControllerForType(TfaType $type): AbstractTwoFactorAuthProvider
    {
        return $this->locator->getClassAutoWire($type->getController());
    }

    public function getAttemptById(int $id): ?UserTfaAttempt
    {
        return $this->hydrator->hydrate(UserTfaAttempt::class, $this->repository->getAttemptById($id));
    }

    public function getAttemptBySlug(int $id): ?UserTfaAttempt
    {
        return $this->hydrator->hydrate(UserTfaAttempt::class, $this->repository->getAttemptBySlug($id));
    }

    public function saveAttempt(UserTfaAttempt $attempt): void
    {
        $this->repository->saveAttempt($attempt);
    }

    public function completeTwoFactorAuth(UserTfaAttempt $attempt): void
    {
        $provider = $this->userAuthentication->getProviderFromSession();
        if (!$provider) {
            throw new \RuntimeException('Provider not set in session');
        }
        if (!$attempt->getMethod()?->getUser()) {
            throw new \RuntimeException('User not found');
        }
        $this->userAuthentication->addSuccessfulLoginForUser($attempt->getMethod()->getUser(), $provider, $attempt);
        $attempt->setVerified(true);
        $this->saveAttempt($attempt);
        $this->addLogToAttempt($attempt, 'Successfully authenticated');
    }

    public function addLogToAttempt(UserTfaAttempt $attempt, string $string): void
    {
        $this->repository->addLogToAttempt($attempt, $string);
    }
}
