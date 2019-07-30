<?php namespace models\summit;
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
use models\utils\IBaseRepository;
/**
 * Interface ISummitAttendeeTicketRepository
 * @package models\summit
 */
interface ISummitAttendeeTicketRepository extends IBaseRepository
{
    /**
     * @param string $external_order_id
     * @param string $external_attendee_id
     * @return SummitAttendeeTicket
     */
    public function getByExternalOrderIdAndExternalAttendeeId($external_order_id, $external_attendee_id);

    /**
     * @param string $number
     * @return bool
     */
    public function existNumber(string $number):bool;

    /**
     * @param string $number
     * @return SummitAttendeeTicket|null
     */
    public function getByNumber(string $number):?SummitAttendeeTicket;

    /**
     * @param string $hash
     * @return SummitAttendeeTicket|null
     */
    public function getByHashExclusiveLock(string $hash):?SummitAttendeeTicket;

    /**
     * @param string $hash
     * @return SummitAttendeeTicket|null
     */
    public function getByFormerHashExclusiveLock(string $hash):?SummitAttendeeTicket;

    /**
     * @param string $hash
     * @return SummitAttendeeTicket|null
     */
    public function getByNumberExclusiveLock(string $number):?SummitAttendeeTicket;

    /**
     * @param string $external_order_id
     * @param string $external_attendee_id
     * @return SummitAttendeeTicket|null
     */
    public function getByExternalOrderIdAndExternalAttendeeIdExclusiveLock($external_order_id, $external_attendee_id):?SummitAttendeeTicket;

    /**
     * @param Summit $summit
     * @param string $external_attendee_id
     * @return SummitAttendeeTicket|null
     */
    public function getByExternalAttendeeIdExclusiveLock(Summit $summit, string $external_attendee_id):?SummitAttendeeTicket;

    /**
     * @param Summit $summit
     * @param string $external_order_id
     * @param string $external_attendee_id
     * @return SummitAttendeeTicket|null
     */
    public function getBySummitAndExternalOrderIdAndExternalAttendeeIdExclusiveLock(Summit $summit, $external_order_id, $external_attendee_id):?SummitAttendeeTicket;

}