<?php namespace App\Services\Model\Imp;
/**
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

use App\Models\Foundation\Main\OrderableChilds;
use App\Models\Foundation\Summit\Factories\SelectionPlanActionTypeFactory;
use App\Models\Foundation\Summit\Repositories\ISelectionPlanActionTypeRepository;
use App\Models\Foundation\Summit\SelectionPlan;
use App\Services\Model\AbstractService;
use App\Services\Model\ISummitSelectionPlanActionTypeService;
use libs\utils\ITransactionService;
use models\exceptions\EntityNotFoundException;
use models\summit\SelectionPlanActionType;
/**
 * Class SummitSelectionPlanActionTypeService
 * @package App\Services\Model\Imp
 */
final class SummitSelectionPlanActionTypeService
    extends AbstractService
    implements ISummitSelectionPlanActionTypeService
{

    use OrderableChilds;
    /**
     * @var ISelectionPlanActionTypeRepository
     */
    private $repository;

    public function __construct
    (
        ISelectionPlanActionTypeRepository $repository,
        ITransactionService $tx_service
    )
    {
        parent::__construct($tx_service);
        $this->repository = $repository;
    }

    /**
     * @inheritDoc
     */
    public function add(SelectionPlan $selection_plan, array $payload):SelectionPlanActionType
    {
        return $this->tx_service->transaction(function() use($selection_plan, $payload){
            $action = SelectionPlanActionTypeFactory::build($payload);
            $max_order = $selection_plan->getSelectionPlanActionTypeMaxOrder();
            $action->setOrder($max_order + 1);
            $selection_plan->addSelectionPlanActionType($action);
            return $action;
        });
    }

    /**
     * @inheritDoc
     */
    public function update(SelectionPlan $selection_plan, int $action_type_id, array $payload):?SelectionPlanActionType
    {
        return $this->tx_service->transaction(function() use($selection_plan, $action_type_id, $payload){

            $action = $selection_plan->getSelectionPlanActionTypeById($action_type_id);
            if(is_null($action)){
                throw new EntityNotFoundException(sprintf("PresentationActionType %s not found.", $action_type_id));
            }
            $action = SelectionPlanActionTypeFactory::populate($action, $payload);
            if (isset($payload['order']) && intval($payload['order']) != $action->getOrder()) {
                // request to update order
                self::recalculateOrderForCollection($selection_plan->getSelectionPlanActionTypes()->toArray(), $action, intval($payload['order']));
            }
            return $action;
        });
    }

    /**
     * @inheritDoc
     */
    public function delete(SelectionPlan $selection_plan, int $action_type_id):void
    {
        $this->tx_service->transaction(function() use($selection_plan, $action_type_id){
            $action = $selection_plan->getSelectionPlanActionTypeById($action_type_id);
            if(is_null($action)){
                throw new EntityNotFoundException(sprintf("SelectionPlanActionType %s not found.", $action_type_id));
            }

            $selection_plan->removeSelectionPlanActionType($action);
        });
    }
}