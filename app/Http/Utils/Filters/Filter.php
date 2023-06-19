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

use App\Http\Utils\Filters\IQueryApplyable;
use App\libs\Utils\PunnyCodeHelper;
use Doctrine\ORM\QueryBuilder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use models\exceptions\ValidationException;

/**
 * Class Filter
 * @package utils
 */
final class Filter
{
    const Int = 'json_int';
    const String = 'json_string';
    const DateTimeEpoch = 'datetime_epoch';
    const Email = 'json_email';
    const ParamPrefix = "param_%s";
    const ValuePlaceholder = ':value';
    const OperatorPlaceholder = ':operator';

    const Boolean = 'json_boolean';

    /**
     * @param string $mapping
     * @return string
     */
    private static function cleanMapping(string $mapping):string {
        if (strstr($mapping, self::ValuePlaceholder)) {
            $mapping = str_replace(self::ValuePlaceholder, "", $mapping);
        }
        if (strstr($mapping, self::OperatorPlaceholder)) {
            $mapping = str_replace(self::OperatorPlaceholder, "", $mapping);
        }
        return trim($mapping);
    }
    /**
     * @var array
     */
    private $filters = [];

    /**
     * @var array
     */
    private $bindings = [];

    /**
     * @var mixed
     */
    private $originalExp;

    public function __construct(array $filters = [], $originalExp = null)
    {
        $this->filters = $filters;
        $this->originalExp = $originalExp;
    }

    /**
     * @param FilterElement|array $filter
     * @return $this
     */
    public function addFilterCondition($filter)
    {
        $this->filters[] = $filter;
        return $this;
    }

    /**
     * will return an array of filter elements, OR filters are returned on a sub array
     * @param string $field
     * @return null|FilterElement[]
     */
    public function getFilter($field)
    {
        $res = [];
        foreach ($this->filters as $filter) {

            if ($filter instanceof FilterElement && $filter->getField() === $field) {
                $res[] = $filter;
            } else if (is_array($filter)) {
                // OR
                $or_res = [];
                foreach ($filter as $e) {
                    if ($e instanceof FilterElement && $e->getField() === $field) {
                        $or_res[] = $e;
                    }
                }
                foreach ($or_res as $e) {
                    $res[] = $e;
                }
            }
        }
        return $res;
    }

    /**
     * @param string $field
     * @return null|FilterElement
     */
    public function getUniqueFilter($field)
    {
        $res = $this->getFilter($field);
        return count($res) == 1 ? $res[0] : null;
    }

    /**
     * @param string $field
     * @return bool
     */
    public function hasFilter($field)
    {
        return count($this->getFilter($field)) > 0;
    }

    /**
     * @param string $field
     * @return null|FilterElement[]
     */
    public function getFlatFilter($field)
    {
        $res = [];
        foreach ($this->filters as $filter) {

            if ($filter instanceof FilterElement && $filter->getField() === $field) {
                $res[] = $filter;
            } else if (is_array($filter)) {
                // OR
                foreach ($filter as $e) {
                    if ($e instanceof FilterElement && $e->getField() === $field) {
                        $res[] = $e;
                    }
                }

            }
        }
        return $res;
    }

    /**
     * @return array
     */
    public function getFiltersKeyValues()
    {
        $res = [];
        foreach ($this->filters as $filter) {

            if ($filter instanceof FilterElement) {
                $res[$filter->getField()] = $filter->getValue();
            } else if (is_array($filter)) {
                // OR
                foreach ($filter as $e) {
                    if ($e instanceof FilterElement) {
                        if (!isset($res[$e->getField()])) $res[$e->getField()] = [];
                        $res[$e->getField()][] = $e->getValue();
                    }
                }
            }
        }
        return $res;
    }

    /**
     * @param array $rules
     * @param array $messages
     * @throws ValidationException
     */
    public function validate(array $rules, array $messages = [])
    {
        $filter_key_values = $this->getFiltersKeyValues();
        foreach ($rules as $field => $rule) {
            if (!isset($filter_key_values[$field])) continue;
            $values = $filter_key_values[$field];
            if (!is_array($values)) $values = [$values];
            foreach ($values as $val) {
                if (is_array($val)) {
                    foreach ($val as $sub_val) {
                        self::_validate($field, $sub_val, $rule, $messages);
                    }
                } else {
                    self::_validate($field, $val, $rule, $messages);
                }
            }
        }
    }

    private static function _validate($field, $val, $rule, $messages)
    {
        $validation = Validator::make
        (
            [$field => $val],
            [$field => $rule],
            $messages
        );
        if ($validation->fails()) {
            $ex = new ValidationException();
            throw $ex->setMessages($validation->messages()->toArray());
        }
    }

