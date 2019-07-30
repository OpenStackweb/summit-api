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
    public function serialize($expand = null, array $fields = array(), array $relations = array(), array $params = array())
    {
        $ticket = $this->object;
        if (!$ticket instanceof SummitAttendeeTicket) return [];
        $values   = parent::serialize($expand, $fields, $relations, $params);

        $base_url         = Config::get('registration.dashboard_base_url', null);
        $edit_ticket_link = Config::get('registration.dashboard_attendee_edit_form_url', null);

        if(empty($base_url))
            throw new \InvalidArgumentException("missing dashboard_base_url value");
        if(empty($edit_ticket_link))
            throw new \InvalidArgumentException("missing dashboard_attendee_edit_form_url value");

        $values['edit_link'] = sprintf($edit_ticket_link, $base_url, $ticket->getHash());

        return $values;
    }
}