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

use App\Models\Foundation\Summit\Repositories\ISpeakersSummitRegistrationPromoCodeRepository;
use App\Repositories\SilverStripeDoctrineRepository;
use models\summit\AssignedPromoCodeSpeaker;
use models\summit\SpeakersSummitRegistrationPromoCode;
use utils\DoctrineFilterMapping;
use utils\Filter;
use utils\Order;
use utils\PagingInfo;

/**
 * Class DoctrineSpeakersSummitRegistrationPromoCodeRepository
 * @package App\Repositories\Summit
 */
class DoctrineSpeakersSummitRegistrationPromoCodeRepository
    extends SilverStripeDoctrineRepository
    implements ISpeakersSummitRegistrationPromoCodeRepository
{
    /**
     * @return string
     */
    protected function getBaseEntity()
    {
        return SpeakersSummitRegistrationPromoCode::class;
    }

    /**
     * @return array
     */
    protected function getFilterMappings(): array
    {
        return [
            'first_name' => new DoctrineFilterMapping(
                "( LOWER(m.first_name) :operator LOWER(:value) )".
                "OR ( LOWER(s.first_name) :operator LOWER(:value) )"
            ),
            'last_name' => new DoctrineFilterMapping(
                "( LOWER(m.last_name) :operator LOWER(:value) )".
                " OR ( LOWER(s.last_name) :operator LOWER(:value) )"
            ),
            'email' => [
                Filter::buildEmailField('m.email'),
                Filter::buildEmailField('m.second_email'),
                Filter::buildEmailField('m.third_email'),
                Filter::buildEmailField('rr.email'),
            ],
            'full_name' => new DoctrineFilterMapping
            (
                "( CONCAT(LOWER(m.first_name), ' ', LOWER(m.last_name)) :operator LOWER(:value) )".
                " OR ( CONCAT(LOWER(s.first_name), ' ', LOWER(s.last_name)) :operator LOWER(:value) )"
            ),
        ];
    }

    /**
     * @return array
     */
    protected function getOrderMappings()
    {
        return [
            'id'        => 'o.id',
            'email'     => 'm.email',
            'email_sent' => 'o.sent',
            'redeemed'   => 'o.redeemed',
            "full_name" => <<<SQL
COALESCE(LOWER(CONCAT(s.first_name, ' ', s.last_name)), LOWER(CONCAT(m.first_name, ' ', m.last_name)))
SQL,
        ];
    }

    /**
     * @inheritDoc
     */
    public function getPromoCodeSpeakers
    (
        SpeakersSummitRegistrationPromoCode $promo_code, PagingInfo $paging_info, Filter $filter = null, Order $order = null
    )
    {
        return $this->getParametrizedAllByPage(function () use ($promo_code) {
            return $this->getEntityManager()
                ->createQueryBuilder()
                ->select("o")
                ->from(AssignedPromoCodeSpeaker::class, "o")
                ->join('o.registration_promo_code', 'p')
                ->join('o.speaker', 's')
                ->leftJoin("s.registration_request", "rr")
                ->join('s.member', 'm')
                ->where("p.id = :promo_code")
                ->setParameter("promo_code", $promo_code);
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