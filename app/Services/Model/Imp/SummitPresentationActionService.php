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

use App\Facades\ResourceServerContext;
use App\Services\Model\AbstractService;
use App\Services\Model\ISummitPresentationActionService;
use models\exceptions\EntityNotFoundException;
use models\main\Member;
use models\summit\PresentationAction;
use models\summit\Summit;
/**
 * Class SummitPresentationActionService
 * @package App\Services\Model\Imp
 */
final class SummitPresentationActionService
    extends AbstractService
implements ISummitPresentationActionService
{

    /**
     * @inheritDoc
     */
    public function updateAction(Summit $summit, int $selection_plan_id, int $presentation_id, int $action_id, bool $isCompleted): ?PresentationAction
    {
        return $this->tx_service->transaction(function() use($summit, $selection_plan_id, $presentation_id, $action_id, $isCompleted){

            $performer = ResourceServerContext::getCurrentUser(false);
            $selection_plan = $summit->getSelectionPlanById($selection_plan_id);
            if(is_null($selection_plan))
                throw new EntityNotFoundException(sprintf("Selection Plan %s not found.", $selection_plan_id));

            $presentation = $selection_plan->getPresentation($presentation_id);

            if(is_null($presentation))
                throw new EntityNotFoundException(sprintf("Presentation %s not found.", $presentation_id));

            $action = $presentation->getActionById($action_id);

            if(is_null($action))
                throw new EntityNotFoundException(sprintf("Action %s not found.", $action_id));

            $action->setUpdatedBy($performer);
            $action->setIsCompleted($isCompleted);
            $message = sprintf("%s has marked status '%s' as %s", $performer->getFullName(), $action->getType()->getLabel(), ($isCompleted? "Completed":"Uncompleted"));
            if(!$action->hasCreatedBy()){
                $action->setCreatedBy($performer);
            }

            // emit notification
            $presentation->addTrackChairComment($performer, $message, true);

            $presentation->setUpdatedBy($performer);

            return $action;
        });
    }
}