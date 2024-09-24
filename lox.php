<?php

require_once __DIR__ . '/vendor/autoload.php';

use PHPLox\Lox;

(new Lox($_SERVER['argv']))();