<?php namespace models\summit;
/**
 * Copyright 2023 OpenStack Foundation
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

use App\Models\Foundation\Summit\ProposedSchedule\SummitProposedScheduleLock;
use models\utils\IBaseRepository;
use utils\Filter;
use utils\Order;
use utils\PagingInfo;

/**
 * Interface ISummitProposedScheduleLockRepository
 * @package models\summit
 */
interface ISummitProposedScheduleLockRepository extends IBaseRepository
{
    /**
     * @param int $summit_id
     * @param string $source
     * @param PagingInfo $paging_info
     * @param Filter|null $filter
     * @param Order|null $order
     * @return mixed
     */
    public function getBySummitAndSource
    (
        int $summit_id,
        string $source,
        PagingInfo $paging_info,
        Filter $filter = null,
        Order $order   = null
    );

    /**
     * @param int $summit_id
     * @param int $track_id
     * @return SummitProposedScheduleLock|null
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getBySummitAndTrackId(int $summit_id, int $track_id): ?SummitProposedScheduleLock;
}