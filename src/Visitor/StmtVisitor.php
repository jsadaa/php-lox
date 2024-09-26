<?php

namespace PHPLox\Visitor;

use PHPLox\Parser\Stmt\{
    Expression,
    PrintStmt,
};

interface StmtVisitor
{
    public function visitExpression(Expression $expression): mixed;
    public function visitPrintStmt(PrintStmt $printstmt): mixed;
}
