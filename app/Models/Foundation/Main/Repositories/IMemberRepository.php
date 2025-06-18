<?php namespace models\main;

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

use models\summit\Summit;
use models\utils\IBaseRepository;
use utils\Filter;
use utils\Order;
use utils\PagingInfo;
use utils\PagingResponse;
/**
 * Interface IMemberRepository
 * @package models\main
 */
interface IMemberRepository extends IBaseRepository
{
    /**
     * @param string $email
     * @return Member|null
     */
    public function getByEmail($email):?Member;

    /**
     * @param string $email
     * @return Member|null
     */
    public function getByEmailExclusiveLock($email):?Member;

    /**
     * @param string $fullname
     * @return Member|null
     */
    public function getByFullName(string $fullname):?Member;

    /**
     * @param PagingInfo $paging_info
     * @param Filter|null $filter
     * @param Order|null $order
     * @return PagingResponse
     */
    public function getAllByPage(PagingInfo $paging_info, Filter $filter = null, Order $order = null);


    /**
     * @param PagingInfo $paging_info
     * @param Filter|null $filter
     * @param Order|null $order
     * @return PagingResponse
     */
    public function getAllCompaniesByPage(PagingInfo $paging_info, Filter $filter = null, Order $order = null);

    /**
     * @param int $external_id
     * @return Member|null
     */
    public function getByExternalId(int $external_id):?Member;

    /**
     * @param int $external_id
     * @return Member|null
     */
    public function getByExternalIdExclusiveLock(int $external_id): ?Member;

    /**
     * @param Summit $summit
     * @param PagingInfo $paging_info
     * @param Filter|null $filter
     * @param Order|null $order
     * @return PagingResponse
     */
    public function getSubmittersBySummit(Summit $summit, PagingInfo $paging_info, Filter $filter = null, Order $order = null);

    /**
     * @param Summit $summit
     * @param PagingInfo $paging_info
     * @param Filter|null $filter
     * @param Order|null $order
     * @return PagingResponse
     * @throws \Doctrine\DBAL\Exception
     */
    public function getSubmittersIdsBySummit(Summit $summit, PagingInfo $paging_info, Filter $filter = null, Order $order = null);
}