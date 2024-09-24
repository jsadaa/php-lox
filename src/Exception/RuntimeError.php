<?php

namespace PHPLox\Exception;

use PHPLox\Scanner\Token\Token;

class RuntimeError extends \RuntimeException
{
    public readonly Token $token;

    public function __construct(Token $token, string $message)
    {
        parent::__construct($message);
        $this->token = $token;
    }
}