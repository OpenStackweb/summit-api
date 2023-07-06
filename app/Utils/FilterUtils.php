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

use utils\Filter;


/**
 * Class FilterUtils
 * @package App\Utils
 */
final class FilterUtils
{
    /**
     * @param Filter $filter
     * @param string|null $date_from_name
     * @param string|null $date_to_name
     * @return array
     * @throws \Exception
     */
    public static function parseDateRangeUTC(
        Filter $filter, ?string $date_from_name = 'start_date', ?string $date_to_name = 'end_date'):array
    {
        $start_date = null;
        $end_date = null;

        if($filter->hasFilter($date_from_name)){
            $start_date = Filter::convertToDateTime($filter->getUniqueFilter($date_from_name)->getValue(), 'UTC');
            $start_date = new \DateTime($start_date, new \DateTimeZone('UTC'));
            $end_date =  $filter->hasFilter($date_to_name) ?
                Filter::convertToDateTime($filter->getUniqueFilter($date_to_name)->getValue(), 'UTC'):
                null;
            $end_date = !is_null($end_date) ? new \DateTime($end_date, new \DateTimeZone('UTC')) : null;
        }
        return [$start_date, $end_date];
    }
}