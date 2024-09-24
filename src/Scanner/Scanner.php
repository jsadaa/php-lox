<?php

declare(strict_types=1);

namespace PHPLox\Scanner;

use PHPLox\Lox;
use PHPLox\Scanner\Token\Token;
use PHPLox\Scanner\Token\TokenType;

/**
 * Class Scanner
 *
 * This class is responsible for scanning the source code and converting it into a list of tokens.
 * It implements a lexical analyzer for a small programming language.
 */
final class Scanner
{
    /**
     * @var array<string, TokenType> A map of keywords to their corresponding token types.
     */
    private static array $keywords = [
        'and' => TokenType::AND,
        'class' => TokenType::CLASS_DECLARATION,
        'else' => TokenType::ELSE,
        'false' => TokenType::FALSE,
        'for' => TokenType::FOR,
        'fun' => TokenType::FUN,
        'if' => TokenType::IF,
        'nil' => TokenType::NIL,
        'or' => TokenType::OR,
        'print' => TokenType::PRINT,
        'return' => TokenType::RETURN,
        'super' => TokenType::SUPER,
        'this' => TokenType::THIS,
        'true' => TokenType::TRUE,
        'var' => TokenType::VAR,
        'while' => TokenType::WHILE,
    ];

    /**
     * @var string The source code to be scanned.
     */
    private readonly string $source;

    /**
     * @var Token[] The list of tokens generated from the source code.
     */
    private array $tokens = [];

    /**
     * @var int The starting position of the current lexeme.
     */
    private int $start = 0;

    /**
     * @var int The current position in the source code.
     */
    private int $current = 0;

    /**
     * @var int The current line number in the source code.
     */
    private int $line = 1;

    /**
     * Scanner constructor.
     *
     * @param string $source The source code to be scanned.
     */
    public function __construct(string $source)
    {
        $this->source = $source;
    }

    /**
     * Scans the source code and returns the list of tokens.
     *
     * This method iterates through the source code, scanning each character and generating tokens.
     * It adds an EOF token at the end of the token list.
     *
     * @return Token[] The list of tokens generated from the source code.
     */
    public function scan_tokens(): array
    {
        while (!$this->is_at_end()) {
            $this->start = $this->current;
            $this->scan_token();
        }

        $this->tokens[] = new Token(TokenType::EOF, '', null, $this->line);

        return $this->tokens;
    }

    /**
     * Scans a single token from the source code.
     *
     * This method reads the next character from the source code and determines its type.
     * It generates the appropriate token and adds it to the token list.
     */
    private function scan_token(): void
    {
        $char = $this->advance();

        switch ($char) {
            case '(':
                $this->add_token(TokenType::LEFT_PAREN);
                break;
            case ')':
                $this->add_token(TokenType::RIGHT_PAREN);
                break;
            case '{':
                $this->add_token(TokenType::LEFT_BRACE);
                break;
            case '}':
                $this->add_token(TokenType::RIGHT_BRACE);
                break;
            case ',':
                $this->add_token(TokenType::COMMA);
                break;
            case '.':
                $this->add_token(TokenType::DOT);
                break;
            case '-':
                $this->add_token(TokenType::MINUS);
                break;
            case '+':
                $this->add_token(TokenType::PLUS);
                break;
            case ';':
                $this->add_token(TokenType::SEMICOLON);
                break;
            case '*':
                $this->add_token(TokenType::STAR);
                break;
            case '!':
                $this->add_token(
                    $this->match('=') ?
                        TokenType::BANG_EQUAL :
                        TokenType::BANG
                );
                break;
            case '=':
                $this->add_token(
                    $this->match('=') ?
                        TokenType::EQUAL_EQUAL :
                        TokenType::EQUAL
                );
                break;
            case '<':
                $this->add_token(
                    $this->match('=') ?
                        TokenType::LESS_EQUAL :
                        TokenType::LESS
                );
                break;
            case '>':
                $this->add_token(
                    $this->match('=') ?
                        TokenType::GREATER_EQUAL :
                        TokenType::GREATER
                );
                break;
            case '/':
                if ($this->match('/')) {
                    while ($this->peek() != "\n" && !$this->is_at_end()) {
                        $this->advance();
                    }
                } else {
                    $this->add_token(TokenType::SLASH);
                }
                break;
            case ' ':
            case "\r":
            case "\t":
                // Ignore whitespace.
                break;
            case "\n":
                $this->line++;
                break;
            case '"':
                $this->string();
                break;
            default:
                if ($this->is_digit($char)) {
                    $this->number();
                } else if ($this->is_alpha($char)) {
                    $this->identifier();
                } else {
                    Lox::scanner_error($this->line, 'Unexpected character.');
                }
                break;
        }
    }

    /**
     * Adds a token to the token list.
     *
     * This method creates a new token with the specified type and literal value, and adds it to the token list.
     *
     * @param TokenType $type The type of the token.
     * @param mixed $literal The literal value of the token (optional).
     */
    private function add_token(TokenType $type, mixed $literal = null): void
    {
        $text = \substr($this->source, $this->start, $this->current - $this->start);
        $this->tokens[] = new Token($type, $text, $literal, $this->line);
    }

    /**
     * Advances to the next character in the source code.
     *
     * This method increments the current position and returns the character at the previous position.
     *
     * @return string The next character in the source code.
     */
    private function advance(): string
    {
        $this->current++;
        return \substr($this->source, $this->current - 1, 1);
    }

