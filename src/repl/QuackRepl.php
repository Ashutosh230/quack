<?php
/**
 * Quack Compiler and toolkit
 * Copyright (C) 2016 Marcelo Camargo <marcelocamargo@linuxmail.org> and
 * CONTRIBUTORS.
 *
 * This file is part of Quack.
 *
 * Quack is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Quack is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Quack.  If not, see <http://www.gnu.org/licenses/>.
 */
define('BASE_PATH', __DIR__ . '/..');
require_once(BASE_PATH . '/toolkit/QuackToolkit.php');

use \QuackCompiler\Lexer\Tokenizer;
use \QuackCompiler\Parser\SyntaxError;
use \QuackCompiler\Parser\TokenReader;
use \QuackCompiler\Scope\Scope;

function isPOSIX()
{
    static $value;
    if (null === $value) {
        $value = strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN';
    }
    return $value;
}

function session()
{
    static $session;
    if (null === $session) {
        $session = (object) [
            // TODO: Include other properties here later, such as scope and AST
            'command' => "",
            'complete_stmt' => true
        ];
    }

    return $session;
}

function is_complete($str = "")
{
    // TODO: Think in a beter way to do it
    // If end of the source is found, then user did not finish writing
    $match = "end of the source";
    $complete = strpos($str, $match) === false;
    return $complete;
}

function start_repl()
{
    $dot = isPOSIX() ? '·' : '-';
    echo <<<LICENSE
Quack {$dot} Copyright (C) 2016 Marcelo Camargo
This program comes with ABSOLUTELY NO WARRANTY.
This is free software, and you are welcome to redistribute it
under certain conditions; type 'show c' for details.\n
LICENSE
    ;
    echo "Use quack --help for more information", PHP_EOL;

    if (args_have('-h', '--help')) {
        open_repl_help();
        return;
    }

    repl();
}

function install_stream_handler()
{
    $prompt = session()->complete_stmt ? "Quack> " : "\__(\"> ";
    if (isPOSIX()) {
        begin_yellow();
        readline_callback_handler_install($prompt, 'readline_callback');
        end_yellow();
    } else {
        echo "$prompt";
    }
}

function begin_yellow()
{
    echo "\033[01;33m";
}

function end_yellow()
{
    echo "\033[0m";
}

function print_entire_license()
{
    echo file_get_contents(__DIR__ . "/../../LICENSE.md");
}

function readline_callback($command)
{
    $command = trim($command);

    switch (trim($command)) {
        case ':quit':
        case ':q':
            exit;
        case 'show c':
            print_entire_license();
            goto next;
        case '':
            goto next;
        case ':clear':
            $clear = isPOSIX() ? 'clear' : 'cls';
            system($clear);
            goto next;
    }

    if (!session()->complete_stmt) {
      $command = session()->command . " " . $command;
    }

    $lexer = new Tokenizer($command);
    $parser = new TokenReader($lexer);

    try {
        $global_scope = new Scope;
        $parser->parse();
        $parser->ast->injectScope($global_scope);
        $parser->ast->runTypeChecker();
        session()->complete_stmt = true;
        /* when */// args_have('-a', '--ast') && var_dump($parser->ast);
        /* when */ args_have('-f', '--format') && $parser->format();
    } catch (SyntaxError $e) {
        $session = session();
        if (is_complete($e)) {
            $session->command = "";
            $session->complete_stmt = true;

            echo $e;
        } else {
            $session->command = $command;
            $session->complete_stmt = false;
        }
    } catch (\Exception $e) {
        echo $e;
    }

    next:
    if (isPOSIX()) {
        readline_add_history($command);
    }

    install_stream_handler();
}

function repl()
{
    $title = "Quack interactive mode";
    if (isPOSIX()) {
        fwrite(STDOUT, "\x1b]2;{$title}\x07");
    } else {
        `title {$title}`;
    }

    echo "Type ^C or :quit to leave", PHP_EOL;
    install_stream_handler();

    while (true) {
        if (isPOSIX()) {
            $write = null;
            $except = null;
            $stream = @stream_select($read = [STDIN], $write, $except, null);

            if ($stream && in_array(STDIN, $read)) {
                readline_callback_read_char();
            }
        } else {
            $line = stream_get_line(STDIN, 1024, PHP_EOL);
            readline_callback($line);
        }
    }
}

function open_repl_help()
{
    // TODO
}

function args_have()
{
    global $argv;
    return count(array_intersect($argv, func_get_args())) > 0;
}

start_repl();
