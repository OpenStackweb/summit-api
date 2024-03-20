<?php namespace App\Http\Utils\Filters;
/*
 * Copyright 2023 OpenStack Foundation
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

use Doctrine\ORM\QueryBuilder;
use utils\Filter;
use utils\FilterElement;
use utils\FilterMapping;

/**
 * Class DoctrineNotInFilterMapping
 * @package App\Http\Utils\Filters
 */
class DoctrineInFilterMapping  extends FilterMapping implements IQueryApplyable
{

    protected $main_operator;

    const Operator = 'IN';
    /**
     * DoctrineFilterMapping constructor.
     * @param string $condition
     */
    public function __construct($condition)
    {
        $this->main_operator = Filter::MainOperatorAnd;
        $this->operator = 'IN';
        parent::__construct("", $condition);
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

    private function buildWhere(QueryBuilder $query, FilterElement $filter):string{
        $value = $filter->getValue();
        if (!is_array($value)) {
            $value = [$value];
        }
        $param_count = $query->getParameters()->count() + 1;
        $query->setParameter(":value_" . $param_count, $value);
        return sprintf("%s %s ( :value_%s )", $this->where, static::Operator, $param_count);
    }

    /**
     * @param QueryBuilder $query
     * @param FilterElement $filter
     * @return QueryBuilder
     */
    public function apply(QueryBuilder $query, FilterElement $filter): QueryBuilder
    {
        if($this->main_operator === Filter::MainOperatorAnd)
            return $query->andWhere($this->buildWhere($query, $filter));
        else
            return $query->orWhere($this->buildWhere($query, $filter));
    }

    /**
     * @param QueryBuilder $query
     * @param FilterElement $filter
     * @return string
     */
    public function applyOr(QueryBuilder $query, FilterElement $filter): string
    {
        return $this->buildWhere($query, $filter);
    }

    public function setMainOperator(string $op): void
    {
        $this->main_operator = $op;
    }
}