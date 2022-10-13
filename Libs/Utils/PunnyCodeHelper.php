<?php namespace App\libs\Utils;
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
use libs\utils\TextUtils;
/**
 * Class PunnyCodeHelper
 * @package App\libs\Utils
 */
final class PunnyCodeHelper
{
    /**
     * @param string|null $email
     * @return string|null
     */
    public static function encodeEmail(?string $email):?string{
        if(empty($email)) return null;
        $email_parts = explode('@', $email);
        if(count($email_parts) !== 2) return $email;
        return strtolower(TextUtils::trim($email_parts[0])).'@'.strtolower(TextUtils::trim(idn_to_ascii($email_parts[1])));
    }

    /**
     * @param string|null $email
     * @return string|null
     */
    public static function decodeEmail(?string $email):?string{
        if(empty($email)) return null;
        $email_parts = explode('@', $email);
        if(count($email_parts) !== 2) return $email;
        return strtolower(TextUtils::trim($email_parts[0])).'@'.strtolower(TextUtils::trim(idn_to_utf8($email_parts[1])));
    }
}