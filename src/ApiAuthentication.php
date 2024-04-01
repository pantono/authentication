<?php

namespace Pantono\Authentication;

use Pantono\Authentication\Repository\ApiAuthenticationRepository;
use Pantono\Hydrator\Hydrator;
use Pantono\Authentication\Model\ApiToken;

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
}
