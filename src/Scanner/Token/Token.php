<?php

declare(strict_types=1);

namespace PHPLox\Scanner\Token;

final readonly class Token
{
    public function __construct(
        public TokenType $type,
        public string $lexeme,
        public mixed $literal,
        public int $line,
    ){}

    public function __toString(): string
    {
        return $this->type->name . " " . $this->lexeme . " " . ($this->literal ?: "");
    }
}