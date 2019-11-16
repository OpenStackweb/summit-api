<?php namespace App\Services\Model;
/**
 * Copyright 2018 OpenStack Foundation
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
use App\Models\Foundation\Summit\Factories\SummitSelectionPlanFactory;
use App\Models\Foundation\Summit\SelectionPlan;
use libs\utils\ITransactionService;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use models\summit\ISummitRepository;
use models\summit\Summit;
/**
 * Class SummitSelectionPlanService
 * @package App\Services\Model
 */
final class SummitSelectionPlanService
    extends AbstractService
    implements ISummitSelectionPlanService
{
    /**
     * @var ISummitRepository
     */
    private $summit_repository;

    public function __construct(ISummitRepository $summit_repository, ITransactionService $tx_service)
    {
        $this->summit_repository = $summit_repository;
        parent::__construct($tx_service);
    }

    /**
     * @param Summit $summit
     * @param array $payload
     * @return SelectionPlan
     * @throws ValidationException
     */
    public function addSelectionPlan(Summit $summit, array $payload)
    {
        return $this->tx_service->transaction(function() use($summit, $payload){

            $selection_plan = SummitSelectionPlanFactory::build($payload, $summit);

            $former_selection_plan = $summit->getSelectionPlanByName($selection_plan->getName());

            if(!is_null($former_selection_plan)){
                throw new ValidationException(trans(
                    'validation_errors.SummitSelectionPlanService.addSelectionPlan.alreadyExistName',
                    [
                        'summit_id' => $summit->getId()
                    ]
                ));
            }

            // validate selection plan
            $summit->checkSelectionPlanConflicts($selection_plan);
            foreach($this->summit_repository->getCurrentAndFutureSummits() as $cur_summit){
                $cur_summit->checkSelectionPlanConflicts($selection_plan);
            }

            $summit->addSelectionPlan($selection_plan);

            return $selection_plan;
        });
    }

    /**
     * @param Summit $summit
     * @param int $selection_plan_id
     * @param array $payload
     * @return SelectionPlan
     * @throws ValidationException
     * @throws EntityNotFoundException
     */
    public function updateSelectionPlan(Summit $summit, $selection_plan_id, array $payload)
    {
        return $this->tx_service->transaction(function() use($summit, $selection_plan_id, $payload){

            $selection_plan = $summit->getSelectionPlanById($selection_plan_id);
            if(is_null($selection_plan))
                throw new EntityNotFoundException(trans
                ('not_found_errors.SummitSelectionPlanService.updateSelectionPlan.SelectionPlanNotFound',
                    [
                        'selection_plan_id' => $selection_plan_id,
                        'summit_id' => $summit->getId()
                    ]
                ));

            if(isset($payload['name'])) {
                $former_selection_plan = $summit->getSelectionPlanByName($payload['name']);
                if (!is_null($former_selection_plan) && $former_selection_plan->getId() != $selection_plan_id) {
                    throw new ValidationException(trans(
                        'validation_errors.SummitSelectionPlanService.updateSelectionPlan.alreadyExistName',
                        [
                            'summit_id' => $summit->getId()
                        ]
                    ));
                }
            }

            SummitSelectionPlanFactory::populate($selection_plan, $payload, $summit);

            // validate selection plan
            $summit->checkSelectionPlanConflicts($selection_plan);
            foreach($this->summit_repository->getCurrentAndFutureSummits() as $cur_summit){
                $cur_summit->checkSelectionPlanConflicts($selection_plan);
            }
            return $selection_plan;
        });
    }

    /**
     * @param Summit $summit
     * @param int $selection_plan_id
     * @throws EntityNotFoundException
     * @return void
     */
    public function deleteSelectionPlan(Summit $summit, $selection_plan_id)
    {
        return $this->tx_service->transaction(function() use($summit, $selection_plan_id){

            $selection_plan = $summit->getSelectionPlanById($selection_plan_id);
            if(is_null($selection_plan))
                throw new EntityNotFoundException(trans
                ('not_found_errors.SummitSelectionPlanService.deleteSelectionPlan.SelectionPlanNotFound',
                    [
                        'selection_plan_id' => $selection_plan_id,
                        'summit_id' => $summit->getId()
                    ]
                ));

            $summit->removeSelectionSelectionPlan($selection_plan);
        });
    }

    /**
     * @param Summit $summit
     * @param int $selection_plan_id
     * @param int $track_group_id
     * @throws EntityNotFoundException
     * @throws ValidationException
     * @return void
     */
    public function addTrackGroupToSelectionPlan(Summit $summit, $selection_plan_id, $track_group_id)
    {
        return $this->tx_service->transaction(function() use($summit, $selection_plan_id, $track_group_id){

            $selection_plan = $summit->getSelectionPlanById($selection_plan_id);
            if(is_null($selection_plan))
                throw new EntityNotFoundException(trans
                ('not_found_errors.SummitSelectionPlanService.addTrackGroupToSelectionPlan.SelectionPlanNotFound',
                    [
                        'selection_plan_id' => $selection_plan_id,
                        'summit_id' => $summit->getId()
                    ]
                ));

            $track_group = $summit->getCategoryGroupById($track_group_id);
            if(is_null($track_group))
                throw new EntityNotFoundException(trans
                ('not_found_errors.SummitSelectionPlanService.addTrackGroupToSelectionPlan.TrackGroupNotFound',
                    [
                        'track_group_id' => $track_group_id,
                        'summit_id'      => $summit->getId()
                    ]
                ));
            $selection_plan->addTrackGroup($track_group);
        });
    }

    /**
     * @param Summit $summit
     * @param int $selection_plan_id
     * @param int $track_group_id
     * @throws EntityNotFoundException
     * @throws ValidationException
     * @return void
     */
    public function deleteTrackGroupToSelectionPlan(Summit $summit, $selection_plan_id, $track_group_id)
    {
        return $this->tx_service->transaction(function() use($summit, $selection_plan_id, $track_group_id){

            $selection_plan = $summit->getSelectionPlanById($selection_plan_id);
            if(is_null($selection_plan))
                throw new EntityNotFoundException(trans
                ('not_found_errors.SummitSelectionPlanService.deleteTrackGroupToSelectionPlan.SelectionPlanNotFound',
                    [
                        'selection_plan_id' => $selection_plan_id,
                        'summit_id' => $summit->getId()
                    ]
                ));

            $track_group = $summit->getCategoryGroupById($track_group_id);
            if(is_null($track_group))
                throw new EntityNotFoundException(trans
                ('not_found_errors.SummitSelectionPlanService.deleteTrackGroupToSelectionPlan.TrackGroupNotFound',
                    [
                        'track_group_id' => $track_group_id,
                        'summit_id'      => $summit->getId()
                    ]
                ));
            $selection_plan->removeTrackGroup($track_group);
        });
    }

    /**
     * @param string $status
     * @return SelectionPlan|null
     * @throws \Exception
     */
    public function getCurrentSelectionPlanByStatus($status)
    {
        return $this->tx_service->transaction(function() use($status) {
            // first get current summit plus future summits
            $summits = $this->summit_repository->getCurrentAndFutureSummits();

            foreach($summits as $summit){
                $selection_plan = $summit->getCurrentSelectionPlanByStatus($status);
                if(is_null($selection_plan)) continue;
                if(!$selection_plan->IsEnabled()) continue;
                return $selection_plan;
            }

            return null;
        });
    }
}