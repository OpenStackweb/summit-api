<?php namespace App\Repositories\Summit;
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
use App\Models\Foundation\Summit\Repositories\ISummitOrderExtraQuestionTypeRepository;
use App\Repositories\SilverStripeDoctrineRepository;
use Illuminate\Support\Facades\Log;
use models\summit\Summit;
use models\summit\SummitOrder;
use models\summit\SummitOrderExtraQuestionAnswer;
use models\summit\SummitOrderExtraQuestionType;
use utils\DoctrineLeftJoinFilterMapping;
/**
 * Class DoctrineSummitOrderExtraQuestionTypeRepository
 * @package App\Repositories\Summit
 */
final class DoctrineSummitOrderExtraQuestionTypeRepository
    extends SilverStripeDoctrineRepository
    implements ISummitOrderExtraQuestionTypeRepository
{

    /**
     * @return array
     */
    protected function getFilterMappings()
    {
        return [
            'name'      => 'e.name:json_string',
            'type'      => 'e.type:json_string',
            'usage;'    => 'e.$usage;:json_string',
            'label'     => 'e.label:json_string',
            'summit_id' => new DoctrineLeftJoinFilterMapping("e.summit", "s" ,"s.id :operator :value")
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
     *
     * @return string
     */
    protected function getBaseEntity()
    {
        return SummitOrderExtraQuestionType::class;
    }

    /**
     * @param Summit $summit
     * @return array
     */
    public function getQuestionsMetadata(Summit $summit)
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
     * @param SummitOrderExtraQuestionType $questionType
     * @return bool
     */
    public function hasAnswers(SummitOrderExtraQuestionType $questionType): bool
    {
        try {
            $query = $this->getEntityManager()
                ->createQueryBuilder()
                ->select("count(e.id)")
                ->from(SummitOrderExtraQuestionAnswer::class, "e")->join("e.question", "q")
                ->where("q = :question")->setParameter("question", $questionType);

            return $query->getQuery()->getSingleScalarResult() > 0;
        }
        catch (\Exception $ex){
            Log::error($ex);
            return false;
        }
    }

    /**
     * @param SummitOrderExtraQuestionType $questionType
     */
    public function deleteAnswersFrom(SummitOrderExtraQuestionType $questionType):void{
        try {
            $query = $this->getEntityManager()
                ->createQueryBuilder()
                ->delete(SummitOrderExtraQuestionAnswer::class, "e")
                ->where("e.question = :question")
                ->setParameter("question", $questionType);

            $query->getQuery()->execute();
        }
        catch (\Exception $ex){
            Log::error($ex);
        }
    }
}