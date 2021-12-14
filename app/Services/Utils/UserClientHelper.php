<?php namespace App\Services\Utils;
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
/**
 * Class UserClientHelper
 * @package App\Services\Utils
 */
final class UserClientHelper
{
    /**
     * returns user current ip address
     * @return string
     */
    public static function getUserIp(): ?string
    {
        return $_SERVER['HTTP_CLIENT_IP'] ?? ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? ($_SERVER['REMOTE_ADDR'] ?? ''));
    }

    /**
     * @return string|null
     */
    public static function getUserOrigin():?string
    {
        return $_SERVER['HTTP_REFERER']?? ($_SERVER['HTTP_ORIGIN'] ?? '');
    }

    /**
     * @return string|null
     */
    public static function getUserBrowser():?string
    {
        return $_SERVER['HTTP_USER_AGENT']??'';
    }
}