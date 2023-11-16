<?php namespace App\ModelSerializers\Summit\Registration;
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

use models\summit\SummitAttendee;
use ModelSerializers\SilverStripeSerializer;
/**
 * Class SummitAttendeeCSVSerializer
 * @package App\ModelSerializers\Summit\Registration
 */
final class SummitAttendeeCSVSerializer extends SilverStripeSerializer
{
    protected static $array_mappings = [
        'MemberId'                => 'member_id:json_int',
        'SummitId'                => 'summit_id:json_int',
        'FirstName'               => 'first_name:json_string',
        'Surname'                 => 'last_name:json_string',
        'Email'                   => 'email:json_string',
        'CompanyName'             => 'company:json_string',
        'CompanyId'               => 'company_id:json_string',
        'DisclaimerAcceptedDate'  => 'disclaimer_accepted_date:datetime_epoch',
        'Status'                  => 'status:json_string',
        'VirtualCheckedIn'        => 'has_virtual_check_in:json_boolean',
    ];

    /**
     * @param $expand
     * @param array $fields
     * @param array $relations
     * @param array $params
     * @return array
     */
    public function serialize($expand = null, array $fields = [], array $relations = [], array $params = [])
    {
        $attendee = $this->object;
        if (!$attendee instanceof SummitAttendee) return [];
        $values = parent::serialize($expand, $fields, $relations, $params);

        $notes = [];
        foreach ($attendee->getOrderedNotes() as $note){
            $notes[] = $note->getContent();
        }

        $values['notes'] = implode("|", $notes);

        return $values;
    }
    
}