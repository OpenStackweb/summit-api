<?php namespace App\Services\Model;
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
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use models\summit\Summit;
use models\summit\SummitBadgeFeatureType;
/**
 * Interface ISummitBadgeFeatureTypeService
 * @package App\Services\Model
 */
interface ISummitBadgeFeatureTypeService
{

    /**
     * @param Summit $summit
     * @param array $data
     * @return SummitBadgeFeatureType
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function addBadgeFeatureType(Summit $summit, array $data):SummitBadgeFeatureType;

    /**
     * @param Summit $summit
     * @param int $feature_id
     * @param array $data
     * @return SummitBadgeFeatureType
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function updateBadgeFeatureType(Summit $summit, int $feature_id, array $data):SummitBadgeFeatureType;

    /**
     * @param Summit $summit
     * @param int $feature_id
     * @throws EntityNotFoundException
     */
    public function deleteBadgeFeatureType(Summit $summit, int $feature_id):void;
}