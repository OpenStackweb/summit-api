<?php namespace ModelSerializers;
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
use Illuminate\Support\Facades\Config;
use models\summit\SummitAttendeeTicket;
/**
 * Class GuestEditionSummitAttendeeTicketSerializer
 * @package ModelSerializers
 */
class GuestEditionSummitAttendeeTicketSerializer extends BaseSummitAttendeeTicketSerializer
{
    /**
     * @param null $expand
     * @param array $fields
     * @param array $relations
     * @param array $params
     * @return array
     */
    public function serialize($expand = null, array $fields = [], array $relations = [], array $params = [])
    {
        $ticket = $this->object;
        if (!$ticket instanceof SummitAttendeeTicket) return [];
        $values   = parent::serialize($expand, $fields, $relations, $params);

        $order = $ticket->getOrder();
        $summit = $order->getSummit();
        $values['edit_link'] = sprintf("%s/a/my-tickets",$summit->getMarketingSiteUrl());

        return $values;
    }
}