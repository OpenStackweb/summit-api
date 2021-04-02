<?php namespace utils;
/**
 * Copyright 2021 OpenStack Foundation
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
 * Class DoctrineCollectionFieldsFilterMapping
 * @package App\Http\Utils\Filters
 */
class DoctrineCollectionFieldsFilterMapping extends DoctrineJoinFilterMapping
{
    private $allowed_collection_fields = [];

    private $joins = [];

    /**
     * DoctrineCollectionFieldsFilterMapping constructor.
     * @param string $table
     * @param string $alias
     * @param array $joins
     * @param array $allowed_collection_fields
     */
    public function __construct
    (
        string $table,
        string $alias,
        array $joins = [],
        array $allowed_collection_fields = []
    )
    {
        $this->allowed_collection_fields = $allowed_collection_fields;
        $this->joins = $joins;
        parent::__construct($table, $alias, "");
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
     * @param string $exp
     * @return FilterElement|null
     * @throws FilterParserException
     */
    private function parseFilter(string $exp):?FilterElement
    {
        list ($field, $op, $value) = FilterParser::filterExpresion($exp);
        if (!key_exists($field, $this->allowed_collection_fields))
            throw new FilterParserException(sprintf("Field %s is not allowed as filter", $field));

        return FilterParser::buildFilter($this->allowed_collection_fields[$field], $op, $value);
    }

    /**
     * @param QueryBuilder $query
     * @param FilterElement $filter
     * @return QueryBuilder
     * @throws \models\exceptions\ValidationException
     */
    public function apply(QueryBuilder $query, FilterElement $filter)
    {
        $value = $filter->getValue();



        if (is_array($value)) {

            $inner_where = '';

            foreach ($value as $val) {

                $filterElement = $this->parseFilter($val);
                $param_count = $query->getParameters()->count() + 1;
                if (!empty($inner_where))
                    $inner_where .= sprintf(" %s ", $filter->getSameFieldOp());
                $inner_where .= sprintf("%s %s %s", $filterElement->getField(), $filterElement->getOperator(), ":value_" . $param_count);
                $query->setParameter(":value_" . $param_count, $filterElement->getValue());
            }

            if (!in_array($this->alias, $query->getAllAliases()))
                $query->innerJoin($this->table, $this->alias, Join::WITH);

            foreach ($this->joins as $join => $join_alias){
                $query->innerJoin(sprintf("%s.%s", $this->alias, $join), $join_alias, Join::WITH);
            }

            $inner_where = sprintf("( %s )", $inner_where);

            return $query->andWhere($inner_where);

        }

        $param_count = $query->getParameters()->count() + 1;
        $filterElement = $this->parseFilter($value);
        $where = sprintf("%s %s %s", $filterElement->getField(), $filterElement->getOperator(), ":value_" . $param_count);
        $query->setParameter(":value_" . $param_count, $filterElement->getValue());
        if (!in_array($this->alias, $query->getAllAliases()))
            $query->innerJoin($this->table, $this->alias, Join::WITH);

        foreach ($this->joins as $join => $join_alias){
            $query->innerJoin(sprintf("%s.%s", $this->alias, $join), $join_alias, Join::WITH);
        }

        return $query->andWhere($where);
    }

    /**
     * @param QueryBuilder $query
     * @param FilterElement $filter
     * @return string
     */
    public function applyOr(QueryBuilder $query, FilterElement $filter)
    {
        $value = $filter->getValue();

        if (is_array($value)) {
            $inner_where = '';

            foreach ($value as $val) {
                $filterElement = $this->parseFilter($val);
                $param_count = $query->getParameters()->count() + 1;
                if (!empty($inner_where))
                    $inner_where .= sprintf(" %s ", $filter->getSameFieldOp());
                $inner_where .= sprintf("%s %s %s", $filterElement->getField(), $filterElement->getOperator(), ":value_" . $param_count);
                $query->setParameter(":value_" . $param_count, $filterElement->getValue());
            }

            $inner_where = sprintf("( %s )", $inner_where);

            if (!in_array($this->alias, $query->getAllAliases()))
                $query->innerJoin($this->table, $this->alias, Join::WITH);

            foreach ($this->joins as $join => $join_alias){
                $query->innerJoin(sprintf("%s.%s", $this->alias, $join), $join_alias, Join::WITH);
            }

            return $inner_where;
        }

        $param_count = $query->getParameters()->count() + 1;
        $filterElement = $this->parseFilter($value);
        $where = sprintf("%s %s %s", $filterElement->getField(), $filterElement->getOperator(), ":value_" . $param_count);
        $query->setParameter(":value_" . $param_count, $filterElement->getValue());
        if (!in_array($this->alias, $query->getAllAliases()))
            $query->innerJoin($this->table, $this->alias, Join::WITH);

        foreach ($this->joins as $join => $join_alias){
            $query->innerJoin(sprintf("%s.%s", $this->alias, $join), $join_alias, Join::WITH);
        }

        return $where;
    }

}