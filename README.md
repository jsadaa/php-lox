# PHPLOX

This is a PHP implementation of the Lox language first interpreter from the amazing book [Crafting Interpreters](http://craftinginterpreters.com/) by Robert Nystrom.

## Notes

This is actually still a work in progress, but I'm trying to keep it as close to the original as possible (with some PHP specific changes and some personal tests).

While it could obviously not make sense to implement an interpreter for an interpreted language with another interpreted language, I'm doing this for fun and learning purposes !

## Installation

You can install the project with the following command:

```bash
composer install
```

## Usage

You can run the interpreter with the following command:

```bash
php lox.php
```

You can also run a Lox script with:

```bash
php lox.php path/to/your/script.lox
```