    /**
     * @param FilterElement $filter
     * @param string $mapping
     * @param int $param_idx
     * @return string
     */
    private function applyCondition(FilterElement $filter, string $mapping, int &$param_idx): string
    {
        $mapping_parts = explode(':', $mapping);
        $value = $filter->getValue();
        $op = $filter->getOperator();
        $sameOp = $filter->getSameFieldOp();

        if (count($mapping_parts) > 1) {
            $filter->setValue($this->convertValue($filter->getRawValue(), $mapping_parts[1]));
            $value = $filter->getValue();
        }

        if (is_array($value)) {
            $inner_condition = '( ';
            foreach ($value as $val) {
                $inner_condition .= sprintf("%s %s :%s %s ", self::cleanMapping($mapping_parts[0]), $op, sprintf(self::ParamPrefix, $param_idx), $sameOp);
                $this->bindings[sprintf(self::ParamPrefix, $param_idx)] = $val;
                ++$param_idx;
            }
            $inner_condition = substr($inner_condition, 0, (strlen($sameOp) + 1) * -1);
            $inner_condition .= ' )';
        } else {
            $inner_condition = sprintf("%s %s :%s ", self::cleanMapping($mapping_parts[0]), $op, sprintf(self::ParamPrefix, $param_idx));
            $this->bindings[sprintf(self::ParamPrefix, $param_idx)] = $value;
            ++$param_idx;
        }

        return $inner_condition;
    }

    /**
     * @param array $mappings
     * @return string
     */
    public function toRawSQL(array $mappings, int $param_idx = 1)
    {
        $sql = '';
        $this->bindings = [];

        foreach ($this->filters as $filter) {
            if ($filter instanceof FilterElement && isset($mappings[$filter->getField()])) {
                $condition = '';
                $mapping = $mappings[$filter->getField()];
                if (is_array($mapping)) {
                    foreach ($mapping as $mapping_or) {
                        if (!empty($condition)) $condition .= ' OR ';
                        $condition .= $this->applyCondition($filter, $mapping_or, $param_idx);
                    }
                } else {
                    $condition = $this->applyCondition($filter, $mapping, $param_idx);
                }

                if (!empty($sql) && !empty($condition)) $sql .= ' AND ';
                $sql .= $condition;
            } else if (is_array($filter)) {
                // an array is a OR
                $condition = '';
                foreach ($filter as $e) {
                    if ($e instanceof FilterElement && isset($mappings[$e->getField()])) {
                        $mapping = $mappings[$e->getField()];

                        if (is_array($mapping)) {
                            foreach ($mapping as $mapping_or) {

                                if (!empty($condition)) $condition .= ' OR ';
                                $condition .= $this->applyCondition($e, $mapping_or, $param_idx);
                            }
                        } else {
                            if (!empty($condition)) $condition .= ' OR ';
                            $condition .= $this->applyCondition($e, $mapping, $param_idx);;
                        }
                    }
                }

                if (!empty($sql)) $sql .= ' AND ';
                $sql .= '( ' . $condition . ' )';
            }
        }
        return $sql;
    }

    /**
     * @param QueryBuilder $query
     * @param array $mappings
     * @return $this
     */
    public function apply2Query(QueryBuilder $query, array $mappings)
    {

        $param_idx = 1;
        $this->bindings = [];

        foreach ($this->filters as $filter) {
            if ($filter instanceof FilterElement && isset($mappings[$filter->getField()])) {
                // single filter element

                $mapping = $mappings[$filter->getField()];
                if ($mapping instanceof IQueryApplyable) {
                    $query = $mapping->apply($query, $filter);
                } else if (is_array($mapping)) {
                    $condition = '';
                    // OR Criteria
                    foreach ($mapping as $mapping_or) {
                        if (!empty($condition)) $condition .= ' OR ';
                        $condition .= $this->applyCondition($filter, $mapping_or, $param_idx);
                    }

                    $query->andWhere($condition);
                } else {
                    $condition = $this->applyCondition($filter, $mapping, $param_idx);
                    $query->andWhere($condition);
                }
            } else if (is_array($filter)) {
                // OR
                $sub_or_query = '';
                foreach ($filter as $e) {
                    if ($e instanceof FilterElement && isset($mappings[$e->getField()])) {

                        $mapping = $mappings[$e->getField()];
                        if ($mapping instanceof IQueryApplyable) {
                            $condition = $mapping->applyOr($query, $e);
                            if (!empty($sub_or_query)) $sub_or_query .= ' OR ';
                            $sub_or_query .= $condition;
                        } else if (is_array($mapping)) {
                            $condition = '';
                            foreach ($mapping as $mapping_or) {
                                if (!empty($condition)) $condition .= ' OR ';
                                $condition .= $this->applyCondition($e, $mapping_or, $param_idx);
                            }

                            if (!empty($sub_or_query)) $sub_or_query .= ' OR ';
                            $sub_or_query .= ' ( ' . $condition . ' ) ';
                        } else {

                            if (!empty($sub_or_query)) $sub_or_query .= ' OR ';
                            $sub_or_query .= $this->applyCondition($e, $mapping, $param_idx);
                        }
                    }
                }
                $query->andWhere($sub_or_query);
            }
        }

        foreach ($this->bindings as $param => $value)
            $query->setParameter($param, $value);

        return $this;
    }

