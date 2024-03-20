<?php namespace utils;
/**
 * Copyright 2017 OpenStack Foundation
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

use App\Http\Utils\Filters\IQueryApplyable;
use Doctrine\ORM\QueryBuilder;

/**
 * Class DoctrineSwitchFilterMapping
 * @package utils
 */
class DoctrineSwitchFilterMapping extends FilterMapping implements IQueryApplyable
{
    /**
     * @var string
     */
    protected $main_operator;

    /**
     * @var DoctrineCaseFilterMapping[]
     */
    private $case_statements;

    public function __construct($case_statements = [])
    {
        parent::__construct("", "");
        $this->case_statements = $case_statements;
        $this->main_operator = Filter::MainOperatorAnd;
    }

    /**
     * @param FilterElement $filter
     * @param array $bindings
     * @return string
     */
    public function toRawSQL(FilterElement $filter, array $bindings = []):string
    {
        throw new \Exception;
    }


    /**
     * @param QueryBuilder $query
     * @param FilterElement $filter
     * @return QueryBuilder
     */
    public function apply(QueryBuilder $query, FilterElement $filter): QueryBuilder
    {
        $value = $filter->getValue();
        if(!is_array($value)) $value = [$value];
        $condition = '';
        foreach ($value as $v) {
            if (!isset($this->case_statements[$v])) continue;
            $case_statement = $this->case_statements[$v];
            if(!empty($condition)) $condition .= ' OR ';
            $condition .= ' ( '.$case_statement->getCondition().' ) ';
        }
        if(!empty($condition))
            $condition = ' ( '.$condition.' ) ';
        if($this->main_operator === Filter::MainOperatorAnd)
            return $query->andWhere($condition);
        else
            return $query->orWhere($condition);
    }

    /**
     * @param QueryBuilder $query
     * @param FilterElement $filter
     * @return string
     */
    public function applyOr(QueryBuilder $query, FilterElement $filter): string
    {
        $value = $filter->getValue();
        if(!is_array($value)) $value = [$value];
        $condition = '';
        foreach ($value as $v) {
            if (!isset($this->case_statements[$filter->getValue()])) continue;
            $case_statement = $this->case_statements[$v];
            if(!empty($condition)) $condition .= ' OR ';
            $condition .= ' ( '.$case_statement->getCondition().' ) ';
        }
        return $condition;
    }

    public function setMainOperator(string $op): void
    {
        $this->main_operator = $op;
    }
}