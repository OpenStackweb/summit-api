<?php namespace App\Models\Foundation\Summit\Factories;
/**
 * Copyright 2019 OpenStack Foundation
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

use Eluceo\iCal\Component\Timezone;
use models\summit\Summit;
use models\summit\SummitRoomReservation;
/**
 * Class SummitRoomReservationFactory
 * @package App\Models\Foundation\Summit\Factories
 */
final class SummitRoomReservationFactory
{
    /**
     * @param Summit $summit
     * @param array $data
     * @return SummitRoomReservation
     */
    public static function build(Summit $summit, array $data){
        $reservation = new SummitRoomReservation;
        if(isset($data['owner']))
            $reservation->setOwner($data['owner']);
        if(isset($data['currency']))
            $reservation->setCurrency(trim($data['currency']));
        if(isset($data['amount']))
            $reservation->setAmount(floatval($data['amount']));

        // dates ( they came on local time epoch , so must be converted to utc using
        // summit timezone
        if(isset($data['start_datetime'])) {
            $val = intval($data['start_datetime']);
            $val = new \DateTime("@$val");
            $val->setTimezone($summit->getTimeZone());
            $reservation->setStartDatetime($summit->convertDateFromTimeZone2UTC($val));
        }

        if(isset($data['end_datetime'])){
            $val = intval($data['end_datetime']);
            $val = new \DateTime("@$val");
            $val->setTimezone($summit->getTimeZone());
            $reservation->setEndDatetime($summit->convertDateFromTimeZone2UTC($val));
        }

        return $reservation;
    }

}