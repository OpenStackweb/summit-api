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

use App\Models\Foundation\ExtraQuestions\ExtraQuestionTypeValue;
use App\Models\Foundation\Summit\ExtraQuestions\AssignedSelectionPlanExtraQuestionType;
use App\Models\Foundation\Summit\ExtraQuestions\SummitSelectionPlanExtraQuestionType;
use App\Models\Foundation\Summit\Factories\SummitSelectionPlanExtraQuestionTypeFactory;
use App\Models\Foundation\Summit\Repositories\ISelectionPlanRepository;
use App\Models\Foundation\Summit\Repositories\ISummitSelectionPlanExtraQuestionTypeRepository;
use App\Models\Foundation\Summit\SelectionPlan;
use App\Services\Model\ISelectionPlanExtraQuestionTypeService;
use Illuminate\Support\Facades\Log;
use libs\utils\ITransactionService;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use models\summit\Summit;
use models\summit\SummitOrderExtraQuestionType;

/**
 * Class SelectionPlanOrderExtraQuestionTypeService
 * @package App\Services\Model\Imp
 */
final class SelectionPlanOrderExtraQuestionTypeService
    extends ExtraQuestionTypeService
    implements ISelectionPlanExtraQuestionTypeService
{

    /**
     * @var ISelectionPlanRepository
     */
    private $selection_plan_repository;

    /**
     * @param ISummitSelectionPlanExtraQuestionTypeRepository $repository
     * @param ISelectionPlanRepository $selection_plan_repository
     * @param ITransactionService $tx_service
     */
    public function __construct
    (
        ISummitSelectionPlanExtraQuestionTypeRepository $repository,
        ISelectionPlanRepository $selection_plan_repository,
        ITransactionService $tx_service
    )
    {
        parent::__construct($tx_service);
        $this->selection_plan_repository = $selection_plan_repository;
        $this->repository = $repository;
    }

    /**
     * @param Summit $summit
     * @param array $payload
     * @return SummitSelectionPlanExtraQuestionType
     * @throws \Exception
     */
    public function addExtraQuestion(Summit $summit, array $payload): SummitSelectionPlanExtraQuestionType
    {
        return $this->tx_service->transaction(function () use ($summit, $payload) {

            $name = trim($payload['name']);
            $former_question = $summit->getSelectionPlanExtraQuestionByName($name);
            if (!is_null($former_question))
                throw new ValidationException("Question Name already exists for Selection Plans.");

            $label = trim($payload['label']);
            $former_question = $summit->getSelectionPlanExtraQuestionByLabel($label);
            if (!is_null($former_question))
                throw new ValidationException("Question Label already exists for Selection Plans.");

            $question = SummitSelectionPlanExtraQuestionTypeFactory::build($payload);

            $summit->addSelectionPlanExtraQuestion($question);

            return $question;
        });
    }

    public function updateExtraQuestion(Summit $summit,int $question_id, array $payload):SummitSelectionPlanExtraQuestionType{
        return $this->tx_service->transaction(function () use ($summit, $question_id, $payload) {

            $question = $summit->getSelectionPlanExtraQuestionById($question_id);
            if (is_null($question))
                throw new EntityNotFoundException("Question not found.");

            if (isset($payload['name'])) {
                $name = trim($payload['name']);
                $former_question = $summit->getSelectionPlanExtraQuestionByName($name);
                if (!is_null($former_question) && $former_question->getId() != $question_id)
                    throw new ValidationException("Question Name already exists for Selection Plan.");
            }

            if (isset($payload['label'])) {
                $label = trim($payload['label']);
                $former_question = $summit->getSelectionPlanExtraQuestionByLabel($label);
                if (!is_null($former_question) && $former_question->getId() != $question_id)
                    throw new ValidationException("Question Label already exists for Selection Plan.");
            }

            return SummitSelectionPlanExtraQuestionTypeFactory::populate($question, $payload);
        });
    }

    /**
     * @inheritDoc
     */
    public function updateExtraQuestionBySelectionPlan(SelectionPlan $selection_plan, int $question_id, array $payload): AssignedSelectionPlanExtraQuestionType
    {
        return $this->tx_service->transaction(function () use ($selection_plan, $question_id, $payload) {

            Log::debug
            (
                sprintf
                (
                    "SelectionPlanOrderExtraQuestionTypeService::updateExtraQuestionBySelectionPlan selection_plan %s question id %s payload %s",
                    $selection_plan->getId(),
                    $question_id,
                    json_encode($payload)
                ));

            $summit = $selection_plan->getSummit();

            $question = $this->updateExtraQuestion($summit, $question_id, $payload);
            $assignment = $selection_plan->getAssignedExtraQuestion($question);
            if(is_null($assignment))
                throw new EntityNotFoundException("Question does not belongs to selection plan.");

            if (isset($payload['order']) && intval($payload['order']) != $assignment->getOrder()) {
                // request to update order
                Log::debug(sprintf("SelectionPlanOrderExtraQuestionTypeService::updateExtraQuestionBySelectionPlan recalculating order"));
                $selection_plan->recalculateQuestionOrder($question, intval($payload['order']));
            }

            if(isset($payload['is_editable'])){
                $assignment->setIsEditable(boolval($payload['is_editable']));
            }

            return $assignment;
        });
    }

    /**
     * @inheritDoc
     */
    public function addExtraQuestionValue(Summit $summit, int $question_id, array $payload): ExtraQuestionTypeValue
    {
        return $this->tx_service->transaction(function () use ($summit, $question_id, $payload) {

            $question = $summit->getSelectionPlanExtraQuestionById($question_id);
            if (is_null($question))
                throw new EntityNotFoundException("Question not found.");

            return parent::_addExtraQuestionValue($question, $payload);
        });
    }

    /**
     * @param int $selection_plan_id
     * @param array $payload
     * @return SummitOrderExtraQuestionType
     * @throws ValidationException
     * @throws EntityNotFoundException
     */
    public function addExtraQuestionAndAssignTo(int $selection_plan_id, array $payload): AssignedSelectionPlanExtraQuestionType
    {
        return $this->tx_service->transaction(function () use ($selection_plan_id, $payload) {

            $selection_plan = $this->selection_plan_repository->getById($selection_plan_id);
            if(is_null($selection_plan))
                throw new EntityNotFoundException("Selection Plan not found.");

            $name = trim($payload['name']);
            $summit = $selection_plan->getSummit();
            $former_question = $summit->getSelectionPlanExtraQuestionByName($name);
            if (!is_null($former_question))
                throw new ValidationException("Question Name already exists for Selection Plans.");

            $label = trim($payload['label']);
            $former_question = $summit->getSelectionPlanExtraQuestionByLabel($label);
            if (!is_null($former_question))
                throw new ValidationException("Question Label already exists for Selection Plans.");

            $question = SummitSelectionPlanExtraQuestionTypeFactory::build($payload);

            $summit->addSelectionPlanExtraQuestion($question);

            $assignment = $selection_plan->addExtraQuestion($question);

            if(is_null($assignment))
                throw new ValidationException("Question is already assigned to selection plan.");

            if(isset($payload['is_editable'])){
                $assignment->setIsEditable(boolval($payload['is_editable']));
            }

            return $assignment;
        });
    }

    /**
     * @inheritDoc
     */
    public function updateExtraQuestionValue(Summit $summit, int $question_id, int $value_id, array $payload): ExtraQuestionTypeValue
    {
        return $this->tx_service->transaction(function () use ($summit, $question_id, $value_id, $payload) {
            $question = $summit->getSelectionPlanExtraQuestionById($question_id);
            if (is_null($question))
                throw new EntityNotFoundException("Question not found.");

            return parent::_updateExtraQuestionValue($question, $value_id, $payload);
        });
    }

    /**
     * @inheritDoc
     */
    public function deleteExtraQuestionValue(Summit $summit, int $question_id, int $value_id): void
    {
        $this->tx_service->transaction(function () use ($summit, $question_id, $value_id) {
            $question = $summit->getSelectionPlanExtraQuestionById($question_id);
            if (is_null($question))
                throw new EntityNotFoundException("Question not found.");

            parent::_deleteExtraQuestionValue($question, $value_id);
        });
    }

    /**
     * @param int $selection_plan_id
     * @param int $question_id
     * @return AssignedSelectionPlanExtraQuestionType
     * @throws ValidationException
     * @throws EntityNotFoundException
     */
    public function assignExtraQuestion(int $selection_plan_id, int $question_id): AssignedSelectionPlanExtraQuestionType
    {
        return $this->tx_service->transaction(function () use ($selection_plan_id, $question_id) {
            $selection_plan = $this->selection_plan_repository->getById($selection_plan_id);
            if(is_null($selection_plan))
                throw new EntityNotFoundException("Selection Plan not found.");
            $summit = $selection_plan->getSummit();
            $question = $summit->getSelectionPlanExtraQuestionById($question_id);
            if (is_null($question))
                throw new EntityNotFoundException("Question not found.");

            $assignment = $selection_plan->addExtraQuestion($question);

            if(is_null($assignment))
                throw new ValidationException("Question is already assigned to selection plan.");

            return $assignment;
        });
    }

    /**
     * @param int $selection_plan_id
     * @param int $question_id
     * @throws ValidationException
     * @throws EntityNotFoundException
     */
    public function removeExtraQuestion(int $selection_plan_id, int $question_id): void
    {
       $this->tx_service->transaction(function () use ($selection_plan_id, $question_id) {

            Log::debug
            (
                sprintf
                (
                    "SelectionPlanOrderExtraQuestionTypeService::removeExtraQuestion selection_plan_id %s question_id %s",
                    $selection_plan_id,
                    $question_id
                )
            );

            $selection_plan = $this->selection_plan_repository->getById($selection_plan_id);
            if(is_null($selection_plan))
                throw new EntityNotFoundException("Selection Plan not found.");

            $question = $selection_plan->getExtraQuestionById($question_id);
            if (!$question instanceof SummitSelectionPlanExtraQuestionType)
                throw new EntityNotFoundException("Question not found.");

            $selection_plan->removeExtraQuestion($question);

        });

        $this->tx_service->transaction(function () use ($selection_plan_id, $question_id) {

            $selection_plan = $this->selection_plan_repository->getById($selection_plan_id);
            if(is_null($selection_plan))
                throw new EntityNotFoundException("Selection Plan not found.");

            $summit = $selection_plan->getSummit();
            if(is_null($summit))
                throw new EntityNotFoundException("Summit not found.");

            $question = $summit->getSelectionPlanExtraQuestionById($question_id);
            if (!$question instanceof SummitSelectionPlanExtraQuestionType)
                throw new EntityNotFoundException("Question not found.");

            if (!$question->hasAssignedPlans()) {
                // remove question from summit
                Log::debug
                (
                    sprintf
                    (
                        "SelectionPlanOrderExtraQuestionTypeService::removeExtraQuestion removing question %s from summit",
                        $question->getId()
                    )
                );

                $summit->removeSelectionPlanExtraQuestion($question);
            }
        });
    }

    /**
     * @inheritDoc
     */
    public function deleteExtraQuestion(Summit $summit, int $question_id): void
    {
        $this->tx_service->transaction(function () use ($summit, $question_id) {

            $question = $summit->getSelectionPlanExtraQuestionById($question_id);
            if (is_null($question))
                throw new EntityNotFoundException("Question not found.");

            $summit->removeSelectionPlanExtraQuestion($question);
        });
    }

}