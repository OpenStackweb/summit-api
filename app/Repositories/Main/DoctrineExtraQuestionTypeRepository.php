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

use App\Models\Foundation\ExtraQuestions\ExtraQuestionAnswer;
use App\Models\Foundation\ExtraQuestions\ExtraQuestionType;
use App\Models\Foundation\ExtraQuestions\ExtraQuestionTypeConstants;
use App\Models\Foundation\ExtraQuestions\IExtraQuestionTypeRepository;
use App\Repositories\SilverStripeDoctrineRepository;
use Doctrine\ORM\QueryBuilder;
use Illuminate\Support\Facades\Log;
use utils\DoctrineCaseFilterMapping;
use utils\DoctrineSwitchFilterMapping;
use utils\Filter;

/**
 * Class DoctrineExtraQuestionTypeRepository
 * @package App\Repositories\Main
 */
abstract class DoctrineExtraQuestionTypeRepository extends SilverStripeDoctrineRepository implements
  IExtraQuestionTypeRepository {
  /**
   * @param QueryBuilder $query
   * @return QueryBuilder
   */
  protected function applyExtraJoins(QueryBuilder $query, ?Filter $filter = null) {
    return $query;
  }

  /**
   * @return array
   */
  protected function getFilterMappings() {
    return [
      "name" => "e.name:json_string",
      "type" => "e.type:json_string",
      "label" => "e.label:json_string",
      "class" => new DoctrineSwitchFilterMapping([
        ExtraQuestionTypeConstants::QuestionClassSubQuestion => new DoctrineCaseFilterMapping(
          ExtraQuestionTypeConstants::QuestionClassSubQuestion,
          "exists (select pr from App\Models\Foundation\Main\ExtraQuestions\SubQuestionRule pr inner join pr.sub_question sq where sq.id = e.id)",
        ),
        ExtraQuestionTypeConstants::QuestionClassMain => new DoctrineCaseFilterMapping(
          ExtraQuestionTypeConstants::QuestionClassMain,
          "not exists (select pr from App\Models\Foundation\Main\ExtraQuestions\SubQuestionRule pr inner join pr.sub_question sq where sq.id = e.id)",
        ),
      ]),
    ];
  }

  /**
   * @return array
   */
  protected function getOrderMappings() {
    return [
      "id" => "e.id",
      "name" => "e.name",
      "label" => "e.label",
      "order" => "e.order",
    ];
  }

  /**
   * @return array
   */
  public function getQuestionsMetadata() {
    $metadata = [];
    foreach (ExtraQuestionTypeConstants::ValidQuestionTypes as $type) {
      $metadata[] = in_array($type, ExtraQuestionTypeConstants::AllowedMultiValueQuestionType)
        ? [
          "type" => $type,
          "values" => "array",
        ]
        : [
          "type" => $type,
        ];
    }
    return $metadata;
  }

  /**
   * @param ExtraQuestionType $questionType
   * @return bool
   */
  public function hasAnswers(ExtraQuestionType $questionType): bool {
    try {
      $query = $this->getEntityManager()
        ->createQueryBuilder()
        ->select("count(e.id)")
        ->from(ExtraQuestionAnswer::class, "e")
        ->join("e.question", "q")
        ->where("q = :question")
        ->setParameter("question", $questionType);

      return $query->getQuery()->getSingleScalarResult() > 0;
    } catch (\Exception $ex) {
      Log::error($ex);
      return false;
    }
  }

  /**
   * @param ExtraQuestionType $questionType
   */
  public function deleteAnswersFrom(ExtraQuestionType $questionType): void {
    try {
      $query = $this->getEntityManager()
        ->createQueryBuilder()
        ->delete(ExtraQuestionAnswer::class, "e")
        ->where("e.question = :question")
        ->setParameter("question", $questionType);

      $query->getQuery()->execute();
    } catch (\Exception $ex) {
      Log::error($ex);
    }
  }
}
