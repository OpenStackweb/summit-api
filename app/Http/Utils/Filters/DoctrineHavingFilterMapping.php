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
        $this->having  = $having;
    }

    /**
     * @param QueryBuilder $query
     * @param FilterElement $filter
     * @return QueryBuilder
     */
    public function apply(QueryBuilder $query, FilterElement $filter){
        $param_count = $query->getParameters()->count() + 1;
        $where       = $this->where;
        $has_param   = false;
        $value       = $filter->getValue();

        if(strstr($where,":value")) {
            $where = str_replace(":value", ":value_" . $param_count, $where);
            $has_param = true;
        }

        if(strstr($where,":operator"))
            $where = str_replace(":operator", $filter->getOperator(), $where);

        $query = $query->andWhere($where);

        if($has_param){
            $query = $query->setParameter(":value_".$param_count, $value);
        }

        $query = $query->addGroupBy($this->groupBy);

        if(strstr($this->having,":value_count") && is_array($value)){
            $this->having = str_replace(":value_count", count($value), $this->having);
        }

        $query = $query->andHaving($this->having);

        return $query;
    }
}