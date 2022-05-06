<?php namespace App\Http\Controllers;
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

use App\Models\Foundation\ExtraQuestions\ExtraQuestionTypeConstants;
use App\Models\Foundation\Summit\Repositories\ISummitOrderExtraQuestionTypeRepository;
use App\Services\Model\ISummitOrderExtraQuestionTypeService;
use libs\utils\HTMLCleaner;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use models\oauth2\IResourceServerContext;
use models\summit\ISummitRepository;
use models\summit\Summit;
use models\utils\IEntity;
use ModelSerializers\SerializerRegistry;
use utils\PagingResponse;
use App\Http\Controllers\RequestProcessor;

/**
 * Class OAuth2SummitOrderExtraQuestionTypeApiController
 * @package App\Http\Controllers
 */
final class OAuth2SummitOrderExtraQuestionTypeApiController
    extends OAuth2ProtectedController
{

    use GetAndValidateJsonPayload;

    /**
     * @var ISummitRepository
     */
    private $summit_repository;

    /**
     * @var ISummitOrderExtraQuestionTypeService
     */
    private $service;

    /**
     * OAuth2SummitSponsorApiController constructor.
     * @param ISummitOrderExtraQuestionTypeRepository $repository
     * @param ISummitRepository $summit_repository
     * @param ISummitOrderExtraQuestionTypeService $service
     * @param IResourceServerContext $resource_server_context
     */
    public function __construct
    (
        ISummitOrderExtraQuestionTypeRepository $repository,
        ISummitRepository                       $summit_repository,
        ISummitOrderExtraQuestionTypeService    $service,
        IResourceServerContext                  $resource_server_context
    )
    {
        parent::__construct($resource_server_context);
        $this->summit_repository = $summit_repository;
        $this->service = $service;
        $this->repository = $repository;
    }

    use GetAllBySummit;

    use GetSummitChildElementById;

    use AddSummitChildElement;

    use UpdateSummitChildElement;

    use DeleteSummitChildElement;

    use RequestProcessor;

    /**
     * @param $summit_id
     * @return mixed
     */
    public function getMetadata($summit_id)
    {
        $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
        if (is_null($summit)) return $this->error404();

        return $this->ok
        (
            $this->repository->getQuestionsMetadata($summit)
        );
    }

    /**
     * @return array
     */
    protected function getFilterRules(): array
    {
        return [
            'name' => ['==', '=@'],
            'type' => ['==', '=@'],
            'usage' => ['==', '=@'],
            'label' => ['==', '=@'],
            'class' => ['==']
        ];
    }

    /**
     * @return array
     */
    protected function getFilterValidatorRules(): array
    {
        return [
            'name' => 'sometimes|required|string',
            'type' => 'sometimes|required|string',
            'usage' => 'sometimes|required|string',
            'label' => 'sometimes|required|string',
            'class' => 'sometimes|required|string|in:' . implode(',', ExtraQuestionTypeConstants::AllowedQuestionClass),
        ];
    }

    /**
     * @return array
     */
    protected function getOrderRules(): array
    {
        return [
            'id',
            'name',
            'label',
            'order',
        ];
    }

    /**
     * @param array $payload
     * @return array
     */
    function getAddValidationRules(array $payload): array
    {
        return SummitOrderExtraQuestionTypeValidationRulesFactory::build($payload);
    }

    /**
     * @param Summit $summit
     * @param array $payload
     * @return IEntity
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    protected function addChild(Summit $summit, array $payload): IEntity
    {
        return $this->service->addOrderExtraQuestion($summit, HTMLCleaner::cleanData($payload, ['label']));
    }

    /**
     * @return ISummitRepository
     */
    protected function getSummitRepository(): ISummitRepository
    {
        return $this->summit_repository;
    }

    /**
     * @param Summit $summit
     * @param $child_id
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    protected function deleteChild(Summit $summit, $child_id): void
    {
        $this->service->deleteOrderExtraQuestion($summit, $child_id);
    }

    protected function getChildFromSummit(Summit $summit, $child_id): ?IEntity
    {
        return $summit->getOrderExtraQuestionById($child_id);
    }

    /**
     * @param array $payload
     * @return array
     */
    function getUpdateValidationRules(array $payload): array
    {
        return SummitOrderExtraQuestionTypeValidationRulesFactory::build($payload, true);
    }

    /**
     * @param Summit $summit
     * @param int $child_id
     * @param array $payload
     * @return IEntity
     * @throws \models\exceptions\EntityNotFoundException
     * @throws \models\exceptions\ValidationException
     */
    protected function updateChild(Summit $summit, int $child_id, array $payload): IEntity
    {
        return $this->service->updateOrderExtraQuestion
        (
            $summit, $child_id,
            HTMLCleaner::cleanData($payload, ['label'])
        );
    }

    /**
     * @param $summit_id
     * @param $question_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function addQuestionValue($summit_id, $question_id)
    {
        return $this->processRequest(function () use ($summit_id, $question_id) {
            $summit = SummitFinderStrategyFactory::build($this->getSummitRepository(), $this->getResourceServerContext())->find($summit_id);
            if (is_null($summit)) return $this->error404();
            $payload = $this->getJsonPayload(ExtraQuestionTypeValueValidationRulesFactory::build(ExtraQuestionTypeValueValidationRulesFactory::build([])));

            $value = $this->service->addOrderExtraQuestionValue($summit, $question_id, $payload);

            return $this->created
            (
                SerializerRegistry::getInstance()->getSerializer($value)->serialize
                (
                    self::getExpands(),
                    self::getFields(),
                    self::getRelations(),
                )
            );
        });
    }

    /**
     * @param $summit_id
     * @param $question_id
     * @param $value_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function updateQuestionValue($summit_id, $question_id, $value_id)
    {
        return $this->processRequest(function () use ($summit_id, $question_id, $value_id) {
            $summit = SummitFinderStrategyFactory::build($this->getSummitRepository(), $this->getResourceServerContext())->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $payload = $this->getJsonPayload(ExtraQuestionTypeValueValidationRulesFactory::build(ExtraQuestionTypeValueValidationRulesFactory::build([], true)));

            $value = $this->service->updateOrderExtraQuestionValue($summit, $question_id, $value_id, $payload);

            return $this->updated(SerializerRegistry::getInstance()->getSerializer($value)->serialize
            (
                self::getExpands(),
                self::getFields(),
                self::getRelations(),
            ));
        });
    }

    /**
     * @param $summit_id
     * @param $question_id
     * @param $value_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function deleteQuestionValue($summit_id, $question_id, $value_id)
    {
        return $this->processRequest(function () use ($summit_id, $question_id, $value_id) {
            $summit = SummitFinderStrategyFactory::build($this->getSummitRepository(), $this->getResourceServerContext())->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $this->service->deleteOrderExtraQuestionValue($summit, $question_id, $value_id);

            return $this->deleted();
        });
    }

    /**
     * @param $summit_id
     * @return mixed
     */
    public function seedDefaultSummitExtraOrderQuestionTypesBySummit($summit_id)
    {
        return $this->processRequest(function () use ($summit_id) {
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $question_types = $this->service->seedSummitOrderExtraQuestionTypesFromEventBrite($summit);

            $response = new PagingResponse
            (
                count($question_types),
                count($question_types),
                1,
                1,
                $question_types
            );

            return $this->created($response->toArray());
        });
    }

    /**
     * Sub Questions
     */

    /**
     * @param $summit_id
     * @param $question_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function getSubQuestionRules($summit_id, $question_id)
    {
        return $this->processRequest(function () use ($summit_id, $question_id) {
            $summit = SummitFinderStrategyFactory::build($this->getSummitRepository(), $this->getResourceServerContext())->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $question = $summit->getOrderExtraQuestionById(intval($question_id));
            if (is_null($question)) return $this->error404();

            $rules = $question->getSubQuestionRules()->toArray();

            $response = new PagingResponse
            (
                count($rules),
                count($rules),
                1,
                1,
                $rules
            );

            return $this->ok
            (
                $response->toArray
                (
                    self::getExpands(),
                    self::getRelations(),
                    self::getFields()
                )
            );
        });
    }

    /**
     * @param $summit_id
     * @param $question_id
     */
    public function addSubQuestionRule($summit_id, $question_id)
    {
        return $this->processRequest(function () use ($summit_id, $question_id) {
            $summit = SummitFinderStrategyFactory::build($this->getSummitRepository(), $this->getResourceServerContext())->find($summit_id);
            if (is_null($summit)) return $this->error404();
            $payload = $this->getJsonPayload(SubQuestionRuleValidationRulesFactory::build([]));

            $sub_question_rule = $this->service->addSubQuestionRule($summit, intval($question_id), $payload);
            return $this->created
            (
                SerializerRegistry::getInstance()
                    ->getSerializer($sub_question_rule)
                    ->serialize
                    (
                        self::getExpands(),
                        self::getRelations(),
                        self::getFields()
                    )
            );
        });
    }

    /**
     * @param $summit_id
     * @param $question_id
     * @param $rule_id
     */
    public function updateSubQuestionRule($summit_id, $question_id, $rule_id)
    {
        return $this->processRequest(function () use ($summit_id, $question_id, $rule_id) {
            $summit = SummitFinderStrategyFactory::build($this->getSummitRepository(), $this->getResourceServerContext())->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $payload = $this->getJsonPayload(SubQuestionRuleValidationRulesFactory::build([], true));

            $sub_question_rule = $this->service->updateSubQuestionRule($summit, intval($question_id), intval($rule_id), $payload);
            return $this->updated
            (
                SerializerRegistry::getInstance()
                    ->getSerializer($sub_question_rule)
                    ->serialize
                    (
                        self::getExpands(),
                        self::getRelations(),
                        self::getFields()
                    )
            );
        });
    }

    /**
     * @param $summit_id
     * @param $question_id
     * @param $rule_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function getSubQuestionRule($summit_id, $question_id, $rule_id)
    {
        return $this->processRequest(function () use ($summit_id, $question_id, $rule_id) {
            $summit = SummitFinderStrategyFactory::build($this->getSummitRepository(), $this->getResourceServerContext())->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $question = $summit->getOrderExtraQuestionById(intval($question_id));
            if (is_null($question)) return $this->error404();

            $sub_question_rule = $question->getSubQuestionRulesById(intval($rule_id));
            if (is_null($sub_question_rule)) return $this->error404();

            return $this->ok
            (
                SerializerRegistry::getInstance()
                    ->getSerializer($sub_question_rule)
                    ->serialize
                    (
                        self::getExpands(),
                        self::getRelations(),
                        self::getFields()
                    )
            );
        });
    }

    /**
     * @param $summit_id
     * @param $question_id
     * @param $rule_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function deleteSubQuestionRule($summit_id, $question_id, $rule_id)
    {
        return $this->processRequest(function () use ($summit_id, $question_id, $rule_id) {
            $summit = SummitFinderStrategyFactory::build($this->getSummitRepository(), $this->getResourceServerContext())->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $this->service->deleteSubQuestionRule($summit, intval($question_id), intval($rule_id));
            return $this->deleted();
        });
    }
}