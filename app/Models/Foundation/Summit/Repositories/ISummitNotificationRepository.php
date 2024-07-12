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
use App\Models\Foundation\Main\Repositories\IPushNotificationRepository;
use models\main\Member;
use utils\Filter;
use utils\Order;
use utils\PagingInfo;
use utils\PagingResponse;
/**
 * Interface ISummitNotificationRepository
 * @package models\summit
 */
interface ISummitNotificationRepository extends IPushNotificationRepository {
  /**
   * @param Summit $summit
   * @param PagingInfo $paging_info
   * @param Filter|null $filter
   * @param Order|null $order
   * @return PagingResponse
   */
  public function getAllByPageBySummit(
    Summit $summit,
    PagingInfo $paging_info,
    Filter $filter = null,
    Order $order = null,
  ): PagingResponse;

  /**
   * @param Member|null $current_member
   * @param Summit $summit
   * @param PagingInfo $paging_info
   * @param Filter|null $filter
   * @param Order|null $order
   * @return PagingResponse
   */
  public function getAllByPageByUserBySummit(
    ?Member $current_member,
    Summit $summit,
    PagingInfo $paging_info,
    Filter $filter = null,
    Order $order = null,
  ): PagingResponse;
}
