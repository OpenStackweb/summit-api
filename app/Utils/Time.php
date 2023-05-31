<?php namespace App\Utils;
/*
 * Copyright 2023 OpenStack Foundation
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
 * Class Time
 * @package App\Utils
 */
final class Time
{
    /**
     * @param int $time
     * @return array
     */
    public static function getHourAndMinutesFromInt(int $time):array
    {
        $time = sprintf("%04d", $time);
        $hours = intval(substr($time,0,2));
        $minutes = intval(substr($time,2,2));
        return [$hours, $minutes];
    }
}