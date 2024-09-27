<?php

declare(strict_types=1);

namespace PHPLox\Parser;

use PHPLox\Exception\ParseError;
use PHPLox\Lox;
use PHPLox\Parser\Expr\Binary;
use PHPLox\Parser\Expr\Grouping;
use PHPLox\Parser\Expr\Literal;
use PHPLox\Parser\Expr\Unary;
use PHPLox\Parser\Stmt\ExpressionStmt;
use PHPLox\Parser\Stmt\PrintStmt;
use PHPLox\Parser\Stmt\Stmt;
use PHPLox\Scanner\Token\Token;
use PHPLox\Scanner\Token\TokenType;

/**
 * Class Parser
 *
 * This class is responsible for parsing a list of tokens and producing an abstract syntax tree (AST).
 */
class Parser
{
    /**
     * @var Token[] List of tokens to be parsed.
     */
    private readonly array $tokens;

    /**
     * @var int Current position in the token list.
     */
    private int $current = 0;

    /**
     * Parser constructor.
     *
     * @param Token[] $tokens List of tokens to be parsed.
     */
    public function __construct(array $tokens)
    {
        $this->tokens = $tokens;
    }

    /**
     * Parses the list of tokens and returns a list of statements.
     *
     * @return Stmt[] The list of parsed statements.
     */
    public function parse(): array
    {
        $statements = [];

        while (!$this->is_at_end()) {
            $statements[] = $this->statement();
        }

        return $statements;
    }

    /**
     * Parses a statement.
     *
     * @return ExpressionStmt|PrintStmt The parsed statement.
     */
    private function statement(): ExpressionStmt|PrintStmt
    {
        if ($this->match(TokenType::PRINT)) {
            return $this->print_statement();
        }

        return $this->expression_statement();
    }

    /**
     * Parses a print statement.
     *
     * @return PrintStmt The parsed print statement.
     */
    private function print_statement(): PrintStmt
    {
        $value = $this->expression();

        $this->consume(TokenType::SEMICOLON, "Expect ';' after value.");

        return new PrintStmt($value);
    }

    /**
     * Parses an expression statement.
     *
     * @return ExpressionStmt The parsed expression statement.
     */
    private function expression_statement(): ExpressionStmt
    {
        $expr = $this->expression();

        $this->consume(TokenType::SEMICOLON, "Expect ';' after expression.");

        return new ExpressionStmt($expr);
    }

    /**
     * Parses an expression.
     *
     * @return Binary|Unary|Grouping|Literal The parsed expression.
     */
    private function expression(): Binary|Unary|Grouping|Literal
    {
        return $this->equality();
    }

    /**
     * Parses an equality expression.
     *
     * @return Binary|Unary|Grouping|Literal The parsed equality expression.
     */
    private function equality(): Binary|Unary|Grouping|Literal
    {
        $expr = $this->comparison();

        while ($this->match(TokenType::BANG, TokenType::BANG_EQUAL)) {
            $op = $this->previous();
            $right = $this->comparison();
            $expr = new Binary($expr, $op, $right);
        }

        return $expr;
    }

    /**
     * Parses a comparison expression.
     *
     * @return Binary|Unary|Grouping|Literal The parsed comparison expression.
     */
    private function comparison(): Binary|Unary|Grouping|Literal
    {
        $expr = $this->term();

        while ($this->match(
            TokenType::GREATER,
            TokenType::GREATER_EQUAL,
            TokenType::LESS,
            TokenType::LESS_EQUAL,
        )) {
            $op = $this->previous();
            $right = $this->term();
            $expr = new Binary($expr, $op, $right);
        }

        return $expr;
    }

    /**
     * Parses a term expression.
     *
     * @return Binary|Unary|Grouping|Literal The parsed term expression.
     */
    private function term(): Binary|Unary|Grouping|Literal
    {
        $expr = $this->factor();

        while ($this->match(TokenType::MINUS, TokenType::PLUS)) {
            $op = $this->previous();
            $right = $this->factor();

            $expr = new Binary($expr, $op, $right);
        }

        return $expr;
    }

