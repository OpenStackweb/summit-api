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
use models\main\Member;
use models\utils\IBaseRepository;
use utils\Filter;
use utils\Order;
use utils\PagingInfo;
use utils\PagingResponse;
/**
 * Class ISummitAttendeeRepository
 * @package models\summit
 */
interface ISummitAttendeeRepository extends IBaseRepository
{
    /**
     * @param Summit $summit
     * @param PagingInfo $paging_info
     * @param Filter|null $filter
     * @param Order|null $order
     * @return PagingResponse
     */
    public function getBySummit(Summit $summit, PagingInfo $paging_info, Filter $filter = null, Order $order = null);

    /**
     * @param Summit $summit
     * @param Member $member
     * @return SummitAttendee|null
     */
    public function getBySummitAndMember(Summit $summit, Member $member):?SummitAttendee;

    /**
     * @param Member $member
     */
    public function getByMember(Member $member);

    /**
     * @param Summit $summit
     * @param string $email
     * @return SummitAttendee|null
     */
    public function getBySummitAndEmail(Summit $summit, string $email):?SummitAttendee;

    /**
     * @param Summit $summit
     * @param string $email
     * @param null|string $first_name
     * @param null|string $last_name
     * @param null|string $external_id
     * @return SummitAttendee|null
     */
    public function getBySummitAndEmailAndFirstNameAndLastNameAndExternalId(Summit $summit, string $email, ?string $first_name = null, ?string $last_name = null, ?string $external_id = null):?SummitAttendee;

    /**
     * @param string $email
     * @return mixed
     */
    public function getByEmail(string $email);

    /**
     * @param string $email
     * @return mixed
     */
    public function getByEmailAndMemberNotSet(string $email);

    /**
     * @param Summit $summit
     * @param null|string $external_id
     * @return SummitAttendee|null
     */
    public function getBySummitAndExternalId(Summit $summit, string $external_id):?SummitAttendee;

    /**
     * @param int $summit_id
     * @return bool
     * @throws \Doctrine\DBAL\Driver\Exception
     */
    public function deleteAllBySummit(int $summit_id):bool;

    /**
     * @param Summit $summit
     * @param string $first_name
     * @param string $last_name
     * @param SummitAttendee $manager
     * @return SummitAttendee|null
     */
    public function getBySummitAndFirstNameAndLastNameAndManager(Summit $summit, string $first_name, string $last_name, SummitAttendee $manager):?SummitAttendee;

}