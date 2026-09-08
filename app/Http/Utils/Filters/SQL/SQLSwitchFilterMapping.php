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

use utils\FilterElement;
use utils\FilterMapping;

/**
 * Class SQLSwitchFilterMapping
 *
 * Raw SQL counterpart of DoctrineSwitchFilterMapping: picks the condition by the
 * filter value, OR-ing the conditions of every value the element carries. The
 * conditions are literals, so nothing gets bound.
 *
 * @package App\Http\Utils\Filters\SQL
 */
class SQLSwitchFilterMapping extends FilterMapping
{
    /**
     * Condition for a value that must not restrict the result set. Spelled out rather
     * than left absent so that a multi-value element such as `==true||false` still
     * evaluates to true, the way the Doctrine switch mapping does.
     */
    const NoRestriction = '1 = 1';

    /**
     * @var array value => SQL condition
     */
    private $case_statements;

    /**
     * @param array $case_statements
     */
    public function __construct(array $case_statements = [])
    {
        parent::__construct('', '');
        $this->case_statements = $case_statements;
    }

    /**
     * @param FilterElement $filter
     * @param array $bindings
     * @return string
     */
    public function toRawSQL(FilterElement $filter, array $bindings = []): string
    {
        $this->bindings = [];

        $value = $filter->getValue();
        if (!is_array($value)) $value = [$value];

        $conditions = [];
        foreach ($value as $v) {
            if (!isset($this->case_statements[$v])) continue;
            $conditions[] = '( ' . $this->case_statements[$v] . ' )';
        }

        if (empty($conditions)) return '';

        return implode(' OR ', $conditions);
    }
}
