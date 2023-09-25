<?php namespace utils;
/**
 * Copyright 2015 OpenStack Foundation
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

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Query\Expr\Select;
use Doctrine\ORM\QueryBuilder;

/**
 * Class Order
 * @package utils
 */
final class Order
{
    /**
     * @var array
     */
    private $ordering;

    public function __construct($ordering = [])
    {
        $this->ordering = $ordering;
    }

    /**
     * @param string $field
     * @return bool
     */
    public function hasOrder(string $field):bool{
        foreach ($this->ordering as $order){
            if ($order instanceof OrderElement && $order->getField() == $field) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param string $field
     * @return bool
     */
    public function removeOrder(string $field):bool{
        foreach ($this->ordering as $index => $order){
            if ($order instanceof OrderElement && $order->getField() == $field) {
                unset($this->ordering[$index]);
                return true;
            }
        }
        return false;
    }

    /**
     * @param QueryBuilder $query
     * @param array $mappings
     * @return $this
     */
    public function apply2Query(QueryBuilder $query, array $mappings)
    {
        $hidden_ord_idx = 0;
        foreach ($this->ordering as $order) {
            if ($order instanceof OrderElement) {
                if (isset($mappings[$order->getField()])) {
                    $mapping = $mappings[$order->getField()];
                    $orders[$mapping] = $order->getDirection();
                    // @see https://www.doctrine-project.org/projects/doctrine-orm/en/2.6/reference/dql-doctrine-query-language.html#select-expressions
                    if(str_contains(strtoupper($mapping),"CASE WHEN") || str_contains(strtoupper($mapping),"COALESCE")){
                        $query->addSelect("({$mapping}) AS HIDDEN ORD_{$hidden_ord_idx}");
                        $mapping = "ORD_{$hidden_ord_idx}";
                        ++$hidden_ord_idx;
                    }
                    if(str_contains(strtoupper($mapping),"COUNT(")){
                        $selects = $query->getDQLPart("select");
                        $query->addSelect("({$mapping}) AS HIDDEN ORD_{$hidden_ord_idx}");
                        $mapping = "ORD_{$hidden_ord_idx}";
                        // add original selects to grouping
                        foreach($selects as $s)
                            foreach($s->getParts() as $p)
                                $query->addGroupBy($p);
                        ++$hidden_ord_idx;
                    }
                    $query->addOrderBy($mapping, $order->getDirection());
                }
            }
        }
        return $this;
    }

    /**
     * @param Criteria $criteria
     * @param array $mappings
     * @return $this
     */
    public function apply2Criteria(Criteria $criteria, array $mappings)
    {
        $orders = [];
        foreach ($this->ordering as $order) {
            if ($order instanceof OrderElement) {
                if (isset($mappings[$order->getField()])) {
                    $mapping = $mappings[$order->getField()];
                    $orders[$mapping] = $order->getDirection();
                }
            }
        }
        if(count($orders) > 0)
            $criteria->orderBy($orders);
        return $this;
    }


    /**
     * @param array $mappings
     * @return string
     */
    public function toRawSQL(array $mappings)
    {
        $sql = ' ORDER BY ';
        foreach ($this->ordering as $order) {
            if ($order instanceof OrderElement) {
                if (isset($mappings[$order->getField()])) {
                    $mapping = $mappings[$order->getField()];
                    $sql .= sprintf('%s %s, ', $mapping, $order->getDirection());
                }
            }
        }
        return substr($sql, 0 , strlen($sql) - 2);
    }
}