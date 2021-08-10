<?php namespace App\Models\Foundation\Summit\Repositories;
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

use models\summit\Summit;
use models\summit\SummitOrder;
use models\utils\IBaseRepository;
use utils\PagingInfo;
use utils\PagingResponse;
/**
 * Interface ISummitOrderRepository
 * @package App\Models\Foundation\Summit\Repositories
 */
interface ISummitOrderRepository extends IBaseRepository
{
    /**
     * @param string $hash
     * @return SummitOrder|null
     */
    public function getByHashLockExclusive(string $hash): ?SummitOrder;

    /**
     * @param string $payment_gateway_cart_id
     * @return SummitOrder|null
     */
    public function getByPaymentGatewayCartIdExclusiveLock(string $payment_gateway_cart_id): ?SummitOrder;

    /**
     * @param string $externalId
     * @return SummitOrder|null
     */
    public function getByExternalIdLockExclusive(string $externalId):?SummitOrder;

    /**
     * @param Summit $summit
     * @param string $externalId
     * @return SummitOrder|null
     */
    public function getByExternalIdAndSummitLockExclusive(Summit $summit, string $externalId):?SummitOrder;

    /**
     * @param string $email
     * @return mixed
     */
    public function getAllByOwnerEmail(string $email);

    /**
     * @param string $email
     * @return mixed
     */
    public function getAllByOwnerEmailAndOwnerNotSet(string $email);

    /**
     * @param int $minutes
     * @param int $max
     * @return mixed
     */
    public function getAllReservedOlderThanXMinutes(int $minutes, int $max = 100);

    /**
     * @param int $minutes
     * @param int $max
     * @return mixed
     */
    public function getAllConfirmedOlderThanXMinutes(int $minutes, int $max = 100);

    /**
     * @param Summit $summit
     * @param PagingInfo $paging_info
     * @return PagingResponse
     */
    public function getAllOrderThatNeedsEmailActionReminder(Summit $summit, PagingInfo $paging_info):PagingResponse;
}