    /**
     * Checks if the next character matches the expected character.
     *
     * This method checks if the next character in the source code matches the expected character.
     * If it does, it advances the current position and returns true. Otherwise, it returns false.
     *
     * @param string $expected The expected character.
     * @return bool True if the next character matches the expected character, false otherwise.
     */
    private function match(string $expected): bool
    {
        if ($this->is_at_end()) {
            return false;
        }

        if (\substr($this->source, $this->current, 1) != $expected) {
            return false;
        }

        $this->current++;
        return true;
    }

    /**
     * Peeks at the next character in the source code without advancing the current position.
     *
     * This method returns the next character in the source code without advancing the current position.
     *
     * @return string The next character in the source code.
     */
    private function peek(): string
    {
        if ($this->is_at_end()) {
            return "\0";
        }

        return \substr($this->source, $this->current, 1);
    }

    /**
     * Peeks at the character after the next character in the source code.
     *
     * This method returns the character after the next character in the source code without advancing the current position.
     *
     * @return string The character after the next character in the source code.
     */
    private function peek_next(): string
    {
        if ($this->current + 1 >= \strlen($this->source)) {
            return '\0';
        }

        return \substr($this->source, $this->current + 1, 1);
    }

    /**
     * Checks if the scanner has reached the end of the source code.
     *
     * This method returns true if the current position is at or beyond the end of the source code.
     *
     * @return bool True if at the end of the source code, false otherwise.
     */
    private function is_at_end(): bool
    {
        return $this->current >= \strlen($this->source);
    }

    /**
     * Checks if the given character is a digit.
     *
     * This method returns true if the given character is a digit (0-9).
     *
     * @param string $char The character to check.
     * @return bool True if the character is a digit, false otherwise.
     */
    private function is_digit(string $char): bool
    {
        return \ord($char) >= \ord('0') && \ord($char) <= \ord('9');
    }

    /**
     * Checks if the given character is an alphabetic character.
     *
     * This method returns true if the given character is an alphabetic character (a-z, A-z) or an underscore (_).
     *
     * @param string $char The character to check.
     * @return bool True if the character is an alphabetic character, false otherwise.
     */
    private function is_alpha(string $char): bool
    {
        return (\ord($char) >= \ord('a') && \ord($char) <= \ord('z')) ||
            (\ord($char) >= \ord('A') && \ord($char) <= \ord('Z')) ||
            $char == '_';
    }

    /**
     * Checks if the given character is an alphanumeric character.
     *
     * This method returns true if the given character is an alphanumeric character (a-z, A-z, 0-9) or an underscore (_).
     *
     * @param string $char The character to check.
     * @return bool True if the character is an alphanumeric character, false otherwise.
     */
    private function is_alpha_numeric(string $char): bool
    {
        return $this->is_alpha($char) || $this->is_digit($char);
    }

    /**
     * Retrieves a substring from the source code.
     *
     * This method returns a substring of the source code starting at the specified position and ending at the specified position.
     *
     * @param int $start The starting position of the substring.
     * @param int $end The ending position of the substring.
     * @return string The substring of the source code.
     */
    private function get_from_source(int $start, int $end): string
    {
        return \substr($this->source, $start, $end - $start);
    }

    /**
     * Scans a string literal from the source code.
     *
     * This method scans a string literal, handling escape sequences and unterminated strings.
     * It adds the scanned string token to the token list.
     */
    private function string(): void
    {
        while ($this->peek() !== '"' && !$this->is_at_end()) {
            if ($this->peek() == "\n") {
                $this->line++;
            }

            $this->advance();
        }

        if ($this->is_at_end()) {
            Lox::scanner_error($this->line, 'Unterminated string.');
            return;
        }

        $this->advance();

        $str_val = \substr(
            $this->source,
            $this->start + 1,
            $this->current - $this->start - 2
        );

        $this->add_token(TokenType::STRING, $str_val);
    }

    /**
     * Scans a number literal from the source code.
     *
     * This method scans a number literal, handling both integer and floating-point numbers.
     * It adds the scanned number token to the token list.
     */
    private function number(): void
    {
        while ($this->is_digit($this->peek())) {
            $this->advance();
        }

        if ($this->peek() == '.' && $this->is_digit($this->peek_next())) {
            // Consume the "."
            $this->advance();
            while ($this->is_digit($this->peek())) {
                $this->advance();
            }
        }

        $number_value = \substr(
            $this->source,
            $this->start,
            $this->current - $this->start,
        );

        $this->add_token(
            TokenType::NUMBER,
            \str_contains($number_value, '.') ?
                \floatval($number_value) :
                \intval($number_value)
        );
    }

    /**
     * Scans an identifier or keyword from the source code.
     *
     * This method scans an identifier or keyword, checking if it matches any reserved keywords.
     * It adds the scanned identifier or keyword token to the token list.
     */
    private function identifier(): void
    {
        while ($this->is_alpha_numeric($this->peek())) {
            $this->advance();
        }

        $text = \substr(
            $this->source,
            $this->start,
            $this->current - $this->start
        );

        $token_type = self::$keywords[$text] ?? TokenType::IDENTIFIER;
        $this->add_token($token_type);
    }
}