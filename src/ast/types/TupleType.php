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
namespace QuackCompiler\Ast\Types;

use \QuackCompiler\Ast\TypeAnnotation;
use \QuackCompiler\Pretty\Parenthesized;
use \QuackCompiler\Pretty\Types\TupleTypeRenderer;
use \QuackCompiler\Scope\Scope;
use \QuackCompiler\TypeChecker\TupleTypeChecker;

class TupleType extends TypeNode implements TypeAnnotation
{
    use TupleTypeChecker;
    use TupleTypeRenderer;
    use Parenthesized;

    public $types;
    public $size;

    public function __construct(...$types)
    {
        $this->types = $types;
        $this->size = count($types);
    }

    public function __toString()
    {
        return $this->parenthesize('#(' . implode(', ', $this->types) . ')');
    }
}
