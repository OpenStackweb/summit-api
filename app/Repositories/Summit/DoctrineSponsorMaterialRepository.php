<?php namespace App\Repositories\Summit;
/*
 * Copyright 2022 OpenStack Foundation
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
use App\Models\Foundation\Summit\Repositories\ISponsorMaterialRepository;
use App\Repositories\SilverStripeDoctrineRepository;
use models\summit\SponsorMaterial;
use utils\DoctrineLeftJoinFilterMapping;

/**
 * Class DoctrineSponsorMaterialRepository
 * @package App\Repositories\Summit
 */
final class DoctrineSponsorMaterialRepository extends SilverStripeDoctrineRepository
implements ISponsorMaterialRepository
{
    /**
     * @return array
     */
    protected function getOrderMappings()
    {
        return [
            'id'    => 'e.id',
            'order' => 'e.order',
        ];
    }

    protected function getFilterMappings()
    {
        return [
            'sponsor_id' =>
                new DoctrineLeftJoinFilterMapping("e.sponsor", "s" ,"s.id :operator :value"),
            'type' => 'e.type'
        ];
    }

    /**
     * @return string
     */
    protected function getBaseEntity()
    {
        return SponsorMaterial::class;
    }
}