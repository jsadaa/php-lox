<?php

declare(strict_types=1);

namespace PHPLox\Parser\Expr;

use PHPLox\Scanner\Token\Token;
use PHPLox\Visitor\ExprVisitor;

final readonly class Unary extends Expr
{
    public function __construct(
        public Token $operator, 
        public Expr $right, 
    )
    {
    }

    public function accept(ExprVisitor $visitor): mixed
    {
        return $visitor->visitUnary($this);
    }
}
