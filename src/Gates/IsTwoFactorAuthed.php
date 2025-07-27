<?php

namespace Pantono\Authentication\Gates;

use Pantono\Contracts\Security\Gate\SecurityGateInterface;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Pantono\Contracts\Endpoint\EndpointDefinitionInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Pantono\Authentication\TwoFactorAuth;
use Pantono\Authentication\Exception\TwoFactorAuthRequired;

class IsTwoFactorAuthed implements SecurityGateInterface
{
    private TwoFactorAuth $twoFactorAuth;

    public function __construct(TwoFactorAuth $twoFactorAuth)
    {
        $this->twoFactorAuth = $twoFactorAuth;
    }

    public function isValid(Request $request, EndpointDefinitionInterface $endpoint, ParameterBag $options, ?Session $session = null): void
    {
        if ($session && $session->has('tfa_attempt_id')) {
            $attemptId = $session->get('tfa_attempt_id');
            $attempt = $this->twoFactorAuth->getAttemptById($attemptId);
            if ($attempt && $attempt->isVerified() && $attempt->getDateExpires() > new \DateTime()) {
                return;
            }
        }
        throw new TwoFactorAuthRequired();
    }
}
