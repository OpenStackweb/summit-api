<?php namespace App\Http\Utils\Filters;
/*
 * Copyright 2022 OpenStack Foundation
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
use utils\FilterElement;

/**
 * Interface IQueryApplyable
 * @package App\Http\Utils\Filters
 */
interface IQueryApplyable {
  public function setMainOperator(string $op): void;
  /**
   * @param QueryBuilder $query
   * @param FilterElement $filter
   * @return QueryBuilder
   */
  public function apply(QueryBuilder $query, FilterElement $filter): QueryBuilder;

  /**
   * @param QueryBuilder $query
   * @param FilterElement $filter
   * @return string
   */
  public function applyOr(QueryBuilder $query, FilterElement $filter): string;
}
