<?php namespace App\Services;
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
use App\Models\Foundation\ExtraQuestions\ExtraQuestionTypeValue;
use App\Models\Foundation\Summit\Factories\SummitOrderExtraQuestionTypeFactory;
use App\Models\Foundation\Summit\Repositories\ISummitOrderExtraQuestionTypeRepository;
use App\Services\Model\Imp\ExtraQuestionTypeService;
use App\Services\Model\ISummitOrderExtraQuestionTypeService;
use libs\utils\ITransactionService;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use models\summit\Summit;
use models\summit\SummitOrderExtraQuestionType;
/**
 * Class SummitOrderExtraQuestionTypeService
 * @package App\Services
 */
final class SummitOrderExtraQuestionTypeService
    extends ExtraQuestionTypeService
    implements ISummitOrderExtraQuestionTypeService
{

    /**
     * SummitOrderExtraQuestionTypeService constructor.
     * @param ISummitOrderExtraQuestionTypeRepository $repository
     * @param ITransactionService $tx_service
     */
    public function __construct
    (
        ISummitOrderExtraQuestionTypeRepository $repository,
        ITransactionService $tx_service
    )
    {
        parent::__construct($tx_service);
        $this->repository = $repository;
    }

    /**
     * @param Summit $summit
     * @param array $payload
     * @return SummitOrderExtraQuestionType
     * @throws ValidationException
     * @throws EntityNotFoundException
     */
    public function addOrderExtraQuestion(Summit $summit, array $payload): SummitOrderExtraQuestionType
    {
        return $this->tx_service->transaction(function () use ($summit, $payload) {
            $name = trim($payload['name']);
            $former_question = $summit->getOrderExtraQuestionByName($name);
            if (!is_null($former_question))
                throw new ValidationException("Question Name already exists for Summit.");

            $label = trim($payload['label']);
            $former_question = $summit->getOrderExtraQuestionByLabel($label);
            if (!is_null($former_question))
                throw new ValidationException("Question Label already exists for Summit.");

            $question = SummitOrderExtraQuestionTypeFactory::build($payload);

            $summit->addOrderExtraQuestion($question);

            return $question;
        });
    }

    /**
     * @param Summit $summit
     * @param int $question_id
     * @param array $payload
     * @return SummitOrderExtraQuestionType
     * @throws ValidationException
     * @throws EntityNotFoundException
     */
    public function updateOrderExtraQuestion(Summit $summit, int $question_id, array $payload): SummitOrderExtraQuestionType
    {
        return $this->tx_service->transaction(function () use ($summit, $question_id, $payload) {

            $question = $summit->getOrderExtraQuestionById($question_id);
            if (is_null($question))
                throw new EntityNotFoundException("Question not Found.");

            if (isset($payload['name'])) {
                $name = trim($payload['name']);
                $former_question = $summit->getOrderExtraQuestionByName($name);
                if (!is_null($former_question) && $former_question->getId() != $question_id)
                    throw new ValidationException("Question Name already exists for Summit.");
            }

            if (isset($payload['label'])) {
                $label = trim($payload['label']);
                $former_question = $summit->getOrderExtraQuestionByLabel($label);
                if (!is_null($former_question) && $former_question->getId() != $question_id)
                    throw new ValidationException("Question Label already exists for Summit.");
            }

            if (isset($payload['order']) && intval($payload['order']) != $question->getOrder()) {
                // request to update order
                $summit->recalculateQuestionOrder($question, intval($payload['order']));
            }

            return SummitOrderExtraQuestionTypeFactory::populate($question, $payload);
        });
    }

    /**
     * @param Summit $summit
     * @param int $question_id
     * @throws ValidationException
     * @throws EntityNotFoundException
     */
    public function deleteOrderExtraQuestion(Summit $summit, int $question_id): void
    {
        $this->tx_service->transaction(function () use ($summit, $question_id) {

            $question = $summit->getOrderExtraQuestionById($question_id);
            if (is_null($question))
                throw new EntityNotFoundException("Question not found.");

            // check if question has answers

            if ($this->repository->hasAnswers($question)) {
                //throw new ValidationException(sprintf("you can not delete question %s bc already has answers from attendees", $question_id));
                $this->repository->deleteAnswersFrom($question);
            }

            $summit->removeOrderExtraQuestion($question);
        });
    }

    /**
     * @param Summit $summit
     * @param int $question_id
     * @param array $payload
     * @return ExtraQuestionTypeValue
     * @throws ValidationException
     * @throws EntityNotFoundException
     */
    public function addOrderExtraQuestionValue(Summit $summit, int $question_id, array $payload): ExtraQuestionTypeValue
    {
        return $this->tx_service->transaction(function () use ($summit, $question_id, $payload) {

            $question = $summit->getOrderExtraQuestionById($question_id);
            if (is_null($question))
                throw new EntityNotFoundException("Question not found.");

            return parent::_addOrderExtraQuestionValue($question, $payload);
        });
    }

    /**
     * @param Summit $summit
     * @param int $question_id
     * @param int $value_id
     * @param array $payload
     * @return ExtraQuestionTypeValue
     * @throws ValidationException
     * @throws EntityNotFoundException
     */
    public function updateOrderExtraQuestionValue(Summit $summit, int $question_id, int $value_id, array $payload): ExtraQuestionTypeValue
    {
        return $this->tx_service->transaction(function () use ($summit, $question_id, $value_id, $payload) {
            $question = $summit->getOrderExtraQuestionById($question_id);
            if (is_null($question))
                throw new EntityNotFoundException("Question not found.");

            return parent::_updateOrderExtraQuestionValue($question, $value_id, $payload);
        });
    }

    /**
     * @param Summit $summit
     * @param int $question_id
     * @param int $value_id
     * @throws ValidationException
     * @throws EntityNotFoundException
     */
    public function deleteOrderExtraQuestionValue(Summit $summit, int $question_id, int $value_id): void
    {
        $this->tx_service->transaction(function () use ($summit, $question_id, $value_id) {
            $question = $summit->getOrderExtraQuestionById($question_id);
            if (is_null($question))
                throw new EntityNotFoundException("Question not found.");

            parent::_deleteOrderExtraQuestionValue($question, $value_id);
        });
    }
}