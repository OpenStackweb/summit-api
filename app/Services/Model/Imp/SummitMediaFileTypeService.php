<?php namespace App\Services\Model\Imp;
/**
 * Copyright 2020 OpenStack Foundation
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

use App\Models\Foundation\Summit\Factories\SummitMediaFileTypeFactory;
use App\Models\Foundation\Summit\Repositories\ISummitMediaFileTypeRepository;
use App\Services\Model\ISummitMediaFileTypeService;
use libs\utils\ITransactionService;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use models\summit\SummitMediaFileType;

/**
 * Class SummitMediaFileTypeService
 * @package App\Services\Model\Imp
 */
final class SummitMediaFileTypeService
extends AbstractModelService
    implements ISummitMediaFileTypeService
{

    /**
     * @var ISummitMediaFileTypeRepository
     */
    private $repository;

    public function __construct
    (
        ISummitMediaFileTypeRepository $repository,
        ITransactionService $tx_service
    )
    {
        parent::__construct($tx_service);
        $this->repository = $repository;
    }

    /**
     * @inheritDoc
     */
    public function add(array $payload): SummitMediaFileType
    {
        return $this->tx_service->transaction(function() use($payload){
            $type = $this->repository->getByName(trim($payload['name']));
            if(!is_null($type))
                throw new ValidationException(sprintf("Name %s already exists.", $payload['name']));
            $type = SummitMediaFileTypeFactory::build($payload);
            $this->repository->add($type);
            return $type;
        });
    }

    /**
     * @inheritDoc
     */
    public function update(int $id, array $payload): SummitMediaFileType
    {
        return $this->tx_service->transaction(function() use($id, $payload){
            $type = $this->repository->getById($id);
            if(is_null($type))
                throw new EntityNotFoundException();
            if($type->IsSystemDefined())
                throw new ValidationException("You can not modify a system defined type.");

            if(isset($payload['name'])){
                $type = $this->repository->getByName(trim($payload['name']));
                if(!is_null($type) && $type->getId() != $id)
                    throw new ValidationException(sprintf("Name %s already exists.", $payload['name']));
            }

            return SummitMediaFileTypeFactory::populate($type, $payload);
        });
    }

    /**
     * @inheritDoc
     */
    public function delete(int $id): void
    {
        $this->tx_service->transaction(function() use($id){
            $type = $this->repository->getById($id);
            if(is_null($type))
                throw new EntityNotFoundException();
            if($type->IsSystemDefined())
                throw new ValidationException("You can not delete a system defined type.");

            $this->repository->delete($type);
        });
    }
}