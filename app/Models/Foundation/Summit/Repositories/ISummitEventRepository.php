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

use App\Models\Foundation\Summit\IPublishableEvent;
use utils\Filter;
use utils\Order;
use utils\PagingInfo;
use utils\PagingResponse;

/**
 * Interface ISummitEventRepository
 * @package models\summit
 */
interface ISummitEventRepository extends ISummitEventPublishRepository
{
    /**
     * @param IPublishableEvent $event
     * @return IPublishableEvent[]
     */
    public function getPublishedOnSameTimeFrame(IPublishableEvent $event): array;

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
    public function getAllPublishedTagsByPage(PagingInfo $paging_info, Filter $filter = null, Order $order = null):PagingResponse;

    /**
     * @param PagingInfo $paging_info
     * @param Filter|null $filter
     * @param Order|null $order
     * @return PagingResponse
     */
    public function getAllByPageLocationTBD(PagingInfo $paging_info, Filter $filter = null, Order $order = null);

    /**
     * @param int $event_id
     */
    public function cleanupScheduleAndFavoritesForEvent(int $event_id):void;

    /**
     * @param Summit $summit
     * @param array $external_ids
     * @return mixed
     */
    public function getPublishedEventsBySummitNotInExternalIds(Summit $summit, array $external_ids);

    /**
     * @param int $summit_id,
     * @return array
     */
    public function getPublishedEventsIdsBySummit(int $summit_id):array;

    /**
     * @param int $summit_id ,
     * @return int
     */
    public function getLastPresentationOrderBySummit(int $summit_id):int;
}