<?php namespace libs\utils;
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



/**
 * Class JsonUtils
 * http://json.org/
 * @package libs\utils
 */
abstract class JsonUtils
{
    /**
     * A string is a sequence of zero or more Unicode characters, wrapped in double quotes, using backslash escapes.
     * A character is represented as a single character string. A string is very much like a C or Java string.
     * @param string $value
     * @return string
     */
    public static function toJsonString($value)
    {
        return $value;
    }

    /**
     * @param $value
     * @return string
     */
    public static function toObfuscatedEmail($value){
        $em   = explode("@", $value);
        $name = implode(array_slice($em, 0, count($em) - 1), '@');
        $len  = floor(mb_strlen($name) / 2);
        $obfuscated_email = mb_substr($name, 0, $len) . str_repeat('*', $len) . "@" . end($em);
        return $obfuscated_email;
    }

    /**
     * @param string $url
     * @return string
     */
    public static function encodeUrl(?string $url):?string{
        if(empty($url)) return null;
        $url= rawurlencode($url);
        $url = str_replace("%3A",":", $url);
        $url = str_replace("%2F","/", $url);
        $url = str_replace("%3D","=", $url);
        $url = str_replace("%3F","?", $url);
        $url = str_replace("%26","&", $url);
        return $url;
    }

    /**
     * @param string $value
     * @return bool
     */
    public static function toJsonBoolean($value)
    {
        if(empty($value)) return false;
        return boolval($value);
    }

    /**
     * @param string $value
     * @return int|null
     */
    public static function toJsonInt($value)
    {
        if(empty($value)) return 0;
        return intval($value);
    }

    /**
     * @param string $value
     * @return float|null
     */
    public static function toJsonFloat($value)
    {
        if(empty($value)) return 0.00;
        return floatval($value);
    }

    /**
     * @param string $value
     * @return int
     */
    public static function toEpoch($value)
    {
        if(empty($value)) return 0;
        $datetime = new \DateTime($value);
        return $datetime->getTimestamp();
    }
}