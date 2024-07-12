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

use App\Http\Utils\Filters\IQueryApplyable;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;

/**
 * Class DoctrineJoinFilterMapping
 * @package utils
 */
class DoctrineJoinFilterMapping extends FilterMapping implements IQueryApplyable {
  /**
   * @var string
   */
  protected $main_operator;

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
  public function __construct($table, $alias, $where) {
    parent::__construct($table, $where);
    $this->main_operator = Filter::MainOperatorAnd;
    $this->alias = $alias;
  }

  /**
   * @param FilterElement $filter
   * @param array $bindings
   * @return string
   */
  public function toRawSQL(FilterElement $filter, array $bindings = []): string {
    throw new \Exception();
  }

  /**
   * @param QueryBuilder $query
   * @param FilterElement $filter
   * @return QueryBuilder
   */
  public function apply(QueryBuilder $query, FilterElement $filter): QueryBuilder {
    $value = $filter->getValue();

    if (is_array($value)) {
      $inner_where = "( ";

      foreach ($value as $val) {
        $param_count = $query->getParameters()->count() + 1;
        $where = $this->where;
        $has_param = false;

        if (strstr($where, ":value")) {
          $where = str_replace(":value", ":value_" . $param_count, $where);
          $has_param = true;
        }

        if (strstr($where, ":operator")) {
          $where = str_replace(":operator", $filter->getOperator(), $where);
        }

        if ($has_param) {
          $query = $query->setParameter(":value_" . $param_count, $val);
        }
        $inner_where .= $where . " " . $filter->getSameFieldOp() . " ";
      }
      $inner_where = substr($inner_where, 0, (strlen($filter->getSameFieldOp()) + 1) * -1);
      $inner_where .= " )";

      if (!in_array($this->alias, $query->getAllAliases())) {
        $query->innerJoin($this->table, $this->alias, Join::WITH);
      }
      if ($this->main_operator === Filter::MainOperatorAnd) {
        $query = $query->andWhere($inner_where);
      } else {
        $query = $query->orWhere($inner_where);
      }
    } else {
      $param_count = $query->getParameters()->count() + 1;
      $where = $this->where;
      $has_param = false;

      if (strstr($where, ":value")) {
        $where = str_replace(":value", ":value_" . $param_count, $where);
        $has_param = true;
      }

      if (strstr($where, ":operator")) {
        $where = str_replace(":operator", $filter->getOperator(), $where);
      }

      if (!in_array($this->alias, $query->getAllAliases())) {
        $query->innerJoin($this->table, $this->alias, Join::WITH);
      }

      if ($this->main_operator === Filter::MainOperatorAnd) {
        $query = $query->andWhere($where);
      } else {
        $query = $query->orWhere($where);
      }

      if ($has_param) {
        $query = $query->setParameter(":value_" . $param_count, $filter->getValue());
      }
    }

    return $query;
  }

  /**
   * @param QueryBuilder $query
   * @param FilterElement $filter
   * @return string
   */
  public function applyOr(QueryBuilder $query, FilterElement $filter): string {
    $value = $filter->getValue();
    if (is_array($value)) {
      $inner_where = "( ";

      foreach ($value as $val) {
        $param_count = $query->getParameters()->count() + 1;
        $where = $this->where;
        $has_param = false;

        if (strstr($where, ":value")) {
          $where = str_replace(":value", ":value_" . $param_count, $where);
          $has_param = true;
        }

        if (strstr($where, ":operator")) {
          $where = str_replace(":operator", $filter->getOperator(), $where);
        }

        if ($has_param) {
          $query->setParameter(":value_" . $param_count, $value);
        }

        $inner_where .= $where . " " . $filter->getSameFieldOp() . " ";
      }

      $inner_where = substr($inner_where, 0, (strlen($filter->getSameFieldOp()) + 1) * -1);
      $inner_where .= " )";

      if (!in_array($this->alias, $query->getAllAliases())) {
        $query->innerJoin($this->table, $this->alias, Join::WITH);
      }

      return $inner_where;
    }

    $param_count = $query->getParameters()->count() + 1;
    $where = $this->where;
    $has_param = false;

    if (strstr($where, ":value")) {
      $where = str_replace(":value", ":value_" . $param_count, $where);
      $has_param = true;
    }

    if (strstr($where, ":operator")) {
      $where = str_replace(":operator", $filter->getOperator(), $where);
    }

    if (!in_array($this->alias, $query->getAllAliases())) {
      $query->innerJoin($this->table, $this->alias, Join::WITH);
    }

    if ($has_param) {
      $query->setParameter(":value_" . $param_count, $value);
    }

    return $where;
  }

  public function setMainOperator(string $op): void {
    $this->main_operator = $op;
  }
}
