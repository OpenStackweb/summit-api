<?php namespace App\ModelSerializers\Summit;
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
use Libs\ModelSerializers\AbstractSerializer;
use models\summit\SponsorBadgeScan;
use ModelSerializers\SerializerRegistry;
use ModelSerializers\SilverStripeSerializer;
/**
 * Class SponsorBadgeScanCSVSerializer
 * @package App\ModelSerializers\Summit
 */
final class SponsorBadgeScanCSVSerializer extends AbstractSerializer
{
    protected static $array_mappings = [
        'ScanDate'     => 'scan_date:datetime_epoch',
        'QRCode'       => 'qr_code:json_string',
        'SponsorId'    => 'sponsor_id:json_int',
        'UserId'       => 'user_id:json_int',
        'BadgeId'      => 'badge_id:json_int',
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
        $scan = $this->object;
        if (!$scan instanceof SponsorBadgeScan) return [];
        $values = parent::serialize($expand, $fields, $relations, $params);

        $attendee = $scan->getBadge()->getTicket()->getOwner();

        $values['attendee_first_name'] = $attendee->hasMember() ? $attendee->getMember()->getFirstName() : $attendee->getFirstName();
        $values['attendee_last_name']  = $attendee->hasMember() ? $attendee->getMember()->getLastName() :$attendee->getSurname();
        $values['attendee_email']      = $attendee->getEmail();
        $values['attendee_company']    = $attendee->getCompanyName();
        return $values;
    }
}