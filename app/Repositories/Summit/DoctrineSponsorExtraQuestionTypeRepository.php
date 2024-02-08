<?php namespace App\Repositories\Summit;
/*
 * Copyright 2024 OpenStack Foundation
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

use App\Models\Foundation\Summit\ExtraQuestions\SummitSponsorExtraQuestionType;
use App\Models\Foundation\Summit\Repositories\ISponsorExtraQuestionTypeRepository;
use App\Repositories\Main\DoctrineExtraQuestionTypeRepository;
use models\summit\Sponsor;
use utils\DoctrineLeftJoinFilterMapping;
use utils\Filter;
use utils\Order;
use utils\PagingInfo;
use utils\PagingResponse;

/**
 * Class DoctrineSponsorExtraQuestionTypeRepository
 * @package App\Repositories\Summit
 */
final class DoctrineSponsorExtraQuestionTypeRepository
    extends DoctrineExtraQuestionTypeRepository
    implements ISponsorExtraQuestionTypeRepository
{

    /**
     * @return array
     */
    protected function getFilterMappings()
    {
        return [
            'sponsor_id' => new DoctrineLeftJoinFilterMapping("e.sponsor", "s" ,"s.id :operator :value"),
            'name'       => 'e.name:json_string',
            'label'      => 'e.label:json_string',
            'type'       => 'e.type:json_string',
        ];
    }

    /**
     * @return array
     */
    protected function getOrderMappings()
    {
        return array_merge(parent::getOrderMappings() , [
            'type' => 'e.type',
        ]);
    }

    /**
     * @return array
     */
    public function getQuestionsMetadata()
    {
        $metadata = [];
        foreach (Sponsor::getAllowedQuestionTypes() as $type){
            $metadata[] = [
                'type' => $type,
            ];
        }
        return $metadata;
    }

    /**
     *
     * @return string
     */
    protected function getBaseEntity()
    {
        return SummitSponsorExtraQuestionType::class;
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