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

use App\Models\Foundation\Summit\Repositories\IPresentationMediaUploadRepository;
use App\Repositories\SilverStripeDoctrineRepository;
use Doctrine\ORM\QueryBuilder;
use models\summit\PresentationMediaUpload;
use utils\Filter;

/**
 * Class DoctrinePresentationMediaUploadRepository
 * @package App\Repositories\Summit
 */
final class DoctrinePresentationMediaUploadRepository
extends SilverStripeDoctrineRepository
    implements IPresentationMediaUploadRepository
{
    /**
     * @return string
     */
    protected function getBaseEntity()
    {
        return PresentationMediaUpload::class;
    }

    /**
     * @return array
     */
    protected function getFilterMappings()
    {
        return [
            'type_id' => 't.id'
        ];
    }

    /**
     * @return array
     */
    protected function getOrderMappings()
    {
        return ['id'];
    }


    /**
     * @param QueryBuilder $query
     * @return QueryBuilder
     */
    protected function applyExtraJoins(QueryBuilder $query, ?Filter $filter = null){
        $query = $query->innerJoin("e.media_upload_type", "t");
        return $query;
    }
}