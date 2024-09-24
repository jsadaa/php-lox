<?php

declare(strict_types=1);

namespace PHPLox\Parser\Expr;

use PHPLox\Visitor\ExprVisitor;

final readonly class Literal extends Expr
{
    public function __construct(
        public mixed $value, 
    )
    {
    }

    public function accept(ExprVisitor $visitor): mixed
    {
        return $visitor->visitLiteral($this);
    }
}
