<?php namespace App\Repositories\Summit;

/*
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
use App\Models\Foundation\Summit\Repositories\ISummitSubmissionInvitationRepository;
use App\Repositories\SilverStripeDoctrineRepository;
use Doctrine\ORM\QueryBuilder;
use models\summit\SummitSubmissionInvitation;
use utils\DoctrineCaseFilterMapping;
use utils\DoctrineLeftJoinFilterMapping;
use utils\DoctrineSwitchFilterMapping;
use utils\Filter;
/**
 * Class DoctrineSummitSubmissionInvitationRepository
 * @package App\Repositories\Summit
 */
final class DoctrineSummitSubmissionInvitationRepository
    extends SilverStripeDoctrineRepository
    implements ISummitSubmissionInvitationRepository
{

    /**
     * @return string
     */
    protected function getBaseEntity()
    {
        return SummitSubmissionInvitation::class;
    }

    /**
     * @param QueryBuilder $query
     * @return QueryBuilder
     */
    protected function applyExtraJoins(QueryBuilder $query, ?Filter $filter = null){
        $query = $query->join('e.summit', 's');
        return $query;
    }

    /**
     * @return array
     */
    protected function getFilterMappings()
    {
        return [
            'email' => 'e.email:json_string',
            'first_name' => 'e.first_name:json_string',
            'last_name' => 'e.last_name:json_string',
            'is_sent' => new DoctrineSwitchFilterMapping([
                    'true' => new DoctrineCaseFilterMapping(
                        'true',
                        "e.sent_date is not null"
                    ),
                    'false' => new DoctrineCaseFilterMapping(
                        'false',
                        "e.sent_date is null"
                    ),
                ]
            ),
            'summit_id' => new DoctrineLeftJoinFilterMapping("e.summit", "s" ,"s.id :operator :value"),
            'tags' => new DoctrineLeftJoinFilterMapping("e.tags", "t","t.tag :operator :value"),
            'tags_id' => new DoctrineLeftJoinFilterMapping("e.tags", "t","t.id :operator :value"),
        ];
    }

    /**
     * @return array
     */
    protected function getOrderMappings()
    {
        return [
            'id'   => 'e.id',
            'email' => 'e.email',
            'sent_date' => 'e.sent_date',
        ];
    }




}