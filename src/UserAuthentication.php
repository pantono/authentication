<?php

namespace Pantono\Authentication;

use Pantono\Authentication\Repository\UserAuthenticationRepository;
use Pantono\Hydrator\Hydrator;
use Pantono\Authentication\Model\User;
use Pantono\Authentication\Model\UserToken;
use Pantono\Contracts\Locator\UserInterface;
use Pantono\Authentication\Model\Permission;

class UserAuthentication
{
    private UserAuthenticationRepository $repository;
    private Hydrator $hydrator;
    private string $userClass = User::class;

    public function __construct(UserAuthenticationRepository $repository, Hydrator $hydrator)
    {
        $this->repository = $repository;
        $this->hydrator = $hydrator;
        $this->userClass = User::class;
    }

    public function setUserClass(string $class): void
    {
        $this->userClass = $class;
    }

    /**
     * @param UserInterface $user
     * @return Permission[]
     */
    public function getPermissionsForUser(UserInterface $user): array
    {
        return $this->repository->getPermissionsForUser($user);
    }

    public function getUserById(int $id): ?UserInterface
    {
        return $this->hydrator->hydrate($this->userClass, $this->repository->getUserById($id));
    }

    public function getUserTokenByToken(string $token): ?UserToken
    {
        return $this->hydrator->hydrate(UserToken::class, $this->repository->getUserTokenByToken($token));
    }

    public function getUserByToken(string $token): ?UserInterface
    {
        return $this->hydrator->hydrate($this->userClass, $this->repository->getUserByToken($token));
    }

    public function updateTokenLastSeen(UserToken $token): void
    {
        $this->repository->updateTokenLastSeen($token);
    }

    public function getGroupsForUser(User $user): array
    {
        return $this->repository->getGroupsForUser($user);
    }

    public function saveUser(User $user): void
    {
        $this->repository->saveUser($user);
    }
}
