<?php namespace models\summit;
/**
 * Copyright 2018 OpenStack Foundation
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
use App\Models\Foundation\Summit\Repositories\ISummitOwnedEntityRepository;
/**
 * Interface ISummitTicketTypeRepository
 * @package models\summit
 */
interface ISummitTicketTypeRepository extends ISummitOwnedEntityRepository
{
    /**
     * @param Summit $summit
     * @param array $ids
     * @return SummitTicketType[]
     */
    public function getByIdsExclusiveLock(Summit $summit, array $ids);

    /**
     * @param Summit $summit
     * @param string $type
     * @return SummitTicketType|null
     */
    public function getByType(Summit $summit, string $type):?SummitTicketType;

}