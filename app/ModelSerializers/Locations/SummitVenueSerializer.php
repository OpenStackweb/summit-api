<?php namespace ModelSerializers\Locations;
/**
 * Copyright 2016 OpenStack Foundation
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

use Libs\ModelSerializers\Many2OneExpandSerializer;
use models\summit\SummitVenue;

/**
 * Class SummitVenueSerializer
 * @package ModelSerializers\Locations
 */
final class SummitVenueSerializer extends SummitGeoLocatedLocationSerializer
{
    protected static $array_mappings = array
    (
        'IsMain' => 'is_main::json_boolean',
    );

    protected static $allowed_relations = [
        'rooms',
        'floors',
    ];

    protected static $expand_mappings = [
        'floors' => [
            'type' => Many2OneExpandSerializer::class,
            'getter' => 'getFloors',
        ],
        'rooms' => [
            'type' => Many2OneExpandSerializer::class,
            'getter' => 'getRooms',
        ],
    ];

    /**
     * @param null $expand
     * @param array $fields
     * @param array $relations
     * @param array $params
     * @return array
     */
    public function serialize($expand = null, array $fields = array(), array $relations = array(), array $params = array() )
    {
        $venue  = $this->object;
        if(!$venue instanceof  SummitVenue) return [];
        if (!count($relations)) $relations = $this->getAllowedRelations();
        $values = parent::serialize($expand, $fields, $relations, $params);
        if(in_array('rooms', $relations) && !isset($values['rooms'])) {
            // rooms
            $rooms = [];
            foreach ($venue->getRooms() as $room) {
                $rooms[] = $room->getId();
            }

            if (count($rooms) > 0)
                $values['rooms'] = $rooms;
        }

        if(in_array('floors', $relations) && !isset($values['floors'])) {
            // floors
            $floors = [];
            foreach ($venue->getFloors() as $floor) {
                $floors[] = $floor->getId();
            }

            if (count($floors) > 0)
                $values['floors'] = $floors;
        }


        return $values;
    }

}