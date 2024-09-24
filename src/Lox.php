<?php

declare(strict_types=1);

namespace PHPLox;

use PHPLox\Exception\RuntimeError;
use PHPLox\Interpreter\Interpreter;
use PHPLox\Parser\Parser;
use PHPLox\Scanner\Scanner;
use PHPLox\Scanner\Token\Token;
use PHPLox\Scanner\Token\TokenType;

/**
 * Class Lox
 *
 * This class serves as the entry point for the Lox interpreter. It handles command-line arguments,
 * reads source files, and runs the interpreter in either file mode or interactive prompt mode.
 */
final class Lox
{
    /**
     * @var array Command-line arguments passed to the script.
     */
    private readonly array $args;
    /**
     * @var bool Indicates if any errors have occurred during scanning or parsing.
     */
    private static bool $had_errors = false;
    /**
     * @var bool Indicates if a runtime error has occurred.
     */
    private static bool $had_runtime_error = false;
    /**
     * @var Interpreter The interpreter instance.
     */
    private static Interpreter $interpreter;

    /**
     * Lox constructor.
     *
     * @param array $args Command-line arguments passed to the script.
     */
    public function __construct(array $args)
    {
        $this->args = $args;
        self::$interpreter = new Interpreter();
    }

    /**
     * Invokes the Lox interpreter.
     *
     * This method determines the mode of operation (file mode or prompt mode) based on the command-line arguments.
     * It then calls the appropriate method to run the interpreter.
     */
    public function __invoke(): void
    {
        $argc = \count($this->args);

        if ($argc > 2) {
            \fwrite(STDERR, "Usage: php lox.php [script]\n");
            exit(64);
        }

        if ($argc == 2) {
            $this->run_file($this->args[1]);
        } else {
            $this->run_prompt();
        }
    }

    /**
     * Runs the interpreter in file mode.
     *
     * This method reads the source code from the specified file and runs the interpreter on it.
     * If any errors occur during scanning or parsing, it exits with an error code.
     *
     * @param string $path The path to the source file.
     */
    private function run_file(string $path): void
    {
        $src_bytes = \unpack('C*', $this->read_file($path));
        $src_str = \implode(\array_map('chr', $src_bytes));

        $this->run($src_str);

        if (Lox::$had_errors) {
            exit(65);
        }

        if (Lox::$had_runtime_error) {
            exit(70);
        }
    }

    /**
     * Reads the contents of a file.
     *
     * This method opens the specified file, reads its contents, and returns them as a string.
     * If the file cannot be opened, it exits with an error code.
     *
     * @param string $path The path to the file.
     * @return string The contents of the file.
     */
    private function read_file(string $path): string
    {
        $file_handle = \fopen($path, 'rb');

        if ($file_handle === false) {
            \fwrite(STDERR, "Could not open file \"$path\".\n");
            exit(66);
        }
        $file_size = \filesize($path);

        return \fread($file_handle, $file_size);
    }

    /**
     * Runs the interpreter in interactive prompt mode.
     *
     * This method repeatedly reads a line of input from the user, runs the interpreter on it,
     * and prints the result. It continues until the user exits the prompt.
     */
    private function run_prompt(): void
    {
        while (true) {
            \fwrite(STDOUT, "> ");
            $line = \readline();

            if ($line === false) {
                \fwrite(STDERR, "\n");
                break;
            }

            $this->run($line);
            Lox::$had_errors = false;
        }
    }

    /**
     * Runs the interpreter on the given source code.
     *
     * This method scans the source code into tokens, parses the tokens into an abstract syntax tree (AST),
     * and prints the resulting AST. If any errors occur during scanning or parsing, it returns early.
     *
     * @param string $source The source code to be interpreted.
     */
    private function run(string $source): void
    {
        $scanner = new Scanner($source);
        $tokens = $scanner->scan_tokens();

        $parser = new Parser($tokens);
        $expression = $parser->parse();

        if (Lox::$had_errors) {
            return;
        }

        $result = self::$interpreter->interpret($expression);

        if ($result !== null) {
            \fwrite(STDOUT, $result . "\n");
        }

        Lox::$had_runtime_error = false;

        \readline_add_history($source);
    }

    /**
     * Reports a scanning error.
     *
     * This method reports a scanning error at the specified line with the given message.
     *
     * @param int $line The line number where the error occurred.
     * @param string $message The error message.
     */
    public static function scanner_error(int $line, string $message): void
    {
        Lox::report($line, "", $message);
    }

    /**
     * Reports a parsing error.
     *
     * This method reports a parsing error at the specified token with the given message.
     * If the error occurred at the end of the file, it indicates that in the error message.
     *
     * @param Token $token The token where the error occurred.
     * @param string $message The error message.
     */
    public static function parser_error(Token $token, string $message): void
    {
        if ($token->type == TokenType::EOF) {
            Lox::report($token->line, " at end", $message);
        } else {
            Lox::report($token->line, " at '" . $token->lexeme . "'", $message);
        }
    }

    /**
     * Reports a runtime error.
     *
     * This method reports a runtime error with the given message.
     *
     * @param RuntimeError $error The runtime error that occurred.
     */
    public static function runtime_error(RuntimeError $error): void
    {
        \fwrite(STDERR, "{$error->getMessage()}\n[line {$error->token->line}]\n");
        Lox::$had_runtime_error = true;
    }

    /**
     * Reports an error.
     *
     * This method prints an error message to STDERR and sets the had_errors flag to true.
     *
     * @param int $line The line number where the error occurred.
     * @param string $where The location of the error.
     * @param string $message The error message.
     */
    private static function report(int $line, string $where, string $message): void
    {
        \fwrite(STDERR, "[line $line] Error$where: $message\n");
        Lox::$had_errors = true;
    }
}