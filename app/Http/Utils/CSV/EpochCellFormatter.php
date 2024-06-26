<?php namespace App\Http\Utils;
/**
 * Copyright 2018 OpenStack Foundation
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
use DateTime;
use DateTimeZone;
/**
 * Class EpochCellFormatter
 * @package App\Http\Utils
 */
final class EpochCellFormatter implements ICellFormatter
{
    const DefaultFormat = 'Y-m-d H:i:s';
    /**
     * @var string
     */
    private $format;

    /**
     * @var DateTimeZone
     */
    private $dateTimeZone;

    /**
     * @var bool
     */
    private $displayTimeZone;

    /**
     * @param string $format
     * @param DateTimeZone|null $dateTimeZone
     * @param bool $showTimeZone
     */
    public function __construct(string $format = EpochCellFormatter::DefaultFormat, DateTimeZone $dateTimeZone = null, bool $displayTimeZone = false)
    {
        $this->format = $format;
        $this->dateTimeZone = $dateTimeZone;
        $this->displayTimeZone = $displayTimeZone;
    }

    /**
     * @param string $val
     * @return string
     */
    public function format($val): string
    {
        if(empty($val)) return '';
        $date = new DateTime("@$val");
        $tzName = 'UTC';
        if(!is_null($this->dateTimeZone)) {
            $date->setTimezone($this->dateTimeZone);
            $tzName = $this->dateTimeZone->getName();
        }
        return $this->displayTimeZone ?
            sprintf("%s (%s)", $date->format($this->format), $tzName) :
            $date->format($this->format);
    }
}