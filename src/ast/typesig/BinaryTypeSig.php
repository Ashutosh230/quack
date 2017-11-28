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
namespace QuackCompiler\Ast\TypeSig;

use \QuackCompiler\Ast\Node;
use \QuackCompiler\Ast\TypeSig;
use \QuackCompiler\Parser\Parser;
use \QuackCompiler\Pretty\Parenthesized;

class BinaryTypeSig extends Node implements TypeSig
{
    use Parenthesized;

    public $operator;
    public $left;
    public $right;

    public function __construct($left, $operator, $right)
    {
        $this->left = $left;
        $this->operator = $operator;
        $this->right = $right;
    }

    public function format(Parser $parser)
    {
        $source = $this->left->format($parser);
        $source .= " {$this->operator} ";
        $source .= $this->right->format($parser);

        return $this->parenthesize($source);
    }
}