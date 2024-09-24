<?php

namespace PHPLox\Interpreter;

use PHPLox\Exception\RuntimeError;
use PHPLox\Lox;
use PHPLox\Parser\Expr\Binary;
use PHPLox\Parser\Expr\Expr;
use PHPLox\Parser\Expr\Grouping;
use PHPLox\Parser\Expr\Literal;
use PHPLox\Parser\Expr\Unary;
use PHPLox\Scanner\Token\TokenType;
use PHPLox\Visitor\ExprVisitor;

/**
 * Class Interpreter
 *
 * This class implements the interpreter for the Lox language. It evaluates expressions and returns the result of the
 * interpretation.
 */
class Interpreter implements ExprVisitor
{
    /**
     * Interpret the given expression.
     *
     * @param Expr $expr The expression to interpret.
     *
     * @return string|null The result of the interpretation.
     */
    public function interpret(Expr $expr): ?string
    {
        try {
            $value = $this->evaluate($expr);
            return $this->stringify($value);
        } catch (RuntimeError $error) {
            Lox::runtime_error($error);
            return null;
        }
    }

    /**
     * Evaluate the given literal expression.
     *
     * @param Literal $literal The literal expression to evaluate.
     *
     * @return mixed The result of the evaluation.
     */
    public function visitLiteral(Literal $literal): mixed
    {
        return $literal->value;
    }

    /**
     * Evaluate the given grouping expression.
     *
     * @param Grouping $grouping The grouping expression to evaluate.
     *
     * @return mixed The result of the evaluation.
     */
    public function visitGrouping(Grouping $grouping): mixed
    {
        return $this->evaluate($grouping->expression);
    }

    /**
     * Evaluate the given unary expression.
     *
     * @param Unary $unary The unary expression to evaluate.
     *
     * @return mixed The result of the evaluation.
     */
    public function visitUnary(Unary $unary): mixed
    {
        $right = $this->evaluate($unary->right);

        switch ($unary->operator->type) {
            case TokenType::MINUS:
                $this->check_number_operand($unary->operator, $right);
                return is_int($right) ? -$right : -(float)$right;
            case TokenType::BANG:
                return !$this->is_truthy($right);
            default:
                return null; // unreachable
        }
    }

    /**
     * Evaluate the given binary expression.
     *
     * @param Binary $binary The binary expression to evaluate.
     *
     * @return mixed The result of the evaluation.
     */
    public function visitBinary(Binary $binary): mixed
    {
        $left = $this->evaluate($binary->left);
        $right = $this->evaluate($binary->right);

        switch ($binary->operator->type) {
            case TokenType::GREATER:
                $this->check_number_operands($binary->operator, $left, $right);
                return (float)$left > (float)$right;
            case TokenType::GREATER_EQUAL:
                $this->check_number_operands($binary->operator, $left, $right);
                return (float)$left >= (float)$right;
            case TokenType::LESS:
                $this->check_number_operands($binary->operator, $left, $right);
                return (float)$left < (float)$right;
            case TokenType::LESS_EQUAL:
                $this->check_number_operands($binary->operator, $left, $right);
                return (float)$left <= (float)$right;
            case TokenType::MINUS:
                $this->check_number_operands($binary->operator, $left, $right);
                return is_int($left) && is_int($right) ? $left - $right : (float)$left - (float)$right;
            case TokenType::PLUS:
                if (is_int($left) && is_int($right) || is_float($left) && is_float($right)) {
                    return $left + $right;
                }

                if (is_float($right) || is_float($left)) {
                    return (float)$left + (float)$right;
                }

                if (is_string($left) && is_string($right)) {
                    return $left . $right;
                }

                throw new RuntimeError($binary->operator, 'Operands must be two numbers or two strings.');
            case TokenType::SLASH:
                $this->check_number_operands($binary->operator, $left, $right);

                if ((float)$right === 0.0) {
                    throw new RuntimeError($binary->operator, 'Division by zero.');
                }

                return is_int($left) && is_int($right) ? $left / $right : (float)$left / (float)$right;
            case TokenType::STAR:
                $this->check_number_operands($binary->operator, $left, $right);

                return is_int($left) && is_int($right) ? $left * $right : (float)$left * (float)$right;
            case TokenType::BANG_EQUAL:
                return !$this->is_equal($left, $right);
            case TokenType::EQUAL_EQUAL:
                return $this->is_equal($left, $right);
            default:
                return null; // unreachable
        }
    }

    /**
     * Evaluate the given expression.
     *
     * @param Expr $expr The expression to evaluate.
     *
     * @return mixed The result of the evaluation.
     */
    private function evaluate(Expr $expr): mixed
    {
        return $expr->accept($this);
    }

    /**
     * Check if the given object is truthy.
     *
     * @param mixed $object The object to check.
     *
     * @return bool True if the object is truthy, false otherwise.
     */
    private function is_truthy(mixed $object): bool
    {
        return match (true) {
            is_null($object) => false,
            is_bool($object) => $object,
            default => true,
        };
    }

    /**
     * Check if the given objects are equal.
     *
     * @param mixed $a The first object to compare.
     * @param mixed $b The second object to compare.
     *
     * @return bool True if the objects are equal, false otherwise.
     */
    private function is_equal(mixed $a, mixed $b): bool
    {
        if (is_null($a) && is_null($b)) {
            return true;
        }

        if (is_null($a) || is_null($b)) {
            return false;
        }

        return $a === $b;
    }

    /**
     * Check if the given operand is a number.
     *
     * @param mixed $operator The operator token.
     * @param mixed $operand The operand to check.
     *
     * @throws RuntimeError If the operand is not a number.
     */
    private function check_number_operand($operator, $operand): void
    {
        if (is_int($operand) || is_float($operand)) {
            return;
        }

        throw new RuntimeError($operator, 'Operand must be a number.');
    }

    private function check_number_operands($operator, $left, $right): void
    {
        if ((is_int($left) || is_float($left)) && (is_int($right) || is_float($right))) {
            return;
        }

        throw new RuntimeError($operator, 'Operands must be numbers.');
    }

    /**
     * Convert the given object to a string.
     *
     * @param mixed $object The object to convert.
     *
     * @return string The string representation of the object.
     */
    private function stringify(mixed $object): string
    {
        if (is_null($object)) {
            return 'nil';
        }

        if (is_bool($object)) {
            return $object ? 'true' : 'false';
        }

        if (is_float($object)) {
            $text = (string)$object;
            if (str_contains($text, '.')) {
                return $text;
            }

            return $text . '.0';
        }

        return (string)$object;
    }
}