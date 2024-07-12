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
 * Class SQLInstanceOfFilterMapping
 * @package App\Http\Utils\Filters\SQL
 */
final class SQLInstanceOfFilterMapping extends FilterMapping {
  private $class_names = [];

  /**
   * @param string $alias
   * @param array $class_names
   */
  public function __construct(string $alias, array $class_names = []) {
    $this->class_names = $class_names;
    parent::__construct($alias, sprintf("%s.ClassName = ':class_name'", $alias));
  }

  private function translateClassName(string $value): string {
    if (isset($this->class_names[$value])) {
      $parts = explode("\\", $this->class_names[$value]);
      return $parts[count($parts) - 1];
    }
    return $value;
  }

  /**
   * @param FilterElement $filter
   * @param array $bindings
   * @return string
   */
  public function toRawSQL(FilterElement $filter, array $bindings = []): string {
    $value = $filter->getValue();

    if (is_array($value)) {
      $where_components = [];
      foreach ($value as $val) {
        $where_components[] = str_replace(
          ":class_name",
          $this->translateClassName($val),
          $this->where,
        );
      }
      return implode(sprintf(" %s ", $filter->getSameFieldOp()), $where_components);
    }

    return str_replace(":class_name", $this->translateClassName($filter->getValue()), $this->where);
  }
}
