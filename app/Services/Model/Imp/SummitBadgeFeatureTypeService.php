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
use App\Models\Foundation\Summit\Factories\SummitBadgeFeatureTypeFactory;
use libs\utils\ITransactionService;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use models\summit\Summit;
use models\summit\SummitBadgeFeatureType;
/**
 * Class SummitBadgeFeatureTypeService
 * @package App\Services\Model
 */
final class SummitBadgeFeatureTypeService extends AbstractService
implements ISummitBadgeFeatureTypeService
{

    public function __construct(ITransactionService $tx_service)
    {
        parent::__construct($tx_service);
    }

    /**
     * @param Summit $summit
     * @param array $data
     * @return SummitBadgeFeatureType
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function addBadgeFeatureType(Summit $summit, array $data): SummitBadgeFeatureType
    {
        return $this->tx_service->transaction(function() use($summit, $data){
            $name = trim($data['name']);
            $former_feature = $summit->getFeatureTypeByName($name);
            if(!is_null($former_feature)){
                throw new ValidationException("feature type name already exists");
            }

            $feature = SummitBadgeFeatureTypeFactory::build($data);

            $summit->addFeatureType($feature);

            return $feature;

        });
    }

    /**
     * @param Summit $summit
     * @param int $feature_id
     * @param array $data
     * @return SummitBadgeFeatureType
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function updateBadgeFeatureType(Summit $summit, int $feature_id, array $data): SummitBadgeFeatureType
    {
        return $this->tx_service->transaction(function() use($summit, $feature_id, $data){

            $feature = $summit->getFeatureTypeById($feature_id);
            if(is_null($feature))
                throw new EntityNotFoundException('feature not found');

            if(isset($data['name'])) {
                $name = trim($data['name']);
                $former_feature = $summit->getFeatureTypeByName($name);
                if (!is_null($former_feature) && $former_feature->getId() != $feature_id) {
                    throw new ValidationException("feature type name already exists");
                }
            }

            return SummitBadgeFeatureTypeFactory::populate($feature, $data);

        });
    }

    /**
     * @param Summit $summit
     * @param int $feature_id
     * @throws EntityNotFoundException
     */
    public function deleteBadgeFeatureType(Summit $summit, int $feature_id): void
    {
         $this->tx_service->transaction(function() use($summit, $feature_id){

            $feature = $summit->getFeatureTypeById($feature_id);
            if(is_null($feature))
                throw new EntityNotFoundException('feature not found');

            $summit->removeFeatureType($feature);

        });
    }
}