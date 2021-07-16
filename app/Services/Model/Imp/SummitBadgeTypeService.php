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
use App\Models\Foundation\Summit\Factories\SummitBadgeTypeFactory;
use libs\utils\ITransactionService;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use models\summit\Summit;
use models\summit\SummitBadgeType;
/**
 * Class SummitBadgeTypeService
 * @package App\Services\Model
 */
final class SummitBadgeTypeService extends AbstractService
    implements ISummitBadgeTypeService
{
    public function __construct(ITransactionService $tx_service)
    {
        parent::__construct($tx_service);
    }

    /**
     * @param Summit $summit
     * @param array $data
     * @return SummitBadgeType
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function addBadgeType(Summit $summit, array $data): SummitBadgeType
    {
        return $this->tx_service->transaction(function () use ($summit, $data) {

            $name = trim($data['name']);

            $former_badge_type = $summit->getBadgeTypeByName($name);

            if (!is_null($former_badge_type)) {
                throw new ValidationException("badge type name already exists");
            }

            $is_default = boolval($data['is_default']);
            $formerDefault = $summit->getDefaultBadgeType();
            if($is_default){
                if(!is_null($formerDefault)){
                    $formerDefault->setIsDefault(false);
                }
            }

            if(!$is_default && is_null($formerDefault)){
                // force default
                $data['is_default'] = true;
            }

            $badge_type = SummitBadgeTypeFactory::build($data);
            // add default access levels
            foreach($summit->getDefaultBadgeAccessLevelTypes() as $default_access_level){
                $badge_type->addAccessLevel($default_access_level);
            }
            $summit->addBadgeType($badge_type);
            return $badge_type;
        });
    }

    /**
     * @param Summit $summit
     * @param int $badge_type_id
     * @param array $data
     * @return SummitBadgeType
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function updateBadgeType(Summit $summit, int $badge_type_id, array $data): SummitBadgeType
    {
        return $this->tx_service->transaction(function () use ($summit, $badge_type_id, $data) {
            $badge_type = $summit->getBadgeTypeById($badge_type_id);
            if (is_null($badge_type))
                throw new EntityNotFoundException("badge type not found");

            if(isset($data['name'])) {
                $name = trim($data['name']);
                $former_badge_type = $summit->getBadgeTypeByName($name);
                if (!is_null($former_badge_type) && $former_badge_type->getId() != $badge_type_id) {
                    throw new ValidationException("badge type name already exists");
                }
            }
            // default check
            $formerDefault = $summit->getDefaultBadgeType();
            if(isset($data['is_default'])) { // trying to set a default ( true or false)
                $is_default = boolval($data['is_default']);
                if ($is_default && !$badge_type->isDefault()) { // is we want to set it as default ...
                    if(!is_null($formerDefault)){
                        // remove former default
                        $formerDefault->setIsDefault(false);
                    }
                }
                // if we dont have a default
                if(!$is_default && is_null($formerDefault)){
                    // force default
                    $data['is_default'] = true;
                }
            }

            return SummitBadgeTypeFactory::populate($badge_type, $data);
        });
    }

    /**
     * @param Summit $summit
     * @param int $badge_type_id
     * @throws EntityNotFoundException
     */
    public function deleteBadgeType(Summit $summit, int $badge_type_id): void
    {
        $this->tx_service->transaction(function () use ($summit, $badge_type_id) {
            $badge_type = $summit->getBadgeTypeById($badge_type_id);
            if (is_null($badge_type))
                throw new EntityNotFoundException("badge type not found");

            $summit->removeBadgeType($badge_type);
        });
    }

    /**
     * @param Summit $summit
     * @param int $badge_type_id
     * @param int $access_level_id
     * @return SummitBadgeType
     */
    public function addAccessLevelToBadgeType(Summit $summit, int $badge_type_id, int $access_level_id): SummitBadgeType
    {
        return $this->tx_service->transaction(function () use ($summit, $badge_type_id, $access_level_id) {
            $badge_type = $summit->getBadgeTypeById($badge_type_id);
            if (is_null($badge_type))
                throw new EntityNotFoundException("badge type not found");

            $access_level = $summit->getBadgeAccessLevelTypeById($access_level_id);
            if (is_null($access_level))
                throw new EntityNotFoundException("access level type not found");

            $badge_type->addAccessLevel($access_level);

            return $badge_type;
        });
    }

    /**
     * @param Summit $summit
     * @param int $badge_type_id
     * @param int $access_level_id
     * @return SummitBadgeType
     */
    public function removeAccessLevelFromBadgeType(Summit $summit, int $badge_type_id, int $access_level_id): SummitBadgeType
    {
        return $this->tx_service->transaction(function () use ($summit, $badge_type_id, $access_level_id) {
            $badge_type = $summit->getBadgeTypeById($badge_type_id);
            if (is_null($badge_type))
                throw new EntityNotFoundException("badge type not found");

            $access_level = $summit->getBadgeAccessLevelTypeById($access_level_id);
            if (is_null($access_level))
                throw new EntityNotFoundException("access level type not found");

            $badge_type->removeAccessLevel($access_level);

            return $badge_type;
        });
    }

    /**
     * @param Summit $summit
     * @param int $badge_type_id
     * @param int $feature_id
     * @return SummitBadgeType
     * @throws \Exception
     */
    public function addFeatureToBadgeType(Summit $summit, int $badge_type_id, int $feature_id): SummitBadgeType
    {
        return $this->tx_service->transaction(function () use ($summit, $badge_type_id, $feature_id) {
            $badge_type = $summit->getBadgeTypeById($badge_type_id);
            if (is_null($badge_type))
                throw new EntityNotFoundException("badge type not found");

            $feature = $summit->getFeatureTypeById($feature_id);
            if (is_null($feature))
                throw new EntityNotFoundException("feature not found");

            $badge_type->addBadgeFeatureType($feature);

            return $badge_type;
        });
    }

    /**
     * @param Summit $summit
     * @param int $badge_type_id
     * @param int $feature_id
     * @return SummitBadgeType
     * @throws \Exception
     */
    public function removeFeatureFromBadgeType(Summit $summit, int $badge_type_id, int $feature_id): SummitBadgeType
    {
        return $this->tx_service->transaction(function () use ($summit, $badge_type_id, $feature_id) {
            $badge_type = $summit->getBadgeTypeById($badge_type_id);
            if (is_null($badge_type))
                throw new EntityNotFoundException("badge type not found");

            $feature = $summit->getFeatureTypeById($feature_id);
            if (is_null($feature))
                throw new EntityNotFoundException("feature not found");

            $badge_type->removeBadgeFeatureType($feature);

            return $badge_type;
        });
    }
}