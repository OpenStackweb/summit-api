<?php namespace utils;
/**
 * Copyright 2019 OpenStack Foundation
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

use Illuminate\Support\Facades\Log;
use Doctrine\ORM\QueryBuilder;

/**
 * Class DoctrineHavingFilterMapping
 * @package utils
 */
class DoctrineHavingFilterMapping extends DoctrineFilterMapping
{
    /**
     * @var string
     */
    protected $groupBy;

    /**
     * @var string
     */
    protected $having;

    /**
     * DoctrineFilterMapping constructor.
     * @param string $condition
     */
    public function __construct(string $condition, string $groupBy, string $having)
    {
        parent::__construct($condition);
        $this->groupBy = $groupBy;
        $this->having = $having;
    }

    /**
     * @param QueryBuilder $query
     * @param FilterElement $filter
     * @return QueryBuilder
     */
    public function apply(QueryBuilder $query, FilterElement $filter): QueryBuilder
    {


        $param_count = $query->getParameters()->count();
        $where = $this->where;
        $has_param = false;
        $value = $filter->getValue();


        Log::debug
        (
            sprintf
            (
                "DoctrineHavingFilterMapping::apply where %s groupBy %s having %s value %s",
                $this->where,
                $this->groupBy,
                $this->having,
                $value
            )
        );

        if (!is_numeric($value) && is_string($value) && ( trim($value) == '' || empty($value)))
        {
            Log::debug("DoctrineHavingFilterMapping::apply value is empty");
            return $query;
        }

        if (is_array($value) && count($value) == 0) {
            Log::debug("DoctrineHavingFilterMapping::apply value is empty");
            return $query;
        }

        if (!empty($where)) {
            if (strstr($where, ":value")) {
                ++$param_count;
                $where = str_replace(":value", ":value_" . $param_count, $where);
                $has_param = true;
            }

            if (strstr($where, ":operator"))
                $where = str_replace(":operator", $filter->getOperator(), $where);

            if($this->main_operator === Filter::MainOperatorAnd)
                $query = $query->andWhere($where);
            else
                $query = $query->orWhere($where);

            if ($has_param) {
                $query = $query->setParameter(":value_" . $param_count, $value);
            }
        }

        if (!empty($this->groupBy)) {
            $query = $query->addGroupBy($this->groupBy);
        }

        if (!empty($this->having)) {
            $has_param = false;
            if (strstr($this->having, ":value_count") && is_array($value)) {
                $this->having = str_replace(":value_count", count($value), $this->having);
            }

            if (strstr($this->having, ":value_count")) {
                $this->having = str_replace(":value_count", 1, $this->having);
            }

            if (strstr($this->having, ":value")) {
                ++$param_count;
                $this->having = str_replace(":value", ":value_" . $param_count, $this->having);
                $has_param = true;
            }

            if (strstr($this->having, ":operator"))
                $this->having = str_replace(":operator", $filter->getOperator(), $this->having);

            if ($has_param) {
                $query = $query->setParameter(":value_" . $param_count, $value);
            }

            if($this->main_operator === Filter::MainOperatorAnd)
                $query = $query->andHaving($this->having);
            else
                $query = $query->orHaving($this->having);
        }

        return $query;
    }
}