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
use App\Utils\FilterUtils;
use Illuminate\Support\Facades\Log;
use libs\utils\JsonUtils;
use models\summit\Summit;
use utils\Filter;

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
     * @throws \Exception
     */
    public function serialize($expand = null, array $fields = [], array $relations = [], array $params = [])
    {
        $summit = $this->object;
        if (!$summit instanceof Summit) return [];
        $values = parent::serialize($expand, $fields, $relations, $params);

        $filter = $params['filter'] ?? null;

        $start_date = null;
        $end_date = null;

        if ($filter instanceof Filter) list($start_date, $end_date) = FilterUtils::parseDateRangeUTC($filter);

        $values['total_active_tickets'] = $summit->getActiveTicketsCount($start_date, $end_date);
        $values['total_inactive_tickets'] = $summit->getInactiveTicketsCount($start_date, $end_date);
        $values['total_orders'] = $summit->getTotalOrdersCount($start_date, $end_date);
        $values['total_active_assigned_tickets'] = $summit->getActiveAssignedTicketsCount($start_date, $end_date);
        $values['total_payment_amount_collected'] = JsonUtils::toJsonMoney($summit->getTotalPaymentAmountCollected($start_date, $end_date));
        $values['total_refund_amount_emitted'] = JsonUtils::toJsonMoney($summit->getTotalRefundAmountEmitted($start_date, $end_date));

        /** Tickets per type  **/
        $res = [];
        $res1 = $summit->getActiveTicketsCountPerTicketType($start_date, $end_date);
        $res2 = $summit->getCheckedInActiveTicketsCountPerTicketType($start_date, $end_date);

        foreach($summit->getTicketTypes() as $tt){
            $type = $tt->getName();
            $col1 = array_column($res1, 'type');
            $col2 = array_column($res2, 'type');
            $key1 = array_search($type, $col1);
            $key2 = array_search($type, $col2);
            $tickets_qty = $key1 !== false ? $res1[$key1]['qty']: 0;
            $checkin_qty = $key2 !== false ? $res2[$key2]['qty']: 0;

            $res[] = [
                'type' => $type,
                'badge_type' => $tt->hasBadgeType() ? $tt->getBadgeType()->getName(): 'TBD',
                'available_qty' => $tt->getQuantity2Sell(),
                'sold_qty' => intval($tickets_qty),
                'checkin_qty' => intval($checkin_qty),
            ];
        }

        $values['total_tickets_per_type'] = $res;

        /** Badge per type **/

        $res = [];
        $res1 = $summit->getActiveBadgesCountPerBadgeType($start_date, $end_date);
        $res2 = $summit->getActiveCheckedInBadgesCountPerBadgeType($start_date, $end_date);

        foreach($summit->getBadgeTypes() as $bt){
            $type = $bt->getName();
            $col1 = array_column($res1, 'type');
            $col2 = array_column($res2, 'type');
            $key1 = array_search($type, $col1);
            $key2 = array_search($type, $col2);
            $tickets_qty = $key1 !== false ? $res1[$key1]['qty']: 0;
            $checkin_qty = $key2 !== false ? $res2[$key2]['qty']: 0;

            $res[] = [
                'type' => $type,
                'badges_qty' => intval($tickets_qty),
                'checkin_qty' => intval($checkin_qty),
            ];
        }

        $values['total_badges_per_type'] = $res;

        // attendees

        $values['total_checked_in_attendees'] = $summit->getInPersonCheckedInAttendeesCount($start_date, $end_date);
        $values['total_virtual_attendees'] = $summit->getVirtualAttendeesCount($start_date, $end_date);

        $values['total_non_checked_in_attendees'] = $summit->getInPersonNonCheckedInAttendeesCount($start_date, $end_date);
        $values['total_virtual_non_checked_in_attendees'] = $summit->getVirtualNonCheckedInAttendeesCount($start_date, $end_date);

        /** Tickets per badge feature type **/

        $res  = [];
        $res1 = $summit->getActiveTicketsPerBadgeFeatureType($start_date, $end_date);
        $res2 = $summit->getAttendeesCheckinPerBadgeFeatureType($start_date, $end_date);

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
                'sold_qty' => intval($tickets_qty),
                'checkin_qty' => intval($checkin_qty),
            ];
        }

        $values['total_tickets_per_badge_feature'] = $res;

        return $values;
    }

}