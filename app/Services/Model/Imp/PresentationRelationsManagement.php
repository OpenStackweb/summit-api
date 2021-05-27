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
use App\Models\Foundation\Summit\ExtraQuestions\SummitSelectionPlanExtraQuestionType;
use Illuminate\Support\Facades\Log;
use models\exceptions\ValidationException;
use models\summit\Presentation;
use models\summit\PresentationExtraQuestionAnswer;

/**
 * Trait PresentationRelationsManagement
 * @package App\Services\Model\Imp
 */
trait PresentationRelationsManagement
{

    protected function savePresentationExtraQuestions(Presentation $presentation, array $payload):Presentation{
        $extra_questions = $payload['extra_questions'] ?? [];
        $selection_plan = $presentation->getSelectionPlan();
        if (count($extra_questions) && !is_null($selection_plan)) {
            // extra questions values
            $mandatory_questions = $selection_plan->getMandatoryExtraQuestions();
            if (count($extra_questions) < $mandatory_questions->count()) {
                throw new ValidationException
                (
                    sprintf
                    (
                        "You neglected to fill in all mandatory questions for the presentation %s (%s) .",
                        count($extra_questions),
                        $mandatory_questions->count()
                    )
                );
            }
            $questions = $selection_plan->getExtraQuestions();
            if ($questions->count() > 0) {
                $presentation->clearExtraQuestionAnswers();
                foreach ($questions as $question) {
                    if (!$question instanceof SummitSelectionPlanExtraQuestionType) continue;
                    foreach ($extra_questions as $question_answer) {
                        if (intval($question_answer['question_id']) == $question->getId()) {
                            $value = trim($question_answer['answer']);

                            if (empty($value) && $question->isMandatory())
                                throw new ValidationException(sprintf('Question "%s" is mandatory', $question->getLabel()));

                            if ($question->allowsValues() && !$question->allowValue($value)) {
                                Log::warning(sprintf("value %s is not allowed for question %s", $value, $question->getName()));
                                throw new ValidationException("The answer you provided is invalid");
                            }

                            $answer = new PresentationExtraQuestionAnswer();
                            $answer->setQuestion($question);
                            $answer->setValue($value);
                            $presentation->addExtraQuestionAnswer($answer);
                            break;
                        }
                    }
                }

            }
        }
        return $presentation;
    }
}