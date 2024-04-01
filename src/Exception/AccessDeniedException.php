<?php

namespace Pantono\Authentication\Exception;

class AccessDeniedException extends \RuntimeException
{
    public function __construct(string $message)
    {
        parent::__construct($message, 401);
    }
}
