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
use Illuminate\Support\Facades\Log;
/**
 * Class FilterParser
 * @package utils
 */
final class FilterParser
{
    /**
     * @param $filters
     * @param array $allowed_fields
     * @return Filter
     * @throws FilterParserException
     */
    public static function parse($filters, $allowed_fields = [])
    {
        Log::debug(sprintf("FilterParser::parse allowed_fields %s", json_encode($allowed_fields)));

        $res                 = [];
        $matches             = [];
        $filter_join_conditions = [];

        if (!is_array($filters))
            $filters = array($filters);

        foreach ($filters as $filter) // parse elements filters
        {
            $filter = trim($filter);
            $filter_join_condition = 'AND';
            if (empty($filter)) continue;
            if(str_starts_with($filter, '||')){
                $filter_join_condition = 'OR';
                $filter = substr($filter, 2); // remove modificator
            }
            $f = null;
            // parse OR filters
            $or_filters = preg_split("|(?<!\\\),|", $filter);

            if (count($or_filters) > 1) {
                $f = [];
                foreach ($or_filters as $of) {

                    //single filter
                    if(empty($of)) continue;

                    list($field, $op, $value) = self::filterExpresion($of);

                    if (!isset($allowed_fields[$field])){
                        throw new FilterParserException(sprintf("filter by field %s is not allowed", $field));
                    }
                    if (!in_array($op, $allowed_fields[$field])){
                        throw new FilterParserException(sprintf("%s op is not allowed for filter by field %s",$op, $field));
                    }
                    // check if value has AND or OR values on same field
                    $same_field_op = null;
                    if(str_contains($value, '&&')){
                        $values = explode('&&', $value);
                        if (count($values) > 1) {
                            $value = $values;
                            $same_field_op = 'AND';
                        }
                    }
                    else if(str_contains($value, '||')){
                        $values = explode('||', $value);
                        if (count($values) > 1) {
                            $value = $values;
                            $same_field_op = 'OR';
                        }
                    }

                    $f_or = self::buildFilter($field, $op, $value, $same_field_op);
                    if (!is_null($f_or))
                        $f[] = $f_or;
                }
            } else {
                //single filter

                list($field, $op, $value) = self::filterExpresion($filter);

                // check if value has AND or OR values on same field
                $same_field_op = null;
                if(str_contains($value, '&&')){
                    $values = explode('&&', $value);
                    if (count($values) > 1) {
                        $value = $values;
                        $same_field_op = 'AND';
                    }
                }
                else if(str_contains($value, '||')){
                    $values = explode('||', $value);
                    if (count($values) > 1) {
                        $value = $values;
                        $same_field_op = 'OR';
                    }
                }

                if (!isset($allowed_fields[$field])){
                    throw new FilterParserException(sprintf("filter by field %s is not allowed", $field));
                }
                if (!in_array($op, $allowed_fields[$field])){
                    throw new FilterParserException(sprintf("%s op is not allowed for filter by field %s",$op, $field));

                }

                $f = self::buildFilter($field, $op, $value, $same_field_op);
            }

            if (!is_null($f)) {
                $res[] = $f;
                $filter_join_conditions[] = $filter_join_condition;
            }
        }
        return new Filter($res, $filters, $filter_join_conditions);
    }

    /**
     * @param string $exp
     * @return array
     * @throws FilterParserException
     */
    public static function filterExpresion(string $exp){

        preg_match('/[@=<>][=>@]{0,1}/', $exp, $matches);

        if (count($matches) != 1)
            throw new FilterParserException(sprintf("invalid OR filter format %s (should be [:FIELD_NAME:OPERAND:VALUE])", $exp));

        $op       = $matches[0];
        $operands = explode($op, $exp, 2);
        $field    = $operands[0];
        $value    = $operands[1];

        return [$field, $op, $value];
    }
    /**
     * Factory Method
     *
     * @param string $field
     * @param string $op
     * @param mixed $value
     * @param string $same_field_op
     * @return FilterElement|null
     */
    public static function buildFilter($field, $op, $value, $same_field_op = null )
    {
        switch ($op) {
            case '==':
                return FilterElement::makeEqual($field, $value, $same_field_op);
                break;
            case '=@':
                return FilterElement::makeLike($field, $value, $same_field_op);
                break;
            case '@@':
                return FilterElement::makeLikeStart($field, $value, $same_field_op);
                break;
            case '>':
                return FilterElement::makeGreather($field, $value, $same_field_op);
                break;
            case '>=':
                return FilterElement::makeGreatherOrEqual($field, $value, $same_field_op);
                break;
            case '<':
                return FilterElement::makeLower($field, $value, $same_field_op);
                break;
            case '<=':
                return FilterElement::makeLowerOrEqual($field, $value, $same_field_op);
                break;
            case '<>':
                return FilterElement::makeNotEqual($field, $value, $same_field_op);
                break;
        }
        return null;
    }
}