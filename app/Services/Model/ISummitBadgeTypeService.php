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
use models\summit\SummitBadgeType;
/**
 * Interface ISummitBadgeTypeService
 * @package App\Services\Model
 */
interface ISummitBadgeTypeService
{
    /**
     * @param Summit $summit
     * @param array $data
     * @return SummitBadgeType
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function addBadgeType(Summit $summit, array $data):SummitBadgeType;

    /**
     * @param Summit $summit
     * @param int $badge_type_id
     * @param array $data
     * @return SummitBadgeType
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function updateBadgeType(Summit $summit, int $badge_type_id, array $data):SummitBadgeType;

    /**
     * @param Summit $summit
     * @param int $badge_type_id
     * @throws EntityNotFoundException
     */
    public function deleteBadgeType(Summit $summit, int $badge_type_id):void;

    /**
     * @param Summit $summit
     * @param int $badge_type_id
     * @param int $access_level_id
     * @return SummitBadgeType
     */
    public function addAccessLevelToBadgeType(Summit $summit, int $badge_type_id,int $access_level_id):SummitBadgeType;

    /**
     * @param Summit $summit
     * @param int $badge_type_id
     * @param int $access_level_id
     * @return SummitBadgeType
     */
    public function removeAccessLevelFromBadgeType(Summit $summit, int $badge_type_id,int $access_level_id):SummitBadgeType;

    /**
     * @param Summit $summit
     * @param int $badge_type_id
     * @param int $feature_id
     * @return SummitBadgeType
     */
    public function addFeatureToBadgeType(Summit $summit, int $badge_type_id,int $feature_id):SummitBadgeType;

    /**
     * @param Summit $summit
     * @param int $badge_type_id
     * @param int $feature_id
     * @return SummitBadgeType
     */
    public function removeFeatureFromBadgeType(Summit $summit, int $badge_type_id,int $feature_id):SummitBadgeType;
}