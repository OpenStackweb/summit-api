<?php namespace App\Jobs\Emails\BookableRooms;
/**
 * Copyright 2020 OpenStack Foundation
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

use App\Jobs\Emails\AbstractSummitEmailJob;
use App\Jobs\Emails\IMailTemplatesConstants;
use libs\utils\FormatUtils;
use models\summit\SummitRoomReservation;

/**
 * Class AbstractBookableRoomReservationEmail
 * @package App\Jobs\Emails\BookableRooms
 */
abstract class AbstractBookableRoomReservationEmail extends AbstractSummitEmailJob {
  protected function getTo(SummitRoomReservation $reservation): string {
    return $reservation->getOwner()->getEmail();
  }

  /**
   * AbstractBookableRoomReservationEmail constructor.
   * @param string $to
   * @param SummitRoomReservation $reservation
   */
  public function __construct(SummitRoomReservation $reservation) {
    $payload = [];
    $room = $reservation->getRoom();
    $summit = $room->getSummit();
    $payload[IMailTemplatesConstants::owner_fullname] = $reservation->getOwner()->getFullName();
    $payload[IMailTemplatesConstants::owner_email] = $reservation->getOwner()->getEmail();
    $payload[IMailTemplatesConstants::room_complete_name] = $room->getCompleteName();
    // dates

    $local_start_date_time = $reservation->getLocalStartDatetime();
    $local_end_date_time = $reservation->getLocalEndDatetime();
    $time_zone_label = $summit->getTimeZoneLabel();

    if (empty($time_zone_label)) {
      $time_zone_label = $summit->getTimeZone()->getName();
    }

    $payload[IMailTemplatesConstants::reservation_start_datetime] =
      $local_start_date_time->format("F d, Y") .
      " at " .
      $local_start_date_time->format("h:i A") .
      " " .
      $time_zone_label;
    $payload[IMailTemplatesConstants::reservation_end_datetime] =
      $local_end_date_time->format("F d, Y") .
      " at " .
      $local_end_date_time->format("h:i A") .
      " " .
      $time_zone_label;

    $payload[IMailTemplatesConstants::reservation_created_datetime] = $reservation
      ->getCreated()
      ->format("F d, Y");
    $payload[IMailTemplatesConstants::reservation_amount] = FormatUtils::getNiceFloat(
      $reservation->getAmount(),
    );
    $payload[IMailTemplatesConstants::reservation_currency] = $reservation->getCurrency();
    $payload[IMailTemplatesConstants::reservation_id] = $reservation->getId();
    $payload[IMailTemplatesConstants::room_capacity] = $room->getCapacity();
    $payload[
      IMailTemplatesConstants::reservation_refunded_amount
    ] = $reservation->getRefundedAmount();

    $template_identifier = $this->getEmailTemplateIdentifierFromEmailEvent($summit);
    parent::__construct($summit, $payload, $template_identifier, $this->getTo($reservation));
  }

  /**
   * @return array
   */
  public static function getEmailTemplateSchema(): array {
    $payload = parent::getEmailTemplateSchema();

    $payload[IMailTemplatesConstants::owner_fullname]["type"] = "string";
    $payload[IMailTemplatesConstants::owner_email]["type"] = "string";
    $payload[IMailTemplatesConstants::room_complete_name]["type"] = "string";
    $payload[IMailTemplatesConstants::reservation_start_datetime]["type"] = "string";
    $payload[IMailTemplatesConstants::reservation_end_datetime]["type"] = "string";
    $payload[IMailTemplatesConstants::reservation_created_datetime]["type"] = "string";
    $payload[IMailTemplatesConstants::reservation_amount]["type"] = "string";
    $payload[IMailTemplatesConstants::reservation_currency]["type"] = "string";
    $payload[IMailTemplatesConstants::reservation_id]["type"] = "int";
    $payload[IMailTemplatesConstants::room_capacity]["type"] = "int";
    $payload[IMailTemplatesConstants::reservation_refunded_amount]["type"] = "string";

    return $payload;
  }
}
