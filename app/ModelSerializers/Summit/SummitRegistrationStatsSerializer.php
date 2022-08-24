<?php namespace ModelSerializers;
/*
 * Copyright 2022 OpenStack Foundation
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
use App\Http\Exceptions\HTTP403ForbiddenException;
use libs\utils\JsonUtils;
use models\summit\Summit;
/**
 * Class SummitRegistrationStatsSerializer
 * @package ModelSerializers
 */
final class SummitRegistrationStatsSerializer extends SilverStripeSerializer
{
    /**
     * @param null $expand
     * @param array $fields
     * @param array $relations
     * @param array $params
     * @return array
     * @throws HTTP403ForbiddenException
     */
    public function serialize($expand = null, array $fields = [], array $relations = [], array $params = [])
    {
        $summit = $this->object;
        if (!$summit instanceof Summit) return [];
        $values = parent::serialize($expand, $fields, $relations, $params);

        $filter = $params['filter'] ?? null;

        $start_date = null;
        $end_date = null;

        if(is_null($filter))

        $values['total_active_tickets'] = $summit->getActiveTicketsCount();
        $values['total_inactive_tickets'] = $summit->getInactiveTicketsCount();
        $values['total_orders'] = $summit->getTotalOrdersCount();
        $values['total_active_assigned_tickets'] = $summit->getActiveAssignedTicketsCount();
        $values['total_payment_amount_collected'] = JsonUtils::toJsonFloat($summit->getTotalPaymentAmountCollected());
        $values['total_refund_amount_emitted'] = JsonUtils::toJsonFloat($summit->getTotalRefundAmountEmitted());
        $values['total_tickets_per_type'] = $summit->getActiveTicketsCountPerTicketType();
        $values['total_badges_per_type'] = $summit->getActiveBadgesCountPerBadgeType();
        $values['total_checked_in_attendees'] = $summit->getCheckedInAttendeesCount();
        $values['total_non_checked_in_attendees'] = $summit->getNonCheckedInAttendeesCount();
        $values['total_virtual_attendees'] = $summit->getVirtualAttendeesCount();

        $res  = [];
        $res1 = $summit->getActiveTicketsPerBadgeFeatureType();
        $res2 = $summit->getAttendeesCheckinPerBadgeFeatureType();

        foreach($summit->getBadgeFeaturesTypes() as $f){

            $type = $f->getName();
            $col1 = array_column($res1, 'type');
            $col2 = array_column($res2, 'type');
            $key1 = array_search($type, $col1);
            $key2 = array_search($type, $col2);
            $tickets_qty = $key1 !== false ? $res1[$key1]['qty']: 0;
            $checkin_qty = $key2 !== false ? $res2[$key2]['qty']: 0;

            $res[] = [
                'type' => $type,
                'tickets_qty' => intval($tickets_qty),
                'checkin_qty' => intval($checkin_qty),
            ];
        }

        $values['total_tickets_per_badge_feature'] = $res;

        return $values;
    }

}