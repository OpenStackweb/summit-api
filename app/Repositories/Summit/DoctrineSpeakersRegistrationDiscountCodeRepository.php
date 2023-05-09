<?php namespace App\Repositories\Summit;
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

use App\Models\Foundation\Summit\Repositories\ISpeakersRegistrationDiscountCodeRepository;
use App\Repositories\SilverStripeDoctrineRepository;
use models\summit\AssignedPromoCodeSpeaker;
use models\summit\SpeakersRegistrationDiscountCode;
use utils\DoctrineFilterMapping;
use utils\Filter;
use utils\Order;
use utils\PagingInfo;

/**
 * Class DoctrineSpeakersRegistrationDiscountCodeRepository
 * @package App\Repositories\Summit
 */
class DoctrineSpeakersRegistrationDiscountCodeRepository
    extends SilverStripeDoctrineRepository
    implements ISpeakersRegistrationDiscountCodeRepository
{
    /**
     * @return string
     */
    protected function getBaseEntity()
    {
        return SpeakersRegistrationDiscountCode::class;
    }

    /**
     * @return array
     */
    protected function getFilterMappings(): array
    {
        return [
            'email'     => new DoctrineFilterMapping("m.email :operator :value"),
            'full_name' => new DoctrineFilterMapping("concat(m.first_name, ' ', m.last_name) :operator :value"),
        ];
    }

    /**
     * @inheritDoc
     */
    public function getDiscountCodeSpeakers(
        SpeakersRegistrationDiscountCode $discount_code, PagingInfo $paging_info, Filter $filter = null, Order $order = null)
    {
        return $this->getParametrizedAllByPage(function () use ($discount_code) {
            return $this->getEntityManager()
                ->createQueryBuilder()
                ->select("o")
                ->from(AssignedPromoCodeSpeaker::class, 'o')
                ->join('o.registration_discount_code', 'd')
                ->join('o.speaker', 's')
                ->join('s.member', 'm')
                ->where("d.id = :discount_code")
                ->setParameter("discount_code", $discount_code);
        },
            $paging_info,
            $filter,
            $order,
            function ($query) {
                //default order
                return $query->addOrderBy("s.id", 'ASC');
            });
    }
}