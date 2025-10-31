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
use App\Models\Foundation\Main\IGroup;
use App\Models\Foundation\Summit\Repositories\ISummitOrderExtraQuestionTypeRepository;
use App\ModelSerializers\SerializerUtils;
use App\Rules\Boolean;
use App\Services\Model\ISummitOrderExtraQuestionTypeService;
use libs\utils\HTMLCleaner;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use models\oauth2\IResourceServerContext;
use models\summit\ISummitRepository;
use models\summit\Summit;
use models\summit\SummitOrder;
use models\summit\SummitOrderExtraQuestionTypeConstants;
use models\utils\IEntity;
use ModelSerializers\SerializerRegistry;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Response;
use utils\Filter;
use utils\FilterElement;
use utils\PagingInfo;
use utils\PagingResponse;

/**
 * Class OAuth2SummitOrderExtraQuestionTypeApiController
 * @package App\Http\Controllers
 */
#[OA\Tag(name: "Order Extra Questions", description: "Summit Order Extra Questions Management")]
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

    use ParametrizedGetAll;

    /**
     * @param $summit_id
     * @return mixed
     */
    #[OA\Get(
        path: "/api/v1/summits/{id}/order-extra-questions/metadata",
        operationId: "getOrderExtraQuestionsMetadata",
        description: "Get metadata for order extra questions",
        tags: ["Order Extra Questions"],
        parameters: [
            new OA\Parameter(
                name: "id",
                description: "Summit ID",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer", format: "int64")
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "Metadata retrieved successfully",
                content: new OA\JsonContent(type: "object")
            ),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Summit not found"),
        ]
    )]
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
            'class' => ['=='],
            'has_ticket_types' => ['=='],
            'has_badge_feature_types' => ['==']
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
            'has_ticket_types' => 'sometimes|string|in:true,false',
            'has_badge_feature_types' => 'sometimes|string|in:true,false'
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
    #[OA\Post(
        path: "/api/v1/summits/{id}/order-extra-questions",
        operationId: "addOrderExtraQuestion",
        description: "Add a new order extra question",
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: "#/components/schemas/SummitOrderExtraQuestionType")
        ),
        tags: ["Order Extra Questions"],
        parameters: [
            new OA\Parameter(
                name: "id",
                description: "Summit ID",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer", format: "int64")
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_CREATED,
                description: "Order extra question created",
                content: new OA\JsonContent(ref: "#/components/schemas/SummitOrderExtraQuestionType")
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: "Bad Request"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Summit not found"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
        ]
    )]
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
    #[OA\Delete(
        path: "/api/v1/summits/{id}/order-extra-questions/{question_id}",
        operationId: "deleteOrderExtraQuestion",
        description: "Delete an order extra question",
        tags: ["Order Extra Questions"],
        parameters: [
            new OA\Parameter(
                name: "id",
                description: "Summit ID",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer", format: "int64")
            ),
            new OA\Parameter(
                name: "question_id",
                description: "Question ID",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer", format: "int64")
            ),
        ],
        responses: [
            new OA\Response(response: Response::HTTP_NO_CONTENT, description: "Question deleted successfully"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Question or Summit not found"),
        ]
    )]
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
    #[OA\Put(
        path: "/api/v1/summits/{id}/order-extra-questions/{question_id}",
        operationId: "updateOrderExtraQuestion",
        description: "Update an order extra question",
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: "#/components/schemas/SummitOrderExtraQuestionType")
        ),
        tags: ["Order Extra Questions"],
        parameters: [
            new OA\Parameter(
                name: "id",
                description: "Summit ID",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer", format: "int64")
            ),
            new OA\Parameter(
                name: "question_id",
                description: "Question ID",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer", format: "int64")
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "Question updated successfully",
                content: new OA\JsonContent(ref: "#/components/schemas/SummitOrderExtraQuestionType")
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: "Bad Request"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Question or Summit not found"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
        ]
    )]
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
    #[OA\Post(
        path: "/api/v1/summits/{id}/order-extra-questions/{question_id}/values",
        operationId: "addOrderExtraQuestionValue",
        description: "Add a value to an order extra question",
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: "#/components/schemas/ExtraQuestionTypeValue")
        ),
        tags: ["Order Extra Questions"],
        parameters: [
            new OA\Parameter(
                name: "id",
                description: "Summit ID",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer", format: "int64")
            ),
            new OA\Parameter(
                name: "question_id",
                description: "Question ID",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer", format: "int64")
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_CREATED,
                description: "Value created successfully",
                content: new OA\JsonContent(ref: "#/components/schemas/ExtraQuestionTypeValue")
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: "Bad Request"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Question or Summit not found"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
        ]
    )]
    public function addQuestionValue($summit_id, $question_id)
    {
        return $this->processRequest(function () use ($summit_id, $question_id) {
            $summit = SummitFinderStrategyFactory::build($this->getSummitRepository(), $this->getResourceServerContext())->find($summit_id);
            if (is_null($summit)) return $this->error404();
            $payload = $this->getJsonPayload(ExtraQuestionTypeValueValidationRulesFactory::buildForAdd());

            $value = $this->service->addOrderExtraQuestionValue($summit, $question_id, $payload);

            return $this->created
            (
                SerializerRegistry::getInstance()->getSerializer($value)->serialize
                (
                    SerializerUtils::getExpand(),
                    SerializerUtils::getFields(),
                    SerializerUtils::getRelations()
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
    #[OA\Put(
        path: "/api/v1/summits/{id}/order-extra-questions/{question_id}/values/{value_id}",
        operationId: "updateOrderExtraQuestionValue",
        description: "Update a value of an order extra question",
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: "#/components/schemas/ExtraQuestionTypeValue")
        ),
        tags: ["Order Extra Questions"],
        parameters: [
            new OA\Parameter(
                name: "id",
                description: "Summit ID",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer", format: "int64")
            ),
            new OA\Parameter(
                name: "question_id",
                description: "Question ID",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer", format: "int64")
            ),
            new OA\Parameter(
                name: "value_id",
                description: "Value ID",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer", format: "int64")
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "Value updated successfully",
                content: new OA\JsonContent(ref: "#/components/schemas/ExtraQuestionTypeValue")
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: "Bad Request"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Value, Question or Summit not found"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
        ]
    )]
    public function updateQuestionValue($summit_id, $question_id, $value_id)
    {
        return $this->processRequest(function () use ($summit_id, $question_id, $value_id) {
            $summit = SummitFinderStrategyFactory::build($this->getSummitRepository(), $this->getResourceServerContext())->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $payload = $this->getJsonPayload(ExtraQuestionTypeValueValidationRulesFactory::buildForUpdate());

            $value = $this->service->updateOrderExtraQuestionValue($summit, $question_id, $value_id, $payload);

            return $this->updated(SerializerRegistry::getInstance()->getSerializer($value)->serialize
            (
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));
        });
    }

    /**
     * @param $summit_id
     * @param $question_id
     * @param $value_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    #[OA\Delete(
        path: "/api/v1/summits/{id}/order-extra-questions/{question_id}/values/{value_id}",
        operationId: "deleteOrderExtraQuestionValue",
        description: "Delete a value from an order extra question",
        tags: ["Order Extra Questions"],
        parameters: [
            new OA\Parameter(
                name: "id",
                description: "Summit ID",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer", format: "int64")
            ),
            new OA\Parameter(
                name: "question_id",
                description: "Question ID",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer", format: "int64")
            ),
            new OA\Parameter(
                name: "value_id",
                description: "Value ID",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer", format: "int64")
            ),
        ],
        responses: [
            new OA\Response(response: Response::HTTP_NO_CONTENT, description: "Value deleted successfully"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Value, Question or Summit not found"),
        ]
    )]
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
    #[OA\Post(
        path: "/api/v1/summits/{id}/order-extra-questions/seed-defaults",
        operationId: "seedDefaultOrderExtraQuestions",
        description: "Seed default order extra questions from EventBrite",
        tags: ["Order Extra Questions"],
        parameters: [
            new OA\Parameter(
                name: "id",
                description: "Summit ID",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer", format: "int64")
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_CREATED,
                description: "Default questions seeded successfully",
                content: new OA\JsonContent(ref: "#/components/schemas/PaginateDataSchemaResponse")
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Summit not found"),
        ]
    )]
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
    #[OA\Get(
        path: "/api/v1/summits/{id}/order-extra-questions/{question_id}/sub-question-rules",
        operationId: "getSubQuestionRules",
        description: "Get sub question rules for an order extra question",
        tags: ["Order Extra Questions"],
        parameters: [
            new OA\Parameter(
                name: "id",
                description: "Summit ID",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer", format: "int64")
            ),
            new OA\Parameter(
                name: "question_id",
                description: "Question ID",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer", format: "int64")
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "Sub question rules retrieved successfully",
                content: new OA\JsonContent(ref: "#/components/schemas/PaginateDataSchemaResponse")
            ),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Question or Summit not found"),
        ]
    )]
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
                    SerializerUtils::getExpand(),
                    SerializerUtils::getFields(),
                    SerializerUtils::getRelations()
                )
            );
        });
    }

    /**
     * @param $summit_id
     * @param $question_id
     */
    #[OA\Post(
        path: "/api/v1/summits/{id}/order-extra-questions/{question_id}/sub-question-rules",
        operationId: "addSubQuestionRule",
        description: "Add a sub question rule to an order extra question",
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: "#/components/schemas/SubQuestionRule")
        ),
        tags: ["Order Extra Questions"],
        parameters: [
            new OA\Parameter(
                name: "id",
                description: "Summit ID",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer", format: "int64")
            ),
            new OA\Parameter(
                name: "question_id",
                description: "Question ID",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer", format: "int64")
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_CREATED,
                description: "Sub question rule created successfully",
                content: new OA\JsonContent(ref: "#/components/schemas/SubQuestionRule")
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: "Bad Request"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Question or Summit not found"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
        ]
    )]
    public function addSubQuestionRule($summit_id, $question_id)
    {
        return $this->processRequest(function () use ($summit_id, $question_id) {
            $summit = SummitFinderStrategyFactory::build($this->getSummitRepository(), $this->getResourceServerContext())->find($summit_id);
            if (is_null($summit)) return $this->error404();
            $payload = $this->getJsonPayload(SubQuestionRuleValidationRulesFactory::buildForAdd());

            $sub_question_rule = $this->service->addSubQuestionRule($summit, intval($question_id), $payload);
            return $this->created
            (
                SerializerRegistry::getInstance()
                    ->getSerializer($sub_question_rule)
                    ->serialize
                    (
                        SerializerUtils::getExpand(),
                        SerializerUtils::getFields(),
                        SerializerUtils::getRelations()
                    )
            );
        });
    }

    /**
     * @param $summit_id
     * @param $question_id
     * @param $rule_id
     */
    #[OA\Put(
        path: "/api/v1/summits/{id}/order-extra-questions/{question_id}/sub-question-rules/{rule_id}",
        operationId: "updateSubQuestionRule",
        description: "Update a sub question rule",
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: "#/components/schemas/SubQuestionRule")
        ),
        tags: ["Order Extra Questions"],
        parameters: [
            new OA\Parameter(
                name: "id",
                description: "Summit ID",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer", format: "int64")
            ),
            new OA\Parameter(
                name: "question_id",
                description: "Question ID",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer", format: "int64")
            ),
            new OA\Parameter(
                name: "rule_id",
                description: "Rule ID",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer", format: "int64")
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "Sub question rule updated successfully",
                content: new OA\JsonContent(ref: "#/components/schemas/SubQuestionRule")
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: "Bad Request"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Rule, Question or Summit not found"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
        ]
    )]
    public function updateSubQuestionRule($summit_id, $question_id, $rule_id)
    {
        return $this->processRequest(function () use ($summit_id, $question_id, $rule_id) {
            $summit = SummitFinderStrategyFactory::build($this->getSummitRepository(), $this->getResourceServerContext())->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $payload = $this->getJsonPayload(SubQuestionRuleValidationRulesFactory::buildForUpdate());

            $sub_question_rule = $this->service->updateSubQuestionRule($summit, intval($question_id), intval($rule_id), $payload);
            return $this->updated
            (
                SerializerRegistry::getInstance()
                    ->getSerializer($sub_question_rule)
                    ->serialize
                    (
                        SerializerUtils::getExpand(),
                        SerializerUtils::getFields(),
                        SerializerUtils::getRelations()
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
    #[OA\Get(
        path: "/api/v1/summits/{id}/order-extra-questions/{question_id}/sub-question-rules/{rule_id}",
        operationId: "getSubQuestionRule",
        description: "Get a specific sub question rule",
        tags: ["Order Extra Questions"],
        parameters: [
            new OA\Parameter(
                name: "id",
                description: "Summit ID",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer", format: "int64")
            ),
            new OA\Parameter(
                name: "question_id",
                description: "Question ID",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer", format: "int64")
            ),
            new OA\Parameter(
                name: "rule_id",
                description: "Rule ID",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer", format: "int64")
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "Sub question rule retrieved successfully",
                content: new OA\JsonContent(ref: "#/components/schemas/SubQuestionRule")
            ),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Rule, Question or Summit not found"),
        ]
    )]
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
                        SerializerUtils::getExpand(),
                        SerializerUtils::getFields(),
                        SerializerUtils::getRelations()
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
    #[OA\Delete(
        path: "/api/v1/summits/{id}/order-extra-questions/{question_id}/sub-question-rules/{rule_id}",
        operationId: "deleteSubQuestionRule",
        description: "Delete a sub question rule",
        tags: ["Order Extra Questions"],
        parameters: [
            new OA\Parameter(
                name: "id",
                description: "Summit ID",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer", format: "int64")
            ),
            new OA\Parameter(
                name: "question_id",
                description: "Question ID",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer", format: "int64")
            ),
            new OA\Parameter(
                name: "rule_id",
                description: "Rule ID",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer", format: "int64")
            ),
        ],
        responses: [
            new OA\Response(response: Response::HTTP_NO_CONTENT, description: "Sub question rule deleted successfully"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Rule, Question or Summit not found"),
        ]
    )]
    public function deleteSubQuestionRule($summit_id, $question_id, $rule_id)
    {
        return $this->processRequest(function () use ($summit_id, $question_id, $rule_id) {
            $summit = SummitFinderStrategyFactory::build($this->getSummitRepository(), $this->getResourceServerContext())->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $this->service->deleteSubQuestionRule($summit, intval($question_id), intval($rule_id));
            return $this->deleted();
        });
    }

    /**
     * @param $summit_id
     * @return mixed
     */
    #[OA\Get(
        path: "/api/v1/summits/{id}/attendees/me/allowed-extra-questions",
        operationId: "getOwnAttendeeAllowedExtraQuestions",
        description: "Get allowed extra questions for the current user's attendance",
        tags: ["Order Extra Questions"],
        parameters: [
            new OA\Parameter(
                name: "id",
                description: "Summit ID",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer", format: "int64")
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "Allowed questions retrieved successfully",
                content: new OA\JsonContent(ref: "#/components/schemas/PaginateDataSchemaResponse")
            ),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Summit or attendee not found"),
        ]
    )]
    public function getOwnAttendeeAllowedExtraQuestions($summit_id)
    {
        return $this->processRequest(function () use ($summit_id) {
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->getResourceServerContext())->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $type = CheckAttendeeStrategyFactory::Me;
            $attendee = CheckAttendeeStrategyFactory::build($type, $this->resource_server_context)->check('me', $summit);
            if (is_null($attendee)) return $this->error404();

            return $this->getAttendeeExtraQuestions($summit_id, $attendee->getId());
        });
    }

    /**
     * @param $summit_id
     * @param $attendee_id
     * @return mixed
     */
    #[OA\Get(
        path: "/api/v1/summits/{id}/attendees/{attendee_id}/allowed-extra-questions",
        operationId: "getAttendeeAllowedExtraQuestions",
        description: "Get allowed extra questions for a specific attendee",
        tags: ["Order Extra Questions"],
        parameters: [
            new OA\Parameter(
                name: "id",
                description: "Summit ID",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer", format: "int64")
            ),
            new OA\Parameter(
                name: "attendee_id",
                description: "Attendee ID",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer", format: "int64")
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "Allowed questions retrieved successfully",
                content: new OA\JsonContent(ref: "#/components/schemas/PaginateDataSchemaResponse")
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "You are not Authorized"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Summit or attendee not found"),
        ]
    )]
    public function getAttendeeExtraQuestions($summit_id, $attendee_id)
    {
        $summit = SummitFinderStrategyFactory::build($this->getSummitRepository(), $this->getResourceServerContext())->find($summit_id);
        if (is_null($summit)) return $this->error404("Summit not found.");

        $attendee = $summit->getAttendeeById(intval($attendee_id));
        if (is_null($attendee)) return $this->error404("Attendee not found.");

        // authz
        // check that we have a current member ( not service account )
        $current_member = $this->getResourceServerContext()->getCurrentUser();
        if(is_null($current_member))
            return $this->error401();
        // check is user is admin or its on any pre - authorized group
        $auth = $current_member->isSummitAllowed($summit) ||
            $current_member->isOnGroup(IGroup::BadgePrinters);

        if(!$auth){
            // check if current member is the attendee
            $auth = (
                    $attendee->getEmail() == $current_member->getEmail()
                    || $attendee->getMemberId() == $current_member->getId()
                    || $attendee->isManagedBy($current_member)
            );

            if(!$auth){
                // check if the attendee is under some order of the current member
                foreach($current_member->getPaidRegistrationOrdersForSummit($summit) as $order){
                    if(!$order instanceof SummitOrder) continue;
                    if($order->hasTicketOwner($attendee)){
                        $auth = true;
                        break;
                    }
                }
            }
        }

        if(!$auth)
            return $this->error403("You are not Authorized.");

        return $this->_getAll(
            function () {
                return [
                    'name'      => ['=@', '=='],
                    'type'      => ['=@', '=='],
                    'label'     => ['=@', '=='],
                    'printable' => ['=='],
                    'usage'     => ['=@', '=='],
                    'summit_id' => ['=='],
                    'tickets_exclude_inactives' => ['=='],
                ];
            },
            function () {
                return [
                    'name'      => 'sometimes|string',
                    'type'      => 'sometimes|string',
                    'label'     => 'sometimes|string',
                    'printable' => 'sometimes|string|in:true,false',
                    'usage'     => 'sometimes|string',
                    'summit_id' => 'sometimes|integer',
                    'tickets_exclude_inactives' => ['sometimes', new Boolean()],
                ];
            },
            function () {
                return [
                    'id',
                    'name',
                    'label',
                    'order',
                ];
            },
            function ($filter) use ($summit) {
                if ($filter instanceof Filter) {
                    $filter->addFilterCondition(FilterElement::makeEqual('summit_id', $summit->getId()));
                    $filter->addFilterCondition(FilterElement::makeEqual('class', ExtraQuestionTypeConstants::QuestionClassMain));
                    $filter->addFilterCondition(FilterElement::makeEqual('usage', SummitOrderExtraQuestionTypeConstants::TicketQuestionUsage));
                }
                return $filter;
            },
            function () {
                return SerializerRegistry::SerializerType_Public;
            },
            null,
            null,
            function ($page, $per_page, $filter, $order, $applyExtraFilters) use ($attendee) {
                return $this->repository->getAllAllowedMainQuestionByAttendee
                (
                    $attendee,
                    new PagingInfo($page, $per_page),
                    call_user_func($applyExtraFilters, $filter),
                    $order
                );
            },
            ['attendee' => $attendee]
        );
    }
}