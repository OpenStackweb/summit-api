<?php namespace App\Http\Utils\Filters\SQL;
/*
 * Copyright 2024 OpenStack Foundation
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
 * Class SQLInFilterMapping
 * @package App\Http\Utils\Filters\SQL
 */
class SQLInFilterMapping extends FilterMapping
{
    protected $main_operator;

    protected $operator;

    /**
     * DoctrineFilterMapping constructor.
     * @param string $alias
     */
    public function __construct(string $alias)
    {
        $this->main_operator = Filter::MainOperatorAnd;
        $this->operator = 'IN';
        parent::__construct($alias, '');
    }

    /**
     * @param FilterElement $filter
     * @param array $bindings
     * @return string
     */
    public function toRawSQL(FilterElement $filter, array $bindings = []):string
    {
        $value = $filter->getValue();
        if (!is_array($value)) {
            $value = [$value];
        }
        // construct named params one by one bc raw sql does not support array binding
        $named_params = [];
        $param_idx = count($bindings) + 1;
        foreach($value as $v){
            $named_params[] = ":".sprintf(Filter::ParamPrefix, $param_idx);
            $this->bindings[sprintf(Filter::ParamPrefix, $param_idx)] = $v;
            $param_idx++;
        }

        return sprintf("%s %s (%s)", $this->table, $this->operator, implode(',', $named_params));
    }
}