    /**
     * Parses a factor expression.
     *
     * @return Binary|Unary|Grouping|Literal The parsed factor expression.
     */
    private function factor(): Binary|Unary|Grouping|Literal
    {
        $expr = $this->unary();

        while ($this->match(TokenType::SLASH, TokenType::STAR)) {
            $op = $this->previous();
            $right = $this->unary();

            $expr = new Binary($expr, $op, $right);
        }

        return $expr;
    }

    /**
     * Parses a unary expression.
     *
     * @return Unary|Grouping|Literal The parsed unary expression.
     */
    private function unary(): Unary|Grouping|Literal
    {
        if ($this->match(TokenType::BANG, TokenType::MINUS)) {
            $op = $this->previous();
            $right = $this->unary();

            return new Unary($op, $right);
        }

        return $this->primary();
    }

    /**
     * Parses a primary expression.
     *
     * @return Grouping|Literal The parsed primary expression.
     */
    private function primary(): Grouping|Literal
    {
        return match (true) {
            $this->match(TokenType::FALSE) => new Literal(false),
            $this->match(TokenType::TRUE) => new Literal(true),
            $this->match(TokenType::NIL) => new Literal(null),
            $this->match(TokenType::NUMBER, TokenType::STRING) => new Literal($this->previous()->literal),
            $this->match(TokenType::LEFT_PAREN) => (function () {
                $expr = $this->expression();
                $this->consume(TokenType::RIGHT_PAREN, "Expect ')' after expression.");
                return new Grouping($expr);
            })(),
            default => throw $this->error($this->peek(), "Expect expression."),
        };
    }

    /**
     * Checks if the current token matches any of the given types.
     *
     * @param TokenType ...$types The token types to match.
     * @return bool True if a match is found, false otherwise.
     */
    private function match(TokenType ...$types): bool
    {
        for ($i = 0; $i < count($types) ; $i++) {
            if ($this->check($types[$i])) {
                $this->advance();
                return true;
            }
        }

        return false;
    }

    /**
     * Checks if the current token is of the given type.
     *
     * @param TokenType $type The token type to check.
     * @return bool True if the current token is of the given type, false otherwise.
     */
    private function check(TokenType $type): bool
    {
        if ($this->is_at_end()) {
            return false;
        }

        return $this->peek()->type == $type;
    }

    /**
     * Advances to the next token.
     *
     * @return Token The previous token.
     */
    private function advance(): Token
    {
        if (!$this->is_at_end()) {
            $this->current++;
        }

        return $this->previous();
    }

    /**
     * Returns the current token.
     *
     * @return Token The current token.
     */
    private function peek(): Token
    {
        return $this->tokens[$this->current];
    }

    /**
     * Returns the previous token.
     *
     * @return Token The previous token.
     */
    private function previous(): Token
    {
        return $this->tokens[$this->current - 1];
    }

    /**
     * Consumes the current token if it is of the given type.
     *
     * @param TokenType $type The token type to consume.
     * @param string $message The error message if the token type does not match.
     * @return Token The consumed token.
     * @throws ParseError If the token type does not match.
     */
    private function consume(TokenType $type, string $message): Token
    {
        if ($this->check($type)) {
            return $this->advance();
        }

        throw $this->error($this->peek(), $message);
    }

    /**
     * Synchronizes the parser state after a parse error.
     */
    private function synchronize(): void
    {
        $this->advance();

        while (!$this->is_at_end()) {
            if ($this->previous()->type == TokenType::SEMICOLON) {
                return;
            }

            switch ($this->peek()->type) {
                case TokenType::CLASS_DECLARATION:
                case TokenType::FUN:
                case TokenType::VAR:
                case TokenType::FOR:
                case TokenType::IF:
                case TokenType::WHILE:
                case TokenType::PRINT:
                case TokenType::RETURN:
                    return;
            }

            $this->advance();
        }
    }

    /**
     * Checks if the parser has reached the end of the token list.
     *
     * @return bool True if at the end of the token list, false otherwise.
     */
    private function is_at_end(): bool
    {
        return $this->peek()->type == TokenType::EOF;
    }

    /**
     * Creates a parse error.
     *
     * @param Token $token The token where the error occurred.
     * @param string $message The error message.
     * @return ParseError The created parse error.
     */
    private function error(Token $token, string $message): ParseError
    {
        Lox::parser_error($token, $message);
        return new ParseError();
    }
}