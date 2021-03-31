<?php namespace App\Repositories\Summit;
/**
 * Copyright 2021 OpenStack Foundation
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

use App\Models\Foundation\Summit\Repositories\IPresentationActionTypeRepository;
use App\Repositories\SilverStripeDoctrineRepository;
use models\summit\PresentationActionType;
use utils\DoctrineLeftJoinFilterMapping;

/**
 * Class DoctrinePresentationActionTypeRepository
 * @package App\Repositories\Summit
 */
final class DoctrinePresentationActionTypeRepository
    extends SilverStripeDoctrineRepository
    implements IPresentationActionTypeRepository
{

    /**
     * @inheritDoc
     */
    protected function getBaseEntity()
    {
       return PresentationActionType::class;
    }

    /**
     * @return array
     */
    protected function getFilterMappings()
    {
        return [
            'label' => 'e.label:json_string',
            'summit_id' => new DoctrineLeftJoinFilterMapping("e.summit", "s" ,"s.id :operator :value")
        ];
    }

    /**
     * @return array
     */
    protected function getOrderMappings()
    {
        return [
            'id' => 'e.id',
            'order' => 'e.order',
            'label' => 'e.label',
        ];
    }
}