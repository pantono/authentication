<?php

namespace Pantono\Authentication\Exception;

class TwoFactorAuthRequired extends \RuntimeException
{
    public function __construct(string $message)
    {
        parent::__construct($message, 402);
    }
}
