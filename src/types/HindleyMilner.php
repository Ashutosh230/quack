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
namespace QuackCompiler\Types;

use \QuackCompiler\Ds\Set;

class HindleyMilner
{
    public static function occursInType($variable, Type $type)
    {
        $pruned_expr = $type->prune();
        if ($pruned_expr === $variable) {
            return true;
        } else if ($pruned_expr instanceof TypeOperator) {
            return static::occursIn($variable, $pruned_expr->types);
        }

        return false;
    }

    public static function occursIn($variable, $types)
    {
        foreach ($types as $subtype) {
            if (static::occursInType($variable, $subtype)) {
                return true;
            }
        }

        return false;
    }

    public function isGeneric($variable, Set $non_generic)
    {
        return !static::occursIn($variable, $non_generic);
    }

    public function fresh(Type $type, Set $non_generic)
    {
        $mappings = [];
        $freshrec = function (Type $type) use ($non_generic, &$mappings) {
            $pruned = $type->prune();
            if ($pruned instanceof TypeVar) {
                if (static::isGeneric($pruned, $non_generic)) {
                    if (!isset($mappings[$pruned->id])) {
                        $mappings[$pruned->id] = new TypeVar();
                    }

                    return $mappings[$pruned->id];
                } else {
                    return $pruned;
                }
            } elseif ($pruned instanceof TypeOperator) {
                return new TypeOperator($pruned->getName(), array_map($freshrec, $pruned->types));
            }
        };

        return $freshrec($type);
    }

    public function unify(Type $t1, Type $t2)
    {
        $left = $t1->prune();
        $right = $t2->prune();

        if ($left instanceof TypeVar) {
            if ($left !== $right) {
                if (static::occursInType($left, $right)) {
                    throw new TypeError('Recursive unification');
                }

                $left->instance = $right;
            }
        } elseif ($left instanceof TypeOperator && $right instanceof TypeVar) {
            static::unify($right, $left);
        } elseif ($left instanceof TypeOperator && $right instanceof TypeOperator) {
            if ($left->name !== $right->name || count($left->types) !== count($right->types)) {
                throw new TypeError('Type mismatch: ' . $left . ' != ' . $right);
            }

            for ($i = 0; $i < count($left->types); $i++) {
                static::unify($left->types[$i], $right->types[$i]);
            }
        }
    }
}