<?php namespace App\Repositories\Main;
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
use App\Models\Foundation\ExtraQuestions\IExtraQuestionTypeRepository;
use App\Repositories\SilverStripeDoctrineRepository;
use Illuminate\Support\Facades\Log;
use models\summit\SummitOrderExtraQuestionType;
use utils\DoctrineLeftJoinFilterMapping;

/**
 * Class DoctrineExtraQuestionTypeRepository
 * @package App\Repositories\Main
 */
abstract class DoctrineExtraQuestionTypeRepository
    extends SilverStripeDoctrineRepository
    implements IExtraQuestionTypeRepository
{
    /**
     * @return array
     */
    protected function getFilterMappings()
    {
        return [
            'name'      => 'e.name:json_string',
            'type'      => 'e.type:json_string',
            'label'     => 'e.label:json_string',
        ];
    }

    /**
     * @return array
     */
    protected function getOrderMappings()
    {
        return [
            'id'    => 'e.id',
            'name'  => 'e.name',
            'label' => 'e.label',
            'order' => 'e.order',
        ];
    }

    /**
     * @return array
     */
    public function getQuestionsMetadata()
    {
        return [
            [
                'type' => 'TextArea',
            ],
            [
                'type' => 'Text',
            ],
            [
                'type' => 'CheckBox',
            ],
            [
                'type'  => 'ComboBox',
                'values' => 'array',
            ],
            [
                'type'  => 'CheckBoxList',
                'values' => 'array',
            ],
            [
                'type' => 'RadioButtonList',
                'values'  => 'array',
            ],

        ];
    }

    /**
     * @param ExtraQuestionType $questionType
     * @return bool
     */
    public function hasAnswers(ExtraQuestionType $questionType): bool
    {
        try {
            $query = $this->getEntityManager()
                ->createQueryBuilder()
                ->select("count(e.id)")
                ->from($this->getBaseEntity(), "e")->join("e.question", "q")
                ->where("q = :question")->setParameter("question", $questionType);

            return $query->getQuery()->getSingleScalarResult() > 0;
        }
        catch (\Exception $ex){
            Log::error($ex);
            return false;
        }
    }

    /**
     * @param ExtraQuestionType $questionType
     */
    public function deleteAnswersFrom(ExtraQuestionType $questionType):void{
        try {
            $query = $this->getEntityManager()
                ->createQueryBuilder()
                ->delete($this->getBaseEntity(), "e")
                ->where("e.question = :question")
                ->setParameter("question", $questionType);

            $query->getQuery()->execute();
        }
        catch (\Exception $ex){
            Log::error($ex);
        }
    }
}