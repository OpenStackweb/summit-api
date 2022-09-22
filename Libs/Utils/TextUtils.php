<?php namespace libs\utils;
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

/**
 * Class TextUtils
 * @package libs\utils
 */
final class TextUtils
{
    /**
     * @param string $str
     * @return bool
     */
    public static function isUnicode(string $str):bool{
        return strlen($str) != strlen(utf8_decode($str));
    }

    /**
     * @param string $str
     * @return string
     */
    public static function trim(string $str):string{
        if(empty($str)) return $str;
        if (self::isUnicode($str))
        {
            return preg_replace('/^[\pZ\pC]+|[\pZ\pC]+$/u', '', $str);
        }
        return trim($str);
    }
}