<?php

namespace Pantono\Authentication;

use Pantono\Authentication\Repository\UsersRepository;
use Pantono\Hydrator\Hydrator;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Pantono\Authentication\Model\Group;
use Pantono\Contracts\Locator\UserInterface;
use Pantono\Authentication\Model\UserToken;
use Pantono\Authentication\Model\Permission;
use Pantono\Authentication\Model\User;
use Pantono\Authentication\Model\UserField;
use Pantono\Authentication\Model\UserFieldType;
use Pantono\Authentication\Event\PreUserSaveEvent;
use Pantono\Authentication\Event\PostUserSaveEvent;
use Pantono\Authentication\Filter\UserFilter;
use Symfony\Component\HttpFoundation\ParameterBag;
use Pantono\Authentication\Exception\UserCreateException;
use Pantono\Authentication\Exception\RequiredFieldAlreadyExists;

class Users
{
    private UsersRepository $repository;
    private Hydrator $hydrator;
    private EventDispatcher $dispatcher;

    public const SYSTEM_USER_ID = 1;
    public const UNKNOWN_USER_ID = 0;

    public function __construct(UsersRepository $repository, Hydrator $hydrator, EventDispatcher $dispatcher)
    {
        $this->repository = $repository;
        $this->hydrator = $hydrator;
        $this->dispatcher = $dispatcher;
    }

    /**
     * @return Group[]
     */
    public function getGroupsForUser(UserInterface $user): array
    {
        return $this->hydrator->hydrateSet(Group::class, $this->repository->getGroupsForUser($user));
    }

    public function getGroupById(int $id): ?Group
    {
        return $this->hydrator->hydrate(Group::class, $this->repository->getGroupById($id));
    }

    public function createUser(array $data): User
    {
        $parameters = new ParameterBag($data);
        $required = ['forename', 'surname', 'email_address'];
        $user = new User();
        foreach ($required as $field) {
            if ($parameters->has($field) === false) {
                throw new RequiredFieldAlreadyExists('Missing required field ' . $field);
            }
        }
        $user->setDateCreated(new \DateTime);
        $user->setDeleted(false);
        $user->setVerified(false);
        $user->setDisabled(false);
        $user->setForename($parameters->get('forename'));
        $user->setSurname($parameters->get('surname'));
        $user->setEmailAddress($parameters->get('email_address'));
        if ($parameters->has('password')) {
            $user->setPassword(password_hash($parameters->get('password'), PASSWORD_DEFAULT));
        }
        $this->saveUser($user);
        return $user;
    }

    public function saveUser(User $user): void
    {
        $previous = $user->getId() ? $this->getUserById($user->getId()) : null;
        $event = new PreUserSaveEvent();
        $event->setPrevious($previous);
        $event->setCurrent($user);
        $this->dispatcher->dispatch($event);

        $this->repository->saveUser($user);

        $event = new PostUserSaveEvent();
        $event->setPrevious($previous);
        $event->setCurrent($user);
        $this->dispatcher->dispatch($event);
    }

    /**
     * @return Permission[]
     */
    public function getPermissionsForUser(User $user): array
    {
        return $this->repository->getPermissionsForUser($user);
    }

    public function getPermissionById(int $id): ?Permission
    {
        return $this->hydrator->hydrate(Permission::class, $this->repository->getPermissionById($id));
    }

    public function getUserById(int $id): ?User
    {
        return $this->hydrator->hydrate(User::class, $this->repository->getUserById($id));
    }

    public function getUserFieldTypeById(int $id): ?UserFieldType
    {
        return $this->hydrator->hydrate(UserFieldType::class, $this->repository->getUserFieldTypeById($id));
    }

    /**
     * @return UserField[]
     */
    public function getFieldsForUser(User $user): array
    {
        return $this->hydrator->hydrateSet(UserField::class, $this->repository->getFieldsForUser($user));
    }

    public function getUserByEmailAddress(string $emailAddress): ?User
    {
        return $this->hydrator->hydrate(User::class, $this->repository->getUserByEmailAddress($emailAddress));
    }

    /**
     * @return User[]
     */
    public function getUsersByFilter(UserFilter $filter): array
    {
        return $this->hydrator->hydrateSet(User::class, $this->repository->getUsersByFilter($filter));
    }

    public function addHistoryForUser(User $user, string $entry, ?User $byUser = null): void
    {
        if ($byUser === null) {
            if (php_sapi_name() == 'cli') {
                $byUser = $this->getUserById(self::SYSTEM_USER_ID);
            } else {
                $byUser = $this->getUserById(self::UNKNOWN_USER_ID);
            }
            if ($byUser === null) {
                throw new \RuntimeException('Unable to find system user');
            }
        }
        $this->repository->addHistoryForUser($user, $entry, $byUser);
    }

    /**
     * @return Permission[]
     */
    public function getAllPermissions(): array
    {
        return $this->hydrator->hydrateSet(Permission::class, $this->repository->getAllPermissions());
    }

    /**
     * @return Group[]
     */
    public function getAllGroups(): array
    {
        return $this->hydrator->hydrateSet(Group::class, $this->repository->getAllGroups());
    }
}
