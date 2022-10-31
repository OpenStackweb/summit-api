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
use App\Models\Foundation\Summit\ExtraQuestions\SummitSelectionPlanExtraQuestionType;
use App\Models\Foundation\Summit\Factories\SummitSelectionPlanExtraQuestionTypeFactory;
use App\Models\Foundation\Summit\Repositories\ISummitSelectionPlanExtraQuestionTypeRepository;
use App\Models\Foundation\Summit\SelectionPlan;
use App\Services\Model\ISelectionPlanExtraQuestionTypeService;
use libs\utils\ITransactionService;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use models\summit\Summit;

/**
 * Class SelectionPlanOrderExtraQuestionTypeService
 * @package App\Services\Model\Imp
 */
final class SelectionPlanOrderExtraQuestionTypeService
    extends ExtraQuestionTypeService
    implements ISelectionPlanExtraQuestionTypeService
{

    /**
     * SelectionPlanOrderExtraQuestionTypeService constructor.
     * @param ISummitSelectionPlanExtraQuestionTypeRepository $repository
     * @param ITransactionService $tx_service
     */
    public function __construct
    (
        ISummitSelectionPlanExtraQuestionTypeRepository $repository,
        ITransactionService $tx_service
    )
    {
        parent::__construct($tx_service);
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
                throw new ValidationException("Question Name already exists for Selection Plan.");

            $label = trim($payload['label']);
            $former_question = $summit->getSelectionPlanExtraQuestionByName($label);
            if (!is_null($former_question))
                throw new ValidationException("Question Label already exists for Selection Plan.");

            $question = SummitSelectionPlanExtraQuestionTypeFactory::build($payload);

            $summit->addSelectionPlanExtraQuestion($question);

            return $question;
        });
    }

    /**
     * @inheritDoc
     */
    public function updateExtraQuestion(Summit $summit, int $question_id, array $payload): SummitSelectionPlanExtraQuestionType
    {
        return $this->tx_service->transaction(function () use ($summit, $question_id, $payload) {

            $question = $summit->getExtraQuestionById($question_id);
            if (is_null($question))
                throw new EntityNotFoundException("question not found");

            if (isset($payload['name'])) {
                $name = trim($payload['name']);
                $former_question = $summit->getExtraQuestionByName($name);
                if (!is_null($former_question) && $former_question->getId() != $question_id)
                    throw new ValidationException("Question Name already exists for Selection Plan.");
            }

            if (isset($payload['label'])) {
                $label = trim($payload['label']);
                $former_question = $summit->getExtraQuestionByLabel($label);
                if (!is_null($former_question) && $former_question->getId() != $question_id)
                    throw new ValidationException("Question Label already exists for Selection Plan.");
            }

            if (isset($payload['order']) && intval($payload['order']) != $question->getOrder()) {
                // request to update order
                $selectionPlan->recalculateQuestionOrder($question, intval($payload['order']));
            }

            return SummitSelectionPlanExtraQuestionTypeFactory::populate($question, $payload);
        });
    }

    /**
     * @inheritDoc
     */
    public function deleteExtraQuestion(SelectionPlan $selectionPlan, int $question_id): void
    {
        $this->tx_service->transaction(function () use ($selectionPlan, $question_id) {

            $question = $selectionPlan->getExtraQuestionById($question_id);
            if (is_null($question))
                throw new EntityNotFoundException("Question not found.");

            // check if question has answers

            if ($this->repository->hasAnswers($question)) {
                //throw new ValidationException(sprintf("you can not delete question %s bc already has answers from attendees", $question_id));
                $this->repository->deleteAnswersFrom($question);
            }

            $selectionPlan->removeExtraQuestion($question);
        });
    }

    /**
     * @inheritDoc
     */
    public function addExtraQuestionValue(SelectionPlan $selectionPlan, int $question_id, array $payload): ExtraQuestionTypeValue
    {
        return $this->tx_service->transaction(function () use ($selectionPlan, $question_id, $payload) {

            $question = $selectionPlan->getExtraQuestionById($question_id);
            if (is_null($question))
                throw new EntityNotFoundException("Question not found.");

            return parent::_addOrderExtraQuestionValue($question, $payload);
        });
    }

    /**
     * @inheritDoc
     */
    public function updateExtraQuestionValue(SelectionPlan $selectionPlan, int $question_id, int $value_id, array $payload): ExtraQuestionTypeValue
    {
        return $this->tx_service->transaction(function () use ($selectionPlan, $question_id, $value_id, $payload) {
            $question = $selectionPlan->getExtraQuestionById($question_id);
            if (is_null($question))
                throw new EntityNotFoundException("Question not found.");

            return parent::_updateOrderExtraQuestionValue($question, $value_id, $payload);
        });
    }

    /**
     * @inheritDoc
     */
    public function deleteExtraQuestionValue(SelectionPlan $selectionPlan, int $question_id, int $value_id): void
    {
        $this->tx_service->transaction(function () use ($selectionPlan, $question_id, $value_id) {
            $question = $selectionPlan->getExtraQuestionById($question_id);
            if (is_null($question))
                throw new EntityNotFoundException("Question not found.");

            parent::_deleteOrderExtraQuestionValue($question, $value_id);
        });
    }
}