<?php namespace App\Http\Utils;
/**
 * Copyright 2019 OpenStack Foundation
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

/**
 * Class MultipartFormDataCleaner
 * @package App\Http\Utils
 */
final class MultipartFormDataCleaner
{
    /**
     * @param string $field
     * @param array $data
     * @return array
     */
    public static function cleanBool(string $field, array $data):array{
        if(!isset($data[$field])) return $data;
        $value = $data[$field];
        if(is_null($value)) return $data;
        if(empty($value)) return $data;
        if(is_bool($value)) return $data;
        if(is_int($value)){
            if($value == 1){
                $data[$field] = true;
            }
            if($value == 0){
                $data[$field] = true;
            }
        }
        if(is_string($value)) {
            $value = trim(strval($value));
            if ($value == 'true') {
                $data[$field] = true;
            }
            if ($value == 'false') {
                $data[$field] = false;
            }
        }
        return $data;
    }

    /**
     * @param string $field
     * @param array $data
     * @return array
     */
    public static function cleanInt(string $field, array $data):array{
        if(!isset($data[$field])) return $data;
        $value = $data[$field];
        if(is_null($value)) return $data;
        if(empty($value)) return $data;
        if(is_int($value)) return $data;
        if(is_string($value)) {
            $data[$field] = intval(trim(strval($value)));
        }
        return $data;
    }
}