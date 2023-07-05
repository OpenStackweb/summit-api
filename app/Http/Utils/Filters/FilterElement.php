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
class FilterElement extends AbstractFilterElement
{
    /**
     * @var mixed
     */
    private $value;
    /**
     * @var string
     */
    private $field;

    /**
     * @var string
     */
    private $same_field_op;

    /**
     * @param $field
     * @param $value
     * @param $operator
     * @param $same_field_op
     */
    protected function __construct($field, $value, $operator, $same_field_op)
    {
        parent::__construct($operator);
        $this->field    = $field;
        if($this->field == 'start_date'){
            $value = intval($value);
        }
        $this->value    = $value;
        $this->same_field_op = $same_field_op;
    }

    /**
     * @param mixed $value
     * @return $this
     */
    public function setValue($value)
    {
        $this->value = $value;
        return $this;
    }

    /**
     * @return string
     */
    public function getField()
    {
        return $this->field;
    }

    public function getRawValue(){
        return $this->value;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        switch($this->operator)
        {
            case 'like':
                if(is_array($this->value)){
                    $res = [];
                    foreach ($this->value as $val){
                        $res[]= empty($val) ? '' : "%".$val."%";
                    }
                    return $res;
                }
                return empty($this->value) ? '' : "%".$this->value."%";
                break;
            case 'start_like':
                if(is_array($this->value)){
                    $res = [];
                    foreach ($this->value as $val){
                        $res[]= empty($val) ? '' : $val."%";
                    }
                    return $res;
                }
                return  empty($this->value) ? '' : $this->value."%";
                break;
            default:
                return $this->value;
                break;
        }
    }

    public function getSameFieldOp():?string {
        return $this->same_field_op;
    }

    public static function makeEqual($field, $value, $same_field_op = null)
    {
        return new self($field, $value, '=', $same_field_op);
    }

    public static function makeGreather($field, $value, $same_field_op = null)
    {
        return new self($field, $value, '>', $same_field_op);
    }

    public static function makeGreatherOrEqual($field, $value, $same_field_op = null)
    {
        return new self($field, $value, '>=', $same_field_op);
    }

    public static function makeBetween($field, $value, $same_field_op = null)
    {
        if(!is_array($value)) throw new \InvalidArgumentException("Value must be an array.");
        if(count($value) !=2 ) throw new \InvalidArgumentException("Value must be an array of 2 elements.");
        return new self($field, $value, ['>=','<='], $same_field_op);
    }

    public static function makeLower($field, $value, $same_field_op = null)
    {
        return new self($field, $value, '<', $same_field_op);
    }

    public static function makeLowerOrEqual($field, $value, $same_field_op = null)
    {
        return new self($field, $value, '<=', $same_field_op);
    }

    public static function makeNotEqual($field, $value, $same_field_op = null)
    {
        return new self($field, $value, '<>', $same_field_op);
    }

    public static function makeLike($field, $value, $same_field_op = null)
    {
        return new self($field, $value, 'like', $same_field_op);
    }

    public static function makeLikeStart($field, $value, $same_field_op = null)
    {
        return new self($field, $value, 'start_like', $same_field_op);
    }

    public function __toString():string
    {
        return sprintf("%s%s%s", $this->field, is_array($this->operator) ? json_encode($this->operator): $this->operator, is_array($this->value)? json_encode($this->value):$this->value);
    }
}