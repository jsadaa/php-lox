<?php

declare(strict_types=1);

namespace PHPLox;

use PHPLox\Parser\Expr\Binary;
use PHPLox\Parser\Expr\Expr;
use PHPLox\Parser\Expr\Grouping;
use PHPLox\Parser\Expr\Literal;
use PHPLox\Parser\Expr\Unary;
use PHPLox\Visitor\ExprVisitor;

class AstPrinter implements ExprVisitor
{
    public function print(Expr $expr): string
    {
        return $expr->accept($this);
    }

    public function visitBinary(Binary $binary): string
    {
        return $this->parenthesize($binary->operator->lexeme, $binary->left, $binary->right);
    }

    public function visitGrouping(Grouping $grouping): string
    {
        return $this->parenthesize("group", $grouping->expression);
    }

    public function visitLiteral(Literal $literal): string
    {
        if ($literal->value === null) return "nil";
        return (string)$literal->value;
    }

    public function visitUnary(Unary $unary): string
    {
        return $this->parenthesize($unary->operator->lexeme, $unary->right);
    }

    private function parenthesize(string $name, Expr ...$exprs): string
    {
        $builder = [];
        $builder[] = "(";
        $builder[] = $name;

        foreach ($exprs as $expr) {
            $builder[] = " ";
            $builder[] = $expr->accept($this);
        }

        $builder[] = ")";

        return implode("", $builder);
    }
}