    /**
     * @param $value
     * @param string|null $strTimeZone
     * @return array|string
     * @throws \Exception
     */
    public static function convertToDateTime($value, ?string $strTimeZone = null)
    {
        $timezone = null;

        if (!empty($strTimeZone)) {
            $timezone = new \DateTimeZone($strTimeZone);
        }

        if (is_array($value)) {
            $res = [];
            foreach ($value as $val) {
                $datetime = new \DateTime("@$val", $timezone);
                if (!is_null($timezone))
                    $datetime = $datetime->setTimezone($timezone);
                $res[] = $datetime->format("Y-m-d H:i:s");
            }
            return $res;
        }
        // single value
        $datetime = new \DateTime("@$value");
        Log::debug(sprintf("Filter::convertToDateTime original date value %s", $datetime->format("Y-m-d H:i:s")));
        if (!is_null($timezone))
            $datetime = $datetime->setTimezone($timezone);
        Log::debug(sprintf("Filter::convertToDateTime final date %s", $datetime->format("Y-m-d H:i:s")));
        return $datetime->format("Y-m-d H:i:s");
    }

    /**
     * @param $value
     * @param string $original_format
     * @return array|int|mixed|string
     * @throws \Exception
     */
    private function convertValue($value, string $original_format)
    {
        $original_format_parts = explode('|', $original_format);
        switch ($original_format_parts[0]) {
            case self::Email:
                if (is_array($value)) {
                    $res = [];
                    foreach ($value as $val) {
                        $res[] = sprintf("%s", PunnyCodeHelper::encodeEmail($val));
                    }
                    return $res;
                }
                return sprintf("%s", PunnyCodeHelper::encodeEmail($value));
            case self::DateTimeEpoch:
                Log::debug(sprintf("Filter::convertValue datetime_epoch %s", $original_format));
                $strTimeZone = count($original_format_parts) > 1 ? $original_format_parts[1] : null;
                return self::convertToDateTime($value, $strTimeZone);
                break;
            case self::Boolean:
                return to_boolean($value) ? 1 : 0;
                break;
            case self::Int:
                if (is_array($value)) {
                    $res = [];
                    foreach ($value as $val) {
                        $res[] = intval($val);
                    }
                    return $res;
                }
                return intval($value);
                break;
            case self::String:
                if (is_array($value)) {
                    $res = [];
                    foreach ($value as $val) {
                        $res[] = sprintf("%s", $val);
                    }
                    return $res;
                }
                return sprintf("%s", $value);
                break;
            default:
                return $value;
                break;
        }
    }

    /**
     * @return array
     */
    public function getSQLBindings()
    {
        return $this->bindings;
    }

    /**
     * @param string $field
     * @return array
     */
    public function getFilterCollectionByField($field)
    {
        $list = [];
        $filter = $this->getFilter($field);

        if (is_array($filter)) {
            if (is_array($filter[0])) {
                foreach ($filter[0] as $filter_element)
                    $list[] = intval($filter_element->getValue());
            } else {
                $list[] = intval($filter[0]->getValue());
            }
        }
        return $list;
    }

    public function __toString(): string
    {
        $res = "";
        foreach ($this->filters as $filter) {

            if ($filter instanceof FilterElement) {
                $res .= '(' . $filter . ')';
            } else if (is_array($filter)) {
                // OR
                $or_res = [];
                foreach ($filter as $e) {
                    if ($e instanceof FilterElement) {
                        $or_res[] = '(' . $e . ')';
                    }
                }
                if (count($or_res)) $res = '(' . implode("|", $or_res) . ')';
            }
        }
        return $res;
    }

    /**
     * @param string $field
     * @param string $type
     * @return string
     */
    private static function buildField(string $field, string $type): string
    {
        return sprintf("%s:%s", $field, $type);
    }

    /**
     * @param string $field
     * @return string
     */
    public static function buildStringField(string $field): string
    {
        return self::buildField($field, self::String);
    }

    /**
     * @param string $field
     * @return string
     */
    public static function buildIntField(string $field): string
    {
        return self::buildField($field, self::Int);
    }

    /**
     * @param string $field
     * @return string
     */
    public static function buildBooleanField(string $field): string
    {
        return self::buildField($field, self::Boolean);
    }

    /**
     * @param string $field
     * @return string
     */
    public static function buildDateTimeEpochField(string $field): string
    {
        return self::buildField($field, self::DateTimeEpoch);
    }

    /**
     * @param string $field
     * @return string
     */
    public static function buildEmailField(string $field): string
    {
        return self::buildField($field, self::Email);
    }

    public static function buildLowerCaseStringField(string $field): string
    {
        return sprintf("LOWER(%s)",$field);
    }

    public static function buildConcatStringFields(array $fields): string
    {
        $res = [];
        foreach ($fields as $field) {
            $res[] = sprintf("LOWER(%s)", $field);
        }
        return sprintf("CONCAT(%s)", implode(",' ',",$res));
    }
    /**
     * @return mixed|null
     */
    public function getOriginalExp()
    {
        return $this->originalExp;
    }
}