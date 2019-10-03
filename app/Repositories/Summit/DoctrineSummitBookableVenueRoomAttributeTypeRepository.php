<?php namespace App\Repositories\Summit;
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
use App\Models\Foundation\Summit\Repositories\ISummitBookableVenueRoomAttributeTypeRepository;
use App\Repositories\SilverStripeDoctrineRepository;
use models\summit\SummitBookableVenueRoomAttributeType;
use utils\DoctrineJoinFilterMapping;

/**
 * Class DoctrineSummitBookableVenueRoomAttributeTypeRepository
 * @package App\Repositories\Summit
 */
final class DoctrineSummitBookableVenueRoomAttributeTypeRepository
    extends SilverStripeDoctrineRepository implements ISummitBookableVenueRoomAttributeTypeRepository
{

    /**
     * @return array
     */
    protected function getFilterMappings()
    {
        return
        [
            'type' => 'e.type:json_string',
            'summit_id' => new DoctrineJoinFilterMapping
            (
                'e.summit',
                's',
                "s.id :operator :value"
            ),
        ];
    }

    /**
     * @return array
     */
    protected function getOrderMappings()
    {
        return [
            'id'   => 'e.id',
            'type' => 'e.type',
        ];
    }

    /**
     * @return string
     */
    protected function getBaseEntity()
    {
        return SummitBookableVenueRoomAttributeType::class;
    }
}