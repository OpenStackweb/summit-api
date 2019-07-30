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

use models\summit\SummitTaxType;
use App\Models\Foundation\Summit\Repositories\ISummitTaxTypeRepository;
use App\Repositories\SilverStripeDoctrineRepository;
use utils\DoctrineLeftJoinFilterMapping;
/**
 * Class DoctrineSummitTaxTypeRepository
 * @package App\Repositories\Summit
 */
final class DoctrineSummitTaxTypeRepository
  extends SilverStripeDoctrineRepository
    implements ISummitTaxTypeRepository
{

    /**
     * @return string
     */
    protected function getBaseEntity()
    {
        return SummitTaxType::class;
    }

    /**
     * @param string $name
     * @return SummitTaxType|null
     */
    public function getByName(string $name): ?SummitTaxType
    {
        return $this->findOneBy([
            'name' => trim($name)
        ]);
    }

    /**
     * @param string $tax_id
     * @return SummitTaxType|null
     */
    public function getByTaxID(string $tax_id): ?SummitTaxType
    {
        return $this->findOneBy([
            'tax_id' => trim($tax_id)
        ]);
    }

    /**
     * @return array
     */
    protected function getFilterMappings()
    {
        return [
            'name'        => 'e.name:json_string',
            'summit_id'   => new DoctrineLeftJoinFilterMapping("e.summit", "s" ,"s.id :operator :value")
        ];
    }

    /**
     * @return array
     */
    protected function getOrderMappings()
    {
        return [
            'id'   => 'e.id',
            'name' => 'e.name',
        ];
    }
}