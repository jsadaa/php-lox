<?php

declare(strict_types=1);

namespace PHPLox\Parser\Stmt;

use PHPLox\Visitor\StmtVisitor;

abstract readonly class Stmt
{
    abstract public function accept(StmtVisitor $visitor): mixed;
}
