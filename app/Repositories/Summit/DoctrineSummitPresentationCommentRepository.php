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
use App\Models\Foundation\Summit\Repositories\ISummitPresentationCommentRepository;
use App\Repositories\SilverStripeDoctrineRepository;
use Doctrine\ORM\QueryBuilder;
use models\summit\SummitPresentationComment;
use utils\Filter;
/**
 * Class DoctrineSummitPresentationCommentRepository
 * @package App\Repositories\Summit
 */
final class DoctrineSummitPresentationCommentRepository
    extends SilverStripeDoctrineRepository
    implements ISummitPresentationCommentRepository
{

    /**
     * @param QueryBuilder $query
     * @return QueryBuilder
     */
    protected function applyExtraJoins(QueryBuilder $query, ?Filter $filter = null){
        $query = $query
            ->innerJoin('e.presentation', 'p')
            ->innerJoin('e.creator', 'c');
        return $query;
    }

    /**
     * @return array
     */
    protected function getFilterMappings()
    {
        return [
            'presentation_id' => 'p.id',
            'creator_id' => 'c.id',
            'is_activity' => 'e.is_activity',
            'is_public' => 'e.is_public',
            'body' => 'e.body',
        ];
    }

    /**
     * @return array
     */
    protected function getOrderMappings()
    {
        return [
            'id' => 'e.id',
            'creator_id' => 'c.id',
        ];
    }

    /**
     * @return string
     */
    protected function getBaseEntity()
    {
        return SummitPresentationComment::class;
    }
}