<?php namespace App\Services\Model\Imp;
/*
 * Copyright 2026 OpenStack Foundation
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

use App\Models\Foundation\Summit\Factories\SummitSponsorshipAddOnTypeFactory;
use App\Models\Foundation\Summit\Repositories\ISummitSponsorshipAddOnRepository;
use App\Models\Foundation\Summit\Repositories\ISummitSponsorshipAddOnTypeRepository;
use App\Services\Model\AbstractService;
use App\Services\Model\ISummitSponsorshipAddOnTypeService;
use libs\utils\ITransactionService;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use models\summit\SummitSponsorshipAddOnType;

/**
 * Class SummitSponsorshipAddOnTypeService
 * @package App\Services\Model\Imp
 */
final class SummitSponsorshipAddOnTypeService
    extends AbstractService
    implements ISummitSponsorshipAddOnTypeService
{
    /**
     * @var ISummitSponsorshipAddOnTypeRepository
     */
    private $repository;

    /**
     * @var ISummitSponsorshipAddOnRepository
     */
    private $add_on_repository;

    public function __construct(
        ISummitSponsorshipAddOnTypeRepository $repository,
        ISummitSponsorshipAddOnRepository     $add_on_repository,
        ITransactionService                   $tx_service
    )
    {
        parent::__construct($tx_service);
        $this->repository        = $repository;
        $this->add_on_repository = $add_on_repository;
    }

    /**
     * @inheritDoc
     */
    public function add(array $payload): SummitSponsorshipAddOnType
    {
        return $this->tx_service->transaction(function () use ($payload) {
            if (isset($payload['name'])) {
                $existing = $this->repository->getByName(trim($payload['name']));
                if (!is_null($existing))
                    throw new ValidationException(sprintf("AddOnType with name '%s' already exists.", $payload['name']));
            }

            $type = SummitSponsorshipAddOnTypeFactory::build($payload);
            $this->repository->add($type);
            return $type;
        });
    }

    /**
     * @inheritDoc
     */
    public function update(int $type_id, array $payload): SummitSponsorshipAddOnType
    {
        return $this->tx_service->transaction(function () use ($type_id, $payload) {
            $type = $this->repository->getById($type_id);
            if (!$type instanceof SummitSponsorshipAddOnType)
                throw new EntityNotFoundException("AddOnType not found.");
            if($type->isSystemDefined())
                throw new ValidationException("System Defined Add On Type can not be updated.");
            if (isset($payload['name'])) {
                $existing = $this->repository->getByName(trim($payload['name']));
                if (!is_null($existing) && $existing->getId() !== $type->getId())
                    throw new ValidationException(sprintf("AddOnType with name '%s' already exists.", $payload['name']));
            }

            return SummitSponsorshipAddOnTypeFactory::populate($type, $payload);
        });
    }

    /**
     * @inheritDoc
     */
    public function delete(int $type_id): void
    {
        $this->tx_service->transaction(function () use ($type_id) {
            $type = $this->repository->getById($type_id);
            if (!$type instanceof SummitSponsorshipAddOnType)
                throw new EntityNotFoundException("AddOnType not found.");

            if($type->isSystemDefined())
                throw new ValidationException("System Defined Add On Type can not be deleted.");

            if ($this->add_on_repository->countByAddOnType($type_id) > 0)
                throw new ValidationException(sprintf("AddOnType '%s' is assigned to one or more add-ons and cannot be deleted.", $type->getName()));

            $this->repository->delete($type);
        });
    }
}
