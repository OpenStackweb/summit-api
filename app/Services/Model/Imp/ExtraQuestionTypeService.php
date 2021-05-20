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

use App\Models\Foundation\ExtraQuestions\ExtraQuestionType;
use App\Models\Foundation\ExtraQuestions\ExtraQuestionTypeValue;
use App\Models\Foundation\ExtraQuestions\IExtraQuestionTypeRepository;
use App\Models\Foundation\Factories\ExtraQuestionTypeValueFactory;
use App\Services\Model\AbstractService;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;

/**
 * Class ExtraQuestionTypeService
 * @package App\Services\Model\Imp
 */
abstract class ExtraQuestionTypeService
    extends AbstractService
{
    /**
     * @var IExtraQuestionTypeRepository
     */
    protected $repository;

    /**
     * @param ExtraQuestionType $question
     * @param array $payload
     * @return ExtraQuestionTypeValue
     * @throws \Exception
     */
    protected function _addOrderExtraQuestionValue(ExtraQuestionType $question, array $payload): ExtraQuestionTypeValue
    {
        return $this->tx_service->transaction(function () use ($question, $payload) {

            $name   = trim($payload['value']);
            $former_value = $question->getValueByName($name);
            if(!is_null($former_value))
                throw new ValidationException("Value already exists.");

            if(isset($payload['label'])) {
                $label = trim($payload['label']);
                $former_value = $question->getValueByLabel($label);
                if (!is_null($former_value))
                    throw new ValidationException("Value already exists.");
            }

            $value = ExtraQuestionTypeValueFactory::build($payload);

            $question->addValue($value);

            return $value;

        });
    }


    /**
     * @param ExtraQuestionType $question
     * @param int $value_id
     * @param array $payload
     * @return ExtraQuestionTypeValue
     * @throws \Exception
     */
    protected function _updateOrderExtraQuestionValue(ExtraQuestionType $question, int $value_id, array $payload): ExtraQuestionTypeValue
    {
        return $this->tx_service->transaction(function () use ($question, $value_id, $payload) {

            $value = $question->getValueById($value_id);
            if(is_null($value))
                throw new EntityNotFoundException("Value not found.");

            if(isset($payload['value'])) {
                $name = trim($payload['value']);
                $former_value = $question->getValueByName($name);
                if (!is_null($former_value) && $former_value->getId() != $value_id)
                    throw new ValidationException("value already exists.");
            }

            if(isset($payload['label'])) {
                $label = trim($payload['label']);
                $former_value = $question->getValueByLabel($label);
                if (!is_null($former_value) && $former_value->getId() != $value_id)
                    throw new ValidationException("value already exists.");
            }


            if (isset($payload['order']) && intval($payload['order']) != $value->getOrder()) {
                // request to update order
                $question->recalculateValueOrder($value,  intval($payload['order']) );
            }

            return ExtraQuestionTypeValueFactory::populate($value, $payload);
        });
    }


    /**
     * @param ExtraQuestionType $question
     * @param int $value_id
     * @throws \Exception
     */
    protected function _deleteOrderExtraQuestionValue(ExtraQuestionType $question, int $value_id): void
    {
        $this->tx_service->transaction(function () use ($question, $value_id) {

            $value = $question->getValueById($value_id);

            if(is_null($value))
                throw new EntityNotFoundException("value not found");

            // check if question has answers

            if($this->repository->hasAnswers($question)){
                throw new ValidationException(sprintf("You can not delete question value %s bc already has answers from attendees.", $value_id));
            }

            $question->removeValue($value);
        });
    }
}