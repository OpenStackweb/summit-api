<?php namespace repositories\main;
/**
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

use App\Models\Foundation\Main\Repositories\IUserStoryRepository;
use App\Models\Foundation\UserStories\UserStory;
use App\Repositories\SilverStripeDoctrineRepository;

/**
 * Class DoctrineUserStoryRepository
 * @package repositories\main
 */
final class DoctrineUserStoryRepository
    extends SilverStripeDoctrineRepository
    implements IUserStoryRepository
{
    /**
     * @return string
     */
    protected function getBaseEntity()
    {
        return UserStory::class;
    }

    /**
     * @return array
     */
    protected function getFilterMappings(): array
    {
        return [
            'name' => 'e.name:json_string',
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
        ];
    }
}