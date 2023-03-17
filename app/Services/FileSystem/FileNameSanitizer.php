<?php namespace App\Services\FileSystem;
/**
 * Copyright 2020 OpenStack Foundation
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
use Behat\Transliterator\Transliterator;
/**
 * Class FileNameSanitizer
 * @package App\Services\FileSystem
 */
final class FileNameSanitizer
{
    private static $default_replacements = [
        '/\s/' => '-', // remove whitespace
        '/_/' => '-', // underscores to dashes
        '/[^A-Za-z0-9+.\-]+/' => '', // remove non-ASCII chars, only allow alphanumeric plus dash and dot
        '/[\-]{2,}/' => '-', // remove duplicate dashes
        '/^[\.\-_]+/' => '', // Remove all leading dots, dashes or underscores
    ];

    /**
     * @param string $filename
     * @return string
     */
    public static function sanitize(string $filename):string {
        $filename = trim(Transliterator::utf8ToAscii($filename));
        foreach(self::$default_replacements as $regex => $replace) {
            $filename = preg_replace($regex, $replace, $filename);
        }
        return $filename;
    }
}