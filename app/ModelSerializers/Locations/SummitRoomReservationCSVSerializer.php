<?php namespace App\ModelSerializers\Locations;
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

use models\summit\SummitRoomReservation;


/**
 * Class SummitRoomReservationCSVSerializer
 * @package App\ModelSerializers\Locations
 */
final class SummitRoomReservationCSVSerializer extends SummitRoomReservationSerializer
{
    public function serialize($expand = null, array $fields = [], array $relations = [], array $params = [])
    {
        $reservation = $this->object;
        if (!$reservation instanceof SummitRoomReservation)
            return [];

        $values = parent::serialize($expand, $fields, $relations, $params);
        $values['owner_name'] = $reservation->getOwner()->getFullName();
        $values['owner_email'] = $reservation->getOwner()->getEmail();

        return $values;
    }
}