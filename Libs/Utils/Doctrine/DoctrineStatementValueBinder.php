<?php namespace Libs\Utils\Doctrine;
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
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Statement;
/**
 * Class DoctrineStatementValueBinder
 * @package Libs\Utils\Doctrine
 */
final class DoctrineStatementValueBinder {
  /**
   * @param $param
   * @return int
   */
  public static function inferParamType($param): int {
    if (is_int($param)) {
      return ParameterType::INTEGER;
    }
    if (is_bool($param)) {
      return ParameterType::BOOLEAN;
    }
    if (is_string($param)) {
      return ParameterType::STRING;
    }
    if (is_array($param)) {
      return ParameterType::INTEGER;
    }
    return ParameterType::STRING;
  }

  /**
   * @param Statement $stmt
   * @param array $params
   * @return Statement
   * @throws \Doctrine\DBAL\Exception
   */
  public static function bind(Statement $stmt, array $params): Statement {
    foreach ($params as $key => $value) {
      $stmt->bindValue($key, $value, self::inferParamType($value));
    }
    return $stmt;
  }
}
