<?php

namespace Pantono\Authentication;

use Pantono\Authentication\Repository\ApiAuthenticationRepository;
use Pantono\Hydrator\Hydrator;
use Pantono\Authentication\Model\ApiToken;
use Pantono\Utilities\StringUtilities;

class ApiAuthentication
{
    private ApiAuthenticationRepository $repository;
    private Hydrator $hydrator;

    public function __construct(ApiAuthenticationRepository $repository, Hydrator $hydrator)
    {
        $this->repository = $repository;
        $this->hydrator = $hydrator;
    }

    public function getApiTokenByToken(string $token): ?ApiToken
    {
        return $this->hydrator->hydrate(ApiToken::class, $this->repository->getApiTokenByToken($token));
    }

    public function updateApiTokenLastSeen(ApiToken $token): void
    {
        $this->repository->updateApiTokenLastSeen($token);
    }

    public function createNewApplicationToken(string $applicationName, ?\DateTimeImmutable $dateExpires = null): ApiToken
    {
        if (!$dateExpires) {
            $dateExpires = new \DateTimeImmutable('+1 year');
        }
        $token = new ApiToken();
        $token->setToken($this->getAvailableToken());
        $token->setDateCreated(new \DateTimeImmutable());
        $token->setDateLastUsed(new \DateTimeImmutable());
        $token->setDateExpires($dateExpires);
        $token->setApplicationName($applicationName);
        $this->repository->saveToken($token);
        return $token;
    }

    private function getAvailableToken(): string
    {
        $token = StringUtilities::generateRandomString(50);
        while (!empty($this->repository->getApiTokenByToken($token))) {
            $token = StringUtilities::generateRandomString(50);
        }
        return $token;
    }
}
