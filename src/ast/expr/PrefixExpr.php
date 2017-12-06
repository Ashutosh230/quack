<?php
/**
 * Quack Compiler and toolkit
 * Copyright (C) 2015-2017 Quack and CONTRIBUTORS
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
namespace QuackCompiler\Ast\Expr;

use \QuackCompiler\Ast\Expr;
use \QuackCompiler\Ast\Node;
use \QuackCompiler\Ds\Set;
use \QuackCompiler\Intl\Localization;
use \QuackCompiler\Lexer\Tag;
use \QuackCompiler\Lexer\Token;
use \QuackCompiler\Parser\Parser;
use \QuackCompiler\Pretty\Parenthesized;
use \QuackCompiler\Scope\Meta;
use \QuackCompiler\Scope\Scope;
use \QuackCompiler\Types\TypeError;
use \QuackCompiler\Types\Unification;

class PrefixExpr extends Node implements Expr
{
    use Parenthesized;

    private $operator;
    private $right;

    public function __construct(Token $operator, Expr $right)
    {
        $this->operator = $operator->getTag();
        $this->right = $right;
    }

    public function format(Parser $parser)
    {
        $source = Tag::T_NOT === $this->operator
            ? 'not '
            : $this->operator;
        $source .= $this->right->format($parser);

        return $this->parenthesize($source);
    }

    public function injectScope($parent_scope)
    {
        $this->scope = $parent_scope;
        $this->right->injectScope($parent_scope);
    }

    public function analyze(Scope $scope, Set $non_generic)
    {
        $op_name = Tag::getOperatorLexeme($this->operator);
        $right_type = $this->right->analyze($scope, $non_generic);

        $type_error = new TypeError(Localization::message('TYP230', [$op_name, $right_type]));

        switch ($this->operator) {
            case '+':
            case '-':
                $number = $scope->getPrimitiveType('Number');
                try {
                    Unification::unify($right_type, $number);
                    return $number;
                } catch (TypeError $error) {
                    throw $type_error;
                }
            case Tag::T_NOT:
                $bool = $this->scope->getPrimitiveType('Bool');
                try {
                    Unification::unify($right_type, $bool);
                    return $bool;
                } catch (TypeError $error) {
                    throw $type_error;
                }
        }
    }
}
