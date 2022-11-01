<?php
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

namespace App\Repositories\Summit;
use App\Models\Foundation\Summit\Repositories\ISponsorSocialNetworkRepository;
use App\Repositories\SilverStripeDoctrineRepository;
use models\summit\SponsorSocialNetwork;
use utils\DoctrineLeftJoinFilterMapping;

/**
 * Class DoctrineSponsorSocialNetworkRepository
 * @package App\Repositories\Summit
 */
final class DoctrineSponsorSocialNetworkRepository
    extends SilverStripeDoctrineRepository
    implements ISponsorSocialNetworkRepository
{
    /**
     * @return array
     */
    protected function getOrderMappings()
    {
        return [
            'id'    => 'e.id',
        ];
    }

    protected function getFilterMappings()
    {
        return [
            'sponsor_id' =>
                new DoctrineLeftJoinFilterMapping("e.sponsor", "s" ,"s.id :operator :value"),
        ];
    }

    /**
     * @return string
     */
    protected function getBaseEntity()
    {
        return SponsorSocialNetwork::class;
    }
}