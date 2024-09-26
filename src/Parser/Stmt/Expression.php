<?php

declare(strict_types=1);

namespace PHPLox\Parser\Stmt;

use PHPLox\Parser\Expr\Expr;
use PHPLox\Visitor\StmtVisitor;

final readonly class Expression extends Stmt
{
    public function __construct(
        public Expr $expression, 
    )
    {
    }

    public function accept(StmtVisitor $visitor): mixed
    {
        return $visitor->visitExpression($this);
    }
}
