<?php namespace App\Http\Utils\Filters\SQL;
/*
 * Copyright 2026 OpenStack Foundation
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 * http://www.apache.org/licenses/LICENSE-2.0
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 **/

use utils\Filter;
use utils\FilterElement;
use utils\FilterMapping;

/**
 * Class SQLRawFilterMapping
 *
 * Raw SQL counterpart of DoctrineFilterMapping: takes a condition carrying the
 * :operator and :value placeholders and renders it for Filter::toRawSQL, binding
 * each value as a named parameter instead of interpolating it.
 *
 * @package App\Http\Utils\Filters\SQL
 */
class SQLRawFilterMapping extends FilterMapping
{
    /**
     * @param string $condition
     */
    public function __construct(string $condition)
    {
        parent::__construct('', $condition);
    }

    /**
     * @param FilterElement $filter
     * @param array $bindings bindings already collected by the caller
     * @return string
     */
    public function toRawSQL(FilterElement $filter, array $bindings = []): string
    {
        $this->bindings = [];
        $param_idx = count($bindings) + 1;

        $value = $filter->getValue();
        $operator = $filter->getOperator();

        if (!is_array($value)) {
            return $this->renderCondition(
                $value,
                is_array($operator) ? $operator[0] : $operator,
                $param_idx
            );
        }

        $conditions = [];
        foreach ($value as $idx => $v) {
            $conditions[] = $this->renderCondition(
                $v,
                is_array($operator) ? $operator[$idx] : $operator,
                $param_idx++
            );
        }

        $same_field_op = $filter->getSameFieldOp() ?? Filter::MainOperatorOr;

        return '( ' . implode(sprintf(' %s ', $same_field_op), $conditions) . ' )';
    }

    /**
     * @param mixed $value
     * @param string $operator
     * @param int $param_idx
     * @return string
     */
    private function renderCondition($value, string $operator, int $param_idx): string
    {
        $param = sprintf(Filter::ParamPrefix, $param_idx);
        $this->bindings[$param] = $value;

        return str_replace(
            [Filter::OperatorPlaceholder, Filter::ValuePlaceholder],
            [$operator, ':' . $param],
            $this->where
        );
    }
}
