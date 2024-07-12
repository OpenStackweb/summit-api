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

use models\summit\SummitAttendee;
use models\summit\SummitOrder;

/**
 * Class SummitOrderOwnSerializer
 * @package ModelSerializers
 */
final class SummitOrderOwnSerializer extends SummitOrderCheckoutSerializer {
  /**
   * @param null $expand
   * @param array $fields
   * @param array $relations
   * @param array $params
   * @return array
   */
  public function serialize(
    $expand = null,
    array $fields = [],
    array $relations = [],
    array $params = [],
  ) {
    $order = $this->object;
    if (!$order instanceof SummitOrder) {
      return [];
    }
    $values = parent::serialize($expand, $fields, $relations, $params);

    $attendees_status = SummitAttendee::StatusComplete;
    $tickets_excerpt_by_ticket_type = [];
    // calculate excerpt
    foreach ($order->getTickets() as $ticket) {
      if (!$ticket->hasOwner()) {
        if ($attendees_status === SummitAttendee::StatusComplete) {
          $attendees_status = SummitAttendee::StatusIncomplete;
        }
      } else {
        // has owner
        $attendee = $ticket->getOwner();
        $attendee_current_status = $attendee->updateStatus();
        if ($attendees_status === SummitAttendee::StatusComplete) {
          $attendees_status = $attendee_current_status;
        }
      }
      $ticket_type_name = $ticket->getTicketTypeName();
      if (!isset($tickets_excerpt_by_ticket_type[$ticket_type_name])) {
        $tickets_excerpt_by_ticket_type[$ticket_type_name] = 0;
      }

      $tickets_excerpt_by_ticket_type[$ticket_type_name] =
        $tickets_excerpt_by_ticket_type[$ticket_type_name] + 1;
    }

    $values["attendees_status"] = $attendees_status;
    $values["tickets_excerpt_by_ticket_type"] = $tickets_excerpt_by_ticket_type;

    return $values;
  }
}
