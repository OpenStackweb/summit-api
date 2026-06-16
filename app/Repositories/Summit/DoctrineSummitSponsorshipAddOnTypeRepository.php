<?php namespace App\Repositories\Summit;
/*
 * Copyright 2026 OpenStack Foundation
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

use App\Models\Foundation\Summit\Repositories\ISummitSponsorshipAddOnTypeRepository;
use App\Repositories\SilverStripeDoctrineRepository;
use models\summit\SummitSponsorshipAddOnType;

/**
 * Class DoctrineSummitSponsorshipAddOnTypeRepository
 * @package App\Repositories\Summit
 */
final class DoctrineSummitSponsorshipAddOnTypeRepository
    extends SilverStripeDoctrineRepository
    implements ISummitSponsorshipAddOnTypeRepository
{
    /**
     * @return string
     */
    protected function getBaseEntity(): string
    {
        return SummitSponsorshipAddOnType::class;
    }

    /**
     * @return array
     */
    protected function getFilterMappings(): array
    {
        return [
            'id'   => 'e.id',
            'name' => 'e.name:json_string',
        ];
    }

    /**
     * @return array
     */
    protected function getOrderMappings(): array
    {
        return [
            'id'   => 'e.id',
            'name' => 'e.name',
        ];
    }

    /**
     * @param string $name
     * @return SummitSponsorshipAddOnType|null
     */
    public function getByName(string $name): ?SummitSponsorshipAddOnType
    {
        return $this->findOneBy(['name' => trim($name)]);
    }
}
