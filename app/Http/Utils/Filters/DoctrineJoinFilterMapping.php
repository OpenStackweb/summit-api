<?php namespace utils;
/**
 * Copyright 2016 OpenStack Foundation
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
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
/**
 * Class DoctrineJoinFilterMapping
 * @package utils
 */
class DoctrineJoinFilterMapping extends FilterMapping
{
    /**
     * @var string
     */
    protected $alias;

    /**
     * DoctrineJoinFilterMapping constructor.
     * @param string $table
     * @param string $alias
     * @param string $where
     */
    public function __construct($table, $alias, $where)
    {
        parent::__construct($table, $where);
        $this->alias = $alias;
    }

    /**
     * @param FilterElement $filter
     * @throws \Exception
     */
    public function toRawSQL(FilterElement $filter)
    {
        throw new \Exception;
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

        if(strstr($where,":value")) {
            $where = str_replace(":value", ":value_" . $param_count, $where);
            $has_param = true;
        }

        if(strstr($where,":operator"))
            $where = str_replace(":operator", $filter->getOperator(), $where);

        if(!in_array($this->alias, $query->getAllAliases()))
            $query->innerJoin($this->table, $this->alias, Join::WITH);

        $query = $query->andWhere($where);

        if($has_param){
            $query = $query->setParameter(":value_".$param_count, $filter->getValue());
        }

        return $query;

    }

    /**
     * @param QueryBuilder $query
     * @param FilterElement $filter
     * @return string
     */
    public function applyOr(QueryBuilder $query, FilterElement $filter){
        $param_count = $query->getParameters()->count() + 1;
        $where       = $this->where;
        $has_param   = false;

        if(strstr($where,":value")) {
            $where = str_replace(":value", ":value_" . $param_count, $where);
            $has_param = true;
        }

        if(strstr($where,":operator"))
            $where = str_replace(":operator", $filter->getOperator(), $where);

        if(!in_array($this->alias, $query->getAllAliases()))
            $query->innerJoin($this->table, $this->alias, Join::WITH);

        if($has_param){
            $query->setParameter(":value_".$param_count, $filter->getValue());
        }
        return $where;
    }

}