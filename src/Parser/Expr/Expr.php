<?php

declare(strict_types=1);

namespace PHPLox\Parser\Expr;

use PHPLox\Visitor\ExprVisitor;

abstract readonly class Expr
{
    abstract public function accept(ExprVisitor $visitor): mixed;
}
