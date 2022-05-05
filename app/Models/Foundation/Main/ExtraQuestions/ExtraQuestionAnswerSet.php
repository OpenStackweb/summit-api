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

/**
 * Class ExtraQuestionAnswerSet
 * @package App\Models\Foundation\Main\ExtraQuestions
 */
final class ExtraQuestionAnswerSet
{
    /**
     * @var array;
     */
    private $snapshot = [];

    /**
     * @param ArrayCollection|array $collection
     */
    public function __construct($collection){
        foreach ($collection as $answer){
            $this->snapshot[$answer->getQuestion()->getId()] = $answer;
        }
    }

    /**
     * @param ExtraQuestionType $question
     * @return ExtraQuestionAnswer|null
     */
    public function getAnswerFor(ExtraQuestionType $question):?ExtraQuestionAnswer {
        return $this->snapshot[$question->getId()] ?? null;
    }

    public function serialize():array {
        $res =[];
        foreach ($this->snapshot as $question_id => $answer){
            $res[] = [
                'question_id' => intval($question_id),
                'answer' => $answer->getValue(),
            ];
        }
        return $res;
    }
}