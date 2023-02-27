<?php namespace App\Models\Foundation\Summit;
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

use App\Events\ScheduleEntityLifeCycleEvent;
use DateTime;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use models\exceptions\ValidationException;
use models\summit\Summit;
use models\summit\SummitEvent;
use ReflectionClass;

/**
 * Trait TimeDurationRestricted
 * @package App\Models\Foundation\Summit
 */
trait TimeDurationRestrictedEvent
{
    /**
     * @param DateTime $value
     * @param Summit|null $summit
     */
    private function _setStartDate(DateTime $value, ?Summit $summit)
    {
        if (!is_null($summit)) {
            $value = $summit->convertDateFromTimeZone2UTC($value);
        }
        $end_date = $this->getEndDate();

        if (!is_null($end_date)) {
            $newDuration = $end_date->getTimestamp() - $value->getTimestamp();
            Log::debug(sprintf("TimeDurationRestrictedEvent::setStartDate id %s setting new duration %s", $this->id, $newDuration));;
            $this->duration = max($newDuration, 0);
        }

        $this->start_date = $value;
        Log::debug(sprintf("TimeDurationRestrictedEvent::setStartDate id %s start_date %s", $this->id, $this->start_date->getTimestamp()));
    }

    /**
     * @param DateTime $value
     * @param Summit|null $summit
     */
    private function _setEndDate(DateTime $value, ?Summit $summit)
    {
        if (!is_null($summit)) {
            $value = $summit->convertDateFromTimeZone2UTC($value);
        }

        $start_date = $this->getStartDate();
        if (!is_null($start_date)) {
            $newDuration = $value->getTimestamp() - $start_date->getTimestamp();
            Log::debug(sprintf("TimeDurationRestrictedEvent::setEndDate id %s newDuration %s", $this->id, $newDuration));
            $this->duration = $newDuration;
        }
        $this->end_date = $value;

        Log::debug(sprintf("SummitEvent::setEndDate id %s end_date %s", $this->id, $this->end_date->getTimestamp()));
    }

    /**
     * @param Summit|null $summit
     * @param int $duration_in_seconds
     * @param bool $skipDatesSetting
     * @throws ValidationException
     * @throws \Exception
     */
    private function _setDuration(?Summit $summit, int $duration_in_seconds, bool $skipDatesSetting = false): void
    {
        if ($duration_in_seconds < 0) {
            throw new ValidationException('Duration should be greater or equal than zero.');
        }

        if ($duration_in_seconds > 0 && $duration_in_seconds < (self::MIN_EVENT_MINUTES * 60)) {
            throw new ValidationException(sprintf('Duration should be greater than %s minutes.', self::MIN_EVENT_MINUTES));
        }

        $this->duration = $duration_in_seconds;

        if (!$skipDatesSetting) {
            $start_date = $this->getStartDate();
            if (!is_null($start_date)) {
                $start_date = clone $start_date;
                $value = $start_date->add(new \DateInterval('PT' . $duration_in_seconds . 'S'));

                if (!is_null($summit)) {
                    $value = $summit->convertDateFromUTC2TimeZone($value);
                }
                $this->setEndDate($value);
            }
        }
    }

    /**
     * @return DateTime|null
     */
    private function _getLocalStartDate(?Summit $summit)
    {
        if (!empty($this->start_date)) {
            $value = clone $this->start_date;
            if (!is_null($summit)) {
                $res = $summit->convertDateFromUTC2TimeZone($value);
            }
            return $res;
        }
        return null;
    }

    /**
     * @return DateTime|null
     */
    public function _getLocalEndDate(?Summit $summit)
    {
        if (!empty($this->end_date)) {
            $value = clone $this->end_date;
            if (!is_null($summit)) {
                $res = $summit->convertDateFromUTC2TimeZone($value);
            }
            return $res;
        }
        return null;
    }
}