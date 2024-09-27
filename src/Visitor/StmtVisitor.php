<?php

namespace PHPLox\Visitor;

use PHPLox\Parser\Stmt\{
    ExpressionStmt,
    PrintStmt,
};

interface StmtVisitor
{
    public function visitExpressionStmt(ExpressionStmt $expr_stmt): mixed;
    public function visitPrintStmt(PrintStmt $printstmt): mixed;
}
