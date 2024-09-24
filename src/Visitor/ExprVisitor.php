<?php

declare(strict_types=1);

namespace PHPLox\Visitor;

use PHPLox\Parser\Expr\Binary;
use PHPLox\Parser\Expr\Grouping;
use PHPLox\Parser\Expr\Literal;
use PHPLox\Parser\Expr\Unary;

interface ExprVisitor
{
    public function visitBinary(Binary $binary): mixed;
    public function visitGrouping(Grouping $grouping): mixed;
    public function visitLiteral(Literal $literal): mixed;
    public function visitUnary(Unary $unary): mixed;
}
