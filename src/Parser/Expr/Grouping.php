<?php

declare(strict_types=1);

namespace PHPLox\Parser\Expr;

use PHPLox\Visitor\ExprVisitor;

final readonly class Grouping extends Expr
{
    public function __construct(
        public Expr $expression, 
    )
    {
    }

    public function accept(ExprVisitor $visitor): mixed
    {
        return $visitor->visitGrouping($this);
    }
}
