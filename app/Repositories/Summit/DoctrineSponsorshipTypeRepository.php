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
use App\Models\Foundation\Summit\Repositories\ISponsorshipTypeRepository;
use App\Repositories\SilverStripeDoctrineRepository;
use models\summit\SponsorshipType;
/**
 * Class DoctrineSponsorshipTypeRepository
 * @package App\Repositories\Summit
 */
final class DoctrineSponsorshipTypeRepository
    extends SilverStripeDoctrineRepository
    implements ISponsorshipTypeRepository
{

    /**
     * @return string
     */
    protected function getBaseEntity()
    {
       return SponsorshipType::class;
    }

    /**
     * @param string $name
     * @return SponsorshipType|null
     */
    public function getByName(string $name): ?SponsorshipType
    {
        return $this->findOneBy(['name' => trim($name)]);
    }

    /**
     * @param string $label
     * @return SponsorshipType|null
     */
    public function getByLabel(string $label): ?SponsorshipType
    {
        return $this->findOneBy(['label' => trim($label)]);
    }

    /**
     * @return array
     */
    protected function getFilterMappings()
    {
        return [
            'name'  => 'e.name:json_string',
            'label' => 'e.label:json_string',
            'size'  => 'e.size:json_string',
        ];
    }

    /**
     * @return array
     */
    protected function getOrderMappings()
    {
        return [
            'id'    => 'e.id',
            'name'  => 'e.name',
            'label' => 'e.label',
            'size'  => 'e.size',
            'order' => 'e.orde',
        ];
    }

    /**
     * @return int
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getMaxOrder(): int
    {
        $sql = <<<SQL
select MAX(`Order`) from SponsorshipType;
SQL;
        $stm   = $this->getEntityManager()->getConnection()->executeQuery($sql);

        return intval($stm->fetchColumn(0));
    }
}