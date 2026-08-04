<?php namespace App\Utils;
/**
 * Copyright 2025 OpenStack Foundation
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
final class Base64
{

    public static function looksLikeBase64(string $s): bool
    {
        if ($s === '') return false;
        // Standard base64 alphabet (RFC 4648 §4) or URL-safe (§5: '-' and '_'), plus '=' padding
        if (!preg_match('#^[A-Za-z0-9+/_-]*={0,2}$#', $s)) return false;
        // Padding may be omitted entirely (it gets added below), but when present it must be
        // exactly what the data length requires (RFC 4648)
        $unpadded = rtrim($s, '=');
        $paddingLength = strlen($s) - strlen($unpadded);
        $remainder = strlen($unpadded) % 4;
        if ($unpadded === '' || $remainder === 1) return false;
        return $paddingLength === 0 || $paddingLength === (4 - $remainder);
    }

    public static function padBase64(string $s): string
    {
        $m = strlen($s) % 4;
        return $m ? $s . str_repeat('=', 4 - $m) : $s;
    }

    public static function tryBase64Decode(string $s): ?string
    {
        // strict base64_decode rejects the URL-safe alphabet: normalize it first
        $s = strtr($s, '-_', '+/');
        $padded = self::padBase64($s);
        $decoded = base64_decode($padded, true);
        return ($decoded === false) ? null : $decoded;
    }
}