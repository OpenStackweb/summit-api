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
use App\Models\Foundation\Summit\Repositories\ISummitOrderExtraQuestionTypeRepository;
use App\Repositories\Main\DoctrineExtraQuestionTypeRepository;
use models\summit\SummitOrderExtraQuestionType;
use utils\DoctrineCaseFilterMapping;
use utils\DoctrineLeftJoinFilterMapping;
use utils\DoctrineSwitchFilterMapping;
use utils\Filter;
use utils\Order;
use utils\PagingInfo;
use utils\PagingResponse;

/**
 * Class DoctrineSummitOrderExtraQuestionTypeRepository
 * @package App\Repositories\Summit
 */
final class DoctrineSummitOrderExtraQuestionTypeRepository
    extends DoctrineExtraQuestionTypeRepository
    implements ISummitOrderExtraQuestionTypeRepository
{

    /**
     * @return array
     */
    protected function getFilterMappings()
    {
        return array_merge(parent::getFilterMappings() , [
            'printable' => 'e.printable:json_boolean',
            'usage'     => 'e.usage:json_string',
            'summit_id' => new DoctrineLeftJoinFilterMapping("e.summit", "s" ,"s.id :operator :value"),
            'allowed_badge_feature_type_id' => new DoctrineLeftJoinFilterMapping("e.allowed_badge_features_types", "bft" ,"bft.id :operator :value"),
            'allowed_ticket_type_id' => new DoctrineLeftJoinFilterMapping("e.allowed_ticket_types", "tt" ,"tt.id :operator :value"),
            'has_badge_feature_types' =>
                new DoctrineSwitchFilterMapping([
                        'true' => new DoctrineCaseFilterMapping(
                            'true',
                            'SIZE(e.allowed_badge_features_types) > 0'
                        ),
                        'false' => new DoctrineCaseFilterMapping(
                            'false',
                            'SIZE(e.allowed_badge_features_types) = 0'
                        ),
                    ]
                ),
            'has_ticket_types' =>
                new DoctrineSwitchFilterMapping([
                        'true' => new DoctrineCaseFilterMapping(
                            'true',
                            'SIZE(e.allowed_ticket_types) > 0'
                        ),
                        'false' => new DoctrineCaseFilterMapping(
                            'false',
                            'SIZE(e.allowed_ticket_types) = 0'
                        ),
                    ]
                ),
        ]);
    }

    /**
     * @return array
     */
    protected function getOrderMappings()
    {
        return parent::getOrderMappings();
    }

    /**
     *
     * @return string
     */
    protected function getBaseEntity()
    {
        return SummitOrderExtraQuestionType::class;
    }

    /**
     * @param PagingInfo $paging_info
     * @param Filter|null $filter
     * @param Order|null $order
     * @return PagingResponse
     */
    public function getAllByPage(PagingInfo $paging_info, Filter $filter = null, Order $order = null): PagingResponse
    {
        return $this->getParametrizedAllByPage(function () {
            return $this->getEntityManager()->createQueryBuilder()
                ->select("e")
                ->from($this->getBaseEntity(), "e");
        },
            $paging_info,
            $filter,
            $order,
            function ($query) {
                //default order
                return $query;
            });
    }
}