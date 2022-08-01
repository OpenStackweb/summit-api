<?php namespace App\Services\Model\Imp;
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
use App\Services\Model\AbstractService;
use App\Services\Model\IBadgeViewTypeService;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use models\summit\Summit;
use models\summit\SummitBadgeViewType;
use models\summit\SummitBadgeViewTypeFactory;

/**
 * Class BadgeViewTypeService
 * @package App\Services\Model\Imp
 */
final class BadgeViewTypeService
extends AbstractService
    implements IBadgeViewTypeService

{

    /**
     * @param Summit $summit
     * @param array $payload
     * @return SummitBadgeViewType|null
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function add(Summit $summit, array $payload): ?SummitBadgeViewType
    {
        return $this->tx_service->transaction(function() use($summit, $payload){

            if(boolval($payload['is_default'])){
                $formerDefault = $summit->getDefaultBadgeViewType();
                if(!is_null($formerDefault))
                    throw new ValidationException(sprintf("There is a former default view."));
            }

            $name = trim($payload['name']);

            $former = $summit->getBadgeViewTypeByName($name);
            if(!is_null($former))
                throw new ValidationException(sprintf("There is a former view called %s.", $name));

            $viewType =  SummitBadgeViewTypeFactory::build($payload);

            $summit->addBadgeViewType($viewType);

            return $viewType;

        });
    }

    /**
     * @param Summit $summit
     * @param int $id
     * @param array $payload
     * @return SummitBadgeViewType|null
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function update(Summit $summit, int $id, array $payload): ?SummitBadgeViewType
    {
        return $this->tx_service->transaction(function() use($summit, $id, $payload){

            $viewType = $summit->getBadgeViewTypeById($id);
            if(is_null($viewType))
                throw new EntityNotFoundException("View Type not found.");

            if(boolval($payload['is_default'])){
                $formerDefault = $summit->getDefaultBadgeViewType();
                if(!is_null($formerDefault) && $formerDefault->getId() !== $id)
                    throw new ValidationException(sprintf("There is a former default view."));
            }

            $name = trim($payload['name']);

            $former = $summit->getBadgeViewTypeByName($name);
            if(!is_null($former) && $former->getId() !== $id)
                throw new ValidationException(sprintf("There is a former view called %s.", $name));

            return SummitBadgeViewTypeFactory::populate($viewType, $payload);
        });
    }

    /**
     * @param Summit $summit
     * @param int $id
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function delete(Summit $summit, int $id): void
    {
        $this->tx_service->transaction(function () use ($summit, $id) {
            $viewType = $summit->getBadgeViewTypeById($id);
            if(is_null($viewType))
                throw new EntityNotFoundException("View Type not found.");

            $summit->removeBadgeViewType($viewType);
        });
    }
}