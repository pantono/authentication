<?php

namespace Pantono\Authentication;

use Pantono\Authentication\Repository\UserAuthenticationRepository;
use Pantono\Hydrator\Hydrator;
use Pantono\Authentication\Model\User;
use Pantono\Authentication\Model\UserToken;
use Pantono\Contracts\Locator\UserInterface;
use Pantono\Authentication\Model\Permission;
use Pantono\Utilities\StringUtilities;
use Pantono\Authentication\Model\Group;

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

    public function getUserByEmailAddress(string $emailAddress): ?UserInterface
    {
        return $this->hydrator->hydrate($this->userClass, $this->repository->getUserByEmailAddress($emailAddress));
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

    /**
     * @return Group[]
     */
    public function getGroupsForUser(UserInterface $user): array
    {
        return $this->hydrator->hydrateSet(Group::class, $this->repository->getGroupsForUser($user));
    }

    public function saveUser(UserInterface $user): void
    {
        $this->repository->saveUser($user);
    }

    public function addTokenForUser(UserInterface $user, ?\DateTimeImmutable $expires = null, ?int $apiTokenId = null): UserToken
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
}
