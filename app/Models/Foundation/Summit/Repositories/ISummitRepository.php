<?php namespace models\summit;
/**
 * Copyright 2015 OpenStack Foundation
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
use utils\Filter;
use utils\Order;
use utils\PagingInfo;
use utils\PagingResponse;

/**
 * Interface ISummitRepository
 * @package models\summit
 */
interface ISummitRepository extends IBaseRepository
{
    /**
     * @return Summit
     */
    public function getCurrent();

    /**
     * @return Summit
     */
    public function getCurrentAndAvailable();

    /**
     * @return Summit
     */
    public function getActive();

    /**
     * @return Summit[]
     */
    public function getAvailables();

    /**
     * @return Summit[]
     */
    public function getAllOrderedByBeginDate();

    /**
     * @param string $name
     * @return Summit
     */
    public function getByName($name);

    /**
     * @return Summit[]
     */
    public function getCurrentAndFutureSummits();

    /**
     * @param string $slug
     * @return Summit|null
     */
    public function getBySlug(string $slug):?Summit;

    /**
     * @param string $slug
     * @return Summit|null
     */
    public function getByQREncryptionKey(string $qr_enc_key): ?Summit;

    /**
     * @return Summit[]
     */
    public function getWithExternalFeed():array;

    /**
     * @return Summit[]
     */
    public function getOnGoing(): array;

    /**
     * @return array
     */
    public function getNotEnded():array;

    /**
     * @return array
     */
    public function getAllWithExternalRegistrationFeed():array;

    /**
     * @param Summit $summit
     * @param PagingInfo $paging_info
     * @param Filter|null $filter
     * @param Order|null $order
     * @return PagingResponse
     */
    public function getRegistrationCompanies(Summit $summit, PagingInfo $paging_info, Filter $filter = null, Order $order = null):PagingResponse;
}