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
     * Returns true iff $str is valid UTF-8 and contains any non-ASCII codepoint.
     */
    public static function isUnicode(string $str): bool
    {
        if ($str === '') return false;

        // 1) Check valid UTF-8 (so using /u will be safe)
        $isUtf8 = function_exists('mb_check_encoding')
            ? mb_check_encoding($str, 'UTF-8')
            : (bool) @preg_match('//u', $str); // @ suppresses warning on invalid UTF-8

        if (!$isUtf8) return false;

        // 2) Contains any non-ASCII?
        return (bool) preg_match('/[^\x00-\x7F]/', $str);
    }

    /**
     * Trims Unicode separators and control chars when UTF-8; falls back to ASCII trim otherwise.
     */
    public static function trim(string $str): string
    {
        if ($str === '') return $str;

        if (self::isUnicode($str)) {
            // \pZ = separators, \pC = other controls; /u = Unicode mode
            return (string) preg_replace('/^[\pZ\pC]+|[\pZ\pC]+$/u', '', $str);
        }

        return trim($str);
    }
}