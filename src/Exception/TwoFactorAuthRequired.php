<?php

namespace Pantono\Authentication\Exception;

class TwoFactorAuthRequired extends \RuntimeException
{
    public function __construct()
    {
        parent::__construct('Two Factor Authentication Required', 401);
    }
}
