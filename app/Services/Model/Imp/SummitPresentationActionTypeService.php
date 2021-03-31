<?php namespace App\Services\Model\Imp;
/**
 * Copyright 2021 OpenStack Foundation
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

use App\Models\Foundation\Main\OrderableChilds;
use App\Models\Foundation\Summit\Factories\PresentationActionTypeFactory;
use App\Models\Foundation\Summit\Repositories\IPresentationActionTypeRepository;
use App\Services\Model\AbstractService;
use App\Services\Model\ISummitPresentationActionTypeService;
use libs\utils\ITransactionService;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use models\summit\PresentationActionType;
use models\summit\Summit;
/**
 * Class SummitPresentationActionTypeService
 * @package App\Services\Model\Imp
 */
final class SummitPresentationActionTypeService
    extends AbstractService
    implements ISummitPresentationActionTypeService
{

    use OrderableChilds;
    /**
     * @var IPresentationActionTypeRepository
     */
    private $repository;

    public function __construct
    (
        IPresentationActionTypeRepository $repository,
        ITransactionService $tx_service
    )
    {
        parent::__construct($tx_service);
        $this->repository = $repository;
    }

    /**
     * @inheritDoc
     */
    public function add(Summit $summit, array $payload): PresentationActionType
    {
        return $this->tx_service->transaction(function() use($summit, $payload){
            $action = PresentationActionTypeFactory::build($payload);
            $max_order = $summit->getPresentationActionTypeMaxOrder();
            $action->setOrder($max_order + 1);
            $summit->addPresentationActionType($action);
            return $action;
        });
    }

    /**
     * @inheritDoc
     */
    public function update(Summit $summit, int $action_type_id, array $payload): ?PresentationActionType
    {
        return $this->tx_service->transaction(function() use($summit, $action_type_id, $payload){

            $action = $summit->getPresentationActionTypeById($action_type_id);
            if(is_null($action)){
                throw new EntityNotFoundException(sprintf("PresentationActionType %s not found.", $action_type_id));
            }
            $action = PresentationActionTypeFactory::populate($action, $payload);
            if (isset($payload['order']) && intval($payload['order']) != $action->getOrder()) {
                // request to update order
                self::recalculateOrderForCollection($summit->getPresentationActionTypes()->toArray(), $action, intval($payload['order']));
            }
            return $action;
        });
    }

    /**
     * @inheritDoc
     */
    public function delete(Summit $summit, int $action_type_id): void
    {
        $this->tx_service->transaction(function() use($summit, $action_type_id){
            $action = $summit->getPresentationActionTypeById($action_type_id);
            if(is_null($action)){
                throw new EntityNotFoundException(sprintf("PresentationActionType %s not found.", $action_type_id));
            }

            $summit->removePresentationActionType($action);
        });
    }
}