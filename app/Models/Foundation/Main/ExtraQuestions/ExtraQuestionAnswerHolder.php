<?php namespace App\Models\Foundation\Main\ExtraQuestions;
/*
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

use App\Models\Foundation\ExtraQuestions\ExtraQuestionAnswer;
use App\Models\Foundation\ExtraQuestions\ExtraQuestionType;
use Doctrine\Common\Collections\ArrayCollection;
use Illuminate\Support\Facades\Log;
use models\exceptions\ValidationException;
use models\summit\SummitOrderExtraQuestionAnswer;
/***
 * Trait ExtraQuestionAnswerHolder
 * @package App\Models\Foundation\Main\ExtraQuestions
 */
trait ExtraQuestionAnswerHolder
{
    /**
     * @return SummitOrderExtraQuestionAnswer[] | ArrayCollection
     */
    public abstract function getExtraQuestionAnswers();

    /**
     * @return ExtraQuestionType[] | ArrayCollection
     */
    public abstract function getExtraQuestions();

    /**
     * @param int $questionId
     * @return ExtraQuestionType|null
     */
    public abstract function getQuestionById(int $questionId):?ExtraQuestionType;

    /**
     * @return bool
     */
    public abstract function canChangeAnswerValue():bool;

    public abstract function clearExtraQuestionAnswers():void;

    /**
     * @param SummitOrderExtraQuestionAnswer $answer
     */
    public abstract function addExtraQuestionAnswer(SummitOrderExtraQuestionAnswer $answer):void;

    /**
     * @return ExtraQuestionAnswerSet
     */
    public function getExtraAnswerSnapshot():ExtraQuestionAnswerSet{
        return new ExtraQuestionAnswerSet($this->getExtraQuestionAnswers());
    }

    /**
     * @param ExtraQuestionAnswer|null $formerAnswer
     * @param ExtraQuestionAnswer|null $currentAnswer
     * @return bool
     */
    private function answerChanged(?ExtraQuestionAnswer $formerAnswer, ?ExtraQuestionAnswer $currentAnswer):bool{
        $formerAnswerValue = !is_null($formerAnswer) ? $formerAnswer->getValue() : "";
        $currentAnswerValue = !is_null($currentAnswer) ? $currentAnswer->getValue() : "";
        if(empty($formerAnswerValue)){
            // was not answered yet
            return false;
        }
        return $formerAnswerValue != $currentAnswerValue;
    }

    /**
     * @param ExtraQuestionType $q
     * @param ExtraQuestionAnswerSet $formerAnswers
     * @param ExtraQuestionAnswerSet $answers
     * @return bool
     * @throws ValidationException
     */
    private function checkQuestion(ExtraQuestionType $q, ExtraQuestionAnswerSet $formerAnswers, ExtraQuestionAnswerSet $answers):bool{
        Log::debug(sprintf("ExtraQuestionAnswerHolder::checkQuestion question %s former answers %s current answers %s", $q->getId(), json_encode($formerAnswers->serialize()), json_encode($answers->serialize())));
        $formerAnswer = $formerAnswers->getAnswerFor($q);
        $currentAnswer = $answers->getAnswerFor($q);
        // check if we are allowed to change the answers that we already did ( bypass only if we are admin)
        if(!$this->canChangeAnswerValue() && $this->answerChanged($formerAnswer, $currentAnswer)){
            throw new ValidationException
            (
                sprintf
                (
                    "Answer can not be changed by this time. Original answer is %s.",
                    $formerAnswer->getQuestion()->getNiceValue($formerAnswer->getValue())
                )
            );
        }
        $res = $q->isAnswered($answers);
        // check sub-questions ...
        foreach ($q->getSubQuestionRules() as $rule){
            $res &= $this->checkQuestion($rule->getSubQuestion(), $formerAnswers, $answers);
        }
        return $res;
    }

    /**
     * @param array|null $answers
     * @return bool
     * @throws ValidationException
     */
    public function hadCompletedExtraQuestions(?array $answers = null): bool
    {

        $res = true;
        $formerAnswers = $this->getExtraAnswerSnapshot();
        Log::debug(sprintf("ExtraQuestionAnswerHolder::hadCompletedExtraQuestions formerAnswers %s", json_encode($formerAnswers->serialize())));
        if (!is_null($answers)) { // if we provide new answers
            Log::debug(sprintf("ExtraQuestionAnswerHolder::hadCompletedExtraQuestions provided new answers %s", json_encode($answers)));

            $this->clearExtraQuestionAnswers();

            // create structure for current answers ...
            foreach ($answers as $answer) {
                $questionId = $answer['question_id'] ?? 0;
                $question = $this->getQuestionById(intval($questionId));
                if (is_null($question))
                    throw new ValidationException(sprintf("Question %s does not exists.", $questionId));
                $value = trim($answer['answer'] ?? '');
                $answer = new SummitOrderExtraQuestionAnswer();
                $answer->setQuestion($question);
                $answer->setValue($value);
                $this->addExtraQuestionAnswer($answer);
            }
        }

        $currentAnswers = $this->getExtraAnswerSnapshot();

        foreach($this->getExtraQuestions() as $q) {
            $res &= $this->checkQuestion($q, $formerAnswers, $currentAnswers);
        }

        $answersToDelete = $currentAnswers->getAnswersToDelete();
        if(count($answersToDelete) > 0){
            Log::debug(sprintf("ExtraQuestionAnswerHolder::hadCompletedExtraQuestions we have answers to delete."));
            foreach ($answersToDelete as $a) {
                Log::debug
                (
                    sprintf
                    (
                        "ExtraQuestionAnswerHolder::hadCompletedExtraQuestions deleting answer %s for question %s.",
                        $a->getValue(),
                        $a->getQuestionId()
                    )
                );

                $this->removeExtraQuestionAnswer($a);
            }
        }

        return $res;
    }
}