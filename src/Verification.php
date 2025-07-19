<?php

namespace Pantono\Authentication;

use Pantono\Authentication\Repository\UserVerificationRepository;
use Pantono\Hydrator\Hydrator;
use Pantono\Authentication\Model\UserVerificationType;
use Pantono\Authentication\Model\User;
use Pantono\Authentication\VerificationController\AbstractVerificationController;
use Pantono\Contracts\Locator\LocatorInterface;
use Pantono\Authentication\Model\UserVerification;
use Pantono\Utilities\StringUtilities;
use Pantono\Authentication\Event\PreUserVerificationSaveEvent;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Pantono\Authentication\Event\PostUserVerificationSaveEvent;

class Verification
{
    private UserVerificationRepository $repository;
    private Hydrator $hydrator;
    private EventDispatcher $dispatcher;
    private LocatorInterface $locator;

    public function __construct(UserVerificationRepository $repository, Hydrator $hydrator, EventDispatcher $dispatcher, LocatorInterface $locator)
    {
        $this->repository = $repository;
        $this->hydrator = $hydrator;
        $this->dispatcher = $dispatcher;
        $this->locator = $locator;
    }

    public function getVerificationTypeById(int $id): ?UserVerificationType
    {
        return $this->hydrator->hydrate(UserVerificationType::class, $this->repository->getVerificationTypeById($id));
    }

    public function getVerificationByToken(string $token): ?UserVerification
    {
        return $this->hydrator->hydrate(UserVerification::class, $this->repository->getVerificationByToken($token));
    }

    public function getVerificationById(int $id): ?UserVerification
    {
        return $this->hydrator->hydrate(UserVerification::class, $this->repository->getVerificationById($id));
    }

    public function initiateVerification(User $user, UserVerificationType $type): UserVerification
    {
        $verification = new UserVerification();
        $verification->setDateCreated(new \DateTimeImmutable());
        $verification->setDateExpires(new \DateTimeImmutable('+1 hour'));
        $verification->setCode($this->getUniqueCode());
        $verification->setToken($this->getUniqueToken());
        $verification->setUser($user);
        $verification->setVerified(false);
        $verification->setType($type);
        $this->saveUserVerification($verification);
        $this->getVerificationController($type)->initiate($verification);

        $this->saveUserVerification($verification);

        return $verification;
    }

    public function processVerification(UserVerification $verification, array $data): void
    {
        if (!$verification->getType()) {
            throw new \RuntimeException('Verification type not set');
        }
        $this->getVerificationController($verification->getType())->verify($verification, $data);
        $this->saveUserVerification($verification);
    }

    public function saveUserVerification(UserVerification $verification): void
    {
        $previous = $verification->getId() ? $this->getVerificationById($verification->getId()) : null;
        $event = new PreUserVerificationSaveEvent();
        $event->setCurrent($verification);
        $event->setPrevious($previous);
        $this->dispatcher->dispatch($event);

        $this->repository->saveVerification($verification);

        $event = new PostUserVerificationSaveEvent();
        $event->setCurrent($verification);
        $event->setPrevious($previous);
        $this->dispatcher->dispatch($event);;
    }

    private function getVerificationController(UserVerificationType $type): AbstractVerificationController
    {
        if (!class_exists($type->getController())) {
            throw new \RuntimeException('Verification controller does not exist');
        }

        $controller = $this->locator->getClassAutoWire($type->getController());
        $controller->setVerification($this);
        return $controller;
    }

    public function addHistoryToVerification(UserVerification $verification, string $entry): void
    {
        $this->repository->addHistoryToVerification($verification, $entry);
    }

    private function getUniqueCode(): string
    {
        $code = StringUtilities::generateRandomString(6);
        while (!$this->repository->getVerificationByCode($code)) {
            $code = StringUtilities::generateRandomString(6);
        }
        return $code;
    }

    private function getUniqueToken(): string
    {
        $token = StringUtilities::generateRandomToken();
        while (!$this->repository->getVerificationByToken($token)) {
            $token = StringUtilities::generateRandomToken();
        }
        return $token;
    }
}
