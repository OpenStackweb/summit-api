<?php namespace App\Http\Controllers;

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

use App\Http\Exceptions\HTTP403ForbiddenException;
use App\Models\Foundation\Main\IGroup;
use App\Models\Foundation\Summit\Repositories\ISummitScheduleConfigRepository;
use App\ModelSerializers\SerializerUtils;
use App\Security\SummitScopes;
use App\Services\Model\ISummitScheduleSettingsService;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use models\oauth2\IResourceServerContext;
use models\summit\ISummitRepository;
use models\summit\Summit;
use models\summit\SummitScheduleConfig;
use models\utils\IEntity;
use ModelSerializers\SerializerRegistry;
use OpenApi\Attributes as OA;
use utils\PagingResponse;

/**
 * Class OAuth2SummitScheduleSettingsApiController
 * @package App\Http\Controllers
 */
final class OAuth2SummitScheduleSettingsApiController extends OAuth2ProtectedController
{

    use AddSummitChildElement;

    use GetAllBySummit;

    use GetSummitChildElementById;

    use AddSummitChildElement;

    use UpdateSummitChildElement;

    use DeleteSummitChildElement;

    use ParametrizedAddEntity;

    use ParametrizedUpdateEntity;

    /**
     * @var ISummitRepository
     */
    private $summit_repository;

    /**
     * @var ISummitScheduleSettingsService
     */
    private $service;

    /**
     * @param ISummitRepository $summit_repository
     * @param ISummitScheduleConfigRepository $repository
     * @param ISummitScheduleSettingsService $service
     * @param IResourceServerContext $resource_server_context
     */
    public function __construct
    (
        ISummitRepository $summit_repository,
        ISummitScheduleConfigRepository $repository,
        ISummitScheduleSettingsService $service,
        IResourceServerContext $resource_server_context
    )
    {
        parent::__construct($resource_server_context);
        $this->service = $service;
        $this->repository = $repository;
        $this->summit_repository = $summit_repository;
    }

    /**
     * @return ISummitRepository
     */
    protected function getSummitRepository(): ISummitRepository
    {
        return $this->summit_repository;
    }

    /**
     * @inheritDoc
     */
    protected function addChild(Summit $summit, array $payload): IEntity
    {
        return $this->service->add($summit, $payload);
    }

    /**
     * @inheritDoc
     */
    function getAddValidationRules(array $payload): array
    {
        return SummitScheduleConfigRulesFactory::build($payload, false);
    }

    /**
     * @inheritDoc
     */
    protected function deleteChild(Summit $summit, $child_id): void
    {
        $this->service->delete($summit, intval($child_id));
    }

    /**
     * @inheritDoc
     */
    protected function getChildFromSummit(Summit $summit, $child_id): ?IEntity
    {
        return $summit->getScheduleSettingById(intval($child_id));
    }

    /**
     * @inheritDoc
     */
    function getUpdateValidationRules(array $payload): array
    {
        return SummitScheduleConfigRulesFactory::build($payload, true);
    }

    /**
     * @inheritDoc
     */
    protected function updateChild(Summit $summit, int $child_id, array $payload): IEntity
    {
        return $this->service->update($summit, intval($child_id), $payload);
    }

    /**
     * @return array
     */
    protected function getFilterRules():array
    {
        return [
            'key'        => ['=@', '=='],
            'is_enabled' => ['=='],
            'is_my_schedule' => ['=='],
            'only_events_with_attendee_access' => ['=='],
            'hide_past_events_with_show_always_on_schedule' => ['=='],
            'color_source' => ['==']
        ];
    }

    /**
     * @return array
     */
    protected function getFilterValidatorRules():array{
        return [
            'key' => 'sometimes|required|string',
            'is_enabled' => 'sometimes|required|boolean',
            'is_my_schedule' => 'sometimes|required|boolean',
            'only_events_with_attendee_access' => 'sometimes|required|boolean',
            'hide_past_events_with_show_always_on_schedule' => 'sometimes|required|boolean',
            'color_source' => 'sometimes|string|in:'.implode(',', SummitScheduleConfig::AllowedColorSource),
        ];
    }
    /**
     * @return array
     */
    protected function getOrderRules():array{
        return [
            'id',
            'key',
        ];
    }

    protected function serializerType():string{
        return SerializerRegistry::SerializerType_Private;
    }

    protected function addSerializerType():string{
        return SerializerRegistry::SerializerType_Private;
    }

    protected function updateSerializerType():string{
        return SerializerRegistry::SerializerType_Private;
    }

    public function getChildSerializer():string{
        return SerializerRegistry::SerializerType_Private;
    }

    #[OA\Get(
        path: "/api/v1/summits/{id}/schedule-settings",
        description: "Get all schedule settings for a summit",
        summary: "Get all schedule settings",
        operationId: "getAllSummitScheduleSettings",
        tags: ['Summit Schedule Settings'],
        security: [['summit_schedule_settings_oauth2' => [
            SummitScopes::ReadSummitData,
            SummitScopes::ReadAllSummitData,
        ]]],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
            ]
        ],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The summit id'
            ),
            new OA\Parameter(
                name: 'page',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 1),
                description: 'Page number'
            ),
            new OA\Parameter(
                name: 'per_page',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 10),
                description: 'Items per page'
            ),
            new OA\Parameter(
                name: 'filter',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string'),
                description: 'Filter expression (e.g., key=@schedule,is_enabled==true)'
            ),
            new OA\Parameter(
                name: 'order',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string'),
                description: 'Order by field (e.g., +id, -key)'
            ),
            new OA\Parameter(
                name: 'expand',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string'),
                description: 'Expand relationships (filters,pre_filters)'
            ),
            new OA\Parameter(
                name: 'relations',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string'),
                description: 'Relations to include (filters,pre_filters)'
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(ref: '#/components/schemas/PaginatedSummitScheduleConfigsResponse')
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error")
        ]
    )]

    #[OA\Get(
        path: "/api/v1/summits/{id}/schedule-settings/{config_id}",
        description: "Get a specific schedule setting by id",
        summary: "Get schedule setting",
        operationId: "getSummitScheduleSetting",
        tags: ['Summit Schedule Settings'],
        security: [['summit_schedule_settings_oauth2' => [
            SummitScopes::ReadSummitData,
            SummitScopes::ReadAllSummitData,
        ]]],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
            ]
        ],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The summit id'
            ),
            new OA\Parameter(
                name: 'config_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The schedule config id'
            ),
            new OA\Parameter(
                name: 'expand',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string'),
                description: 'Expand relationships (filters,pre_filters)'
            ),
            new OA\Parameter(
                name: 'relations',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string'),
                description: 'Relations to include (filters,pre_filters)'
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(ref: '#/components/schemas/SummitScheduleConfig')
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error")
        ]
    )]

    #[OA\Post(
        path: "/api/v1/summits/{id}/schedule-settings",
        description: "Create a new schedule setting for a summit",
        summary: "Create schedule setting",
        operationId: "createSummitScheduleSetting",
        tags: ['Summit Schedule Settings'],
        security: [['summit_schedule_settings_oauth2' => [
            SummitScopes::WriteSummitData,
        ]]],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
            ]
        ],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The summit id'
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/SummitScheduleConfigCreateRequest')
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Created',
                content: new OA\JsonContent(ref: '#/components/schemas/SummitScheduleConfig')
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: "Bad Request"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error")
        ]
    )]

    #[OA\Put(
        path: "/api/v1/summits/{id}/schedule-settings/{config_id}",
        description: "Update an existing schedule setting",
        summary: "Update schedule setting",
        operationId: "updateSummitScheduleSetting",
        tags: ['Summit Schedule Settings'],
        security: [['summit_schedule_settings_oauth2' => [
            SummitScopes::WriteSummitData,
        ]]],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
            ]
        ],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The summit id'
            ),
            new OA\Parameter(
                name: 'config_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The schedule config id'
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/SummitScheduleConfigUpdateRequest')
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(ref: '#/components/schemas/SummitScheduleConfig')
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: "Bad Request"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error")
        ]
    )]

    #[OA\Delete(
        path: "/api/v1/summits/{id}/schedule-settings/{config_id}",
        description: "Delete a schedule setting",
        summary: "Delete schedule setting",
        operationId: "deleteSummitScheduleSetting",
        tags: ['Summit Schedule Settings'],
        security: [['summit_schedule_settings_oauth2' => [
            SummitScopes::WriteSummitData,
        ]]],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
            ]
        ],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The summit id'
            ),
            new OA\Parameter(
                name: 'config_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The schedule config id'
            )
        ],
        responses: [
            new OA\Response(response: Response::HTTP_NO_CONTENT, description: 'No Content'),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error")
        ]
    )]

    #[OA\Get(
        path: "/api/v1/summits/{id}/schedule-settings/metadata",
        description: "Get metadata for schedule settings",
        summary: "Get schedule settings metadata",
        operationId: "getSummitScheduleSettingsMetadata",
        tags: ['Summit Schedule Settings'],
        security: [['summit_schedule_settings_oauth2' => [
            SummitScopes::ReadSummitData,
            SummitScopes::ReadAllSummitData,
        ]]],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
            ]
        ],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The summit id'
            )
        ],
        responses: [
            new OA\Response(response: 200, description: 'Success with an empty response body'),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
        ]
    )]
    public function getMetadata($summit_id){

    }

    #[OA\Post(
        path: "/api/v1/summits/{id}/schedule-settings/{config_id}/filters",
        description: "Add a filter to a schedule setting",
        summary: "Add schedule setting filter",
        operationId: "addSummitScheduleSettingFilter",
        tags: ['Summit Schedule Settings'],
        security: [['summit_schedule_settings_oauth2' => [
            SummitScopes::WriteSummitData,
        ]]],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
            ]
        ],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The summit id'
            ),
            new OA\Parameter(
                name: 'config_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The schedule config id'
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                type: 'object',
                required: ['type'],
                properties: [
                    new OA\Property(
                        property: 'type',
                        type: 'string',
                        enum: ['DATE', 'TRACK', 'TRACK_GROUPS', 'COMPANY', 'LEVEL', 'SPEAKERS', 'VENUES', 'EVENT_TYPES', 'TITLE', 'CUSTOM_ORDER', 'ABSTRACT', 'TAGS']
                    ),
                    new OA\Property(property: 'is_enabled', type: 'boolean'),
                    new OA\Property(property: 'label', type: 'string', nullable: true)
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Created',
                content: new OA\JsonContent(ref: '#/components/schemas/SummitScheduleFilterElementConfig')
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: "Bad Request"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error")
        ]
    )]
    public function addFilter($summit_id, $config_id){

        $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
        if (is_null($summit)) return $this->error404();

        $args = [$summit, intval($config_id)];

        return $this->_add(
            function ($payload) {
                return SummitScheduleFilterElementConfigValidationRulesFactory::build($payload);
            },
            function ($payload, $summit, $id){
                return $this->service->addFilter($summit, $id, $payload);
            },
            ...$args
        );
    }

    #[OA\Put(
        path: "/api/v1/summits/{id}/schedule-settings/{config_id}/filters/{filter_id}",
        description: "Update a filter of a schedule setting",
        summary: "Update schedule setting filter",
        operationId: "updateSummitScheduleSettingFilter",
        tags: ['Summit Schedule Settings'],
        security: [['summit_schedule_settings_oauth2' => [
            SummitScopes::WriteSummitData,
        ]]],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
            ]
        ],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The summit id'
            ),
            new OA\Parameter(
                name: 'config_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The schedule config id'
            ),
            new OA\Parameter(
                name: 'filter_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The filter id'
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                type: 'object',
                properties: [
                    new OA\Property(
                        property: 'type',
                        type: 'string',
                        enum: ['DATE', 'TRACK', 'TRACK_GROUPS', 'COMPANY', 'LEVEL', 'SPEAKERS', 'VENUES', 'EVENT_TYPES', 'TITLE', 'CUSTOM_ORDER', 'ABSTRACT', 'TAGS'],
                        nullable: true
                    ),
                    new OA\Property(property: 'is_enabled', type: 'boolean', nullable: true),
                    new OA\Property(property: 'label', type: 'string', nullable: true)
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(ref: '#/components/schemas/SummitScheduleFilterElementConfig')
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: "Bad Request"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error")
        ]
    )]
    public function updateFilter($summit_id, $config_id, $filter_id){
        $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
        if (is_null($summit)) return $this->error404();

        $args = [$summit, intval($config_id)];

        return $this->_update($filter_id, function($payload){
            return SummitScheduleFilterElementConfigValidationRulesFactory::build($payload, false);
        },
            function($filter_id, $payload, $summit, $config_id){
                return $this->service->updateFilter
                (
                    $summit,
                    $config_id,
                    $filter_id,
                    $payload
                );
            }, ...$args);
    }

    #[OA\Post(
        path: "/api/v1/summits/{id}/schedule-settings/seed",
        description: "Seed default schedule settings for a summit",
        summary: "Seed default schedule settings",
        operationId: "seedDefaultSummitScheduleSettings",
        tags: ['Summit Schedule Settings'],
        security: [['summit_schedule_settings_oauth2' => [
            SummitScopes::WriteSummitData,
        ]]],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
            ]
        ],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The summit id'
            )
        ],
        responses: [
            new OA\Response(
                response: 201,
                description: 'Created',
                content: new OA\JsonContent(ref: '#/components/schemas/PaginatedSummitScheduleConfigsResponse')
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error")
        ]
    )]
    public function seedDefaults($summit_id){
        try {
            $summit = SummitFinderStrategyFactory::build($this->getSummitRepository(), $this->getResourceServerContext())->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $list = $this->service->seedDefaults($summit);

            $response = new PagingResponse
            (
                count($list),
                count($list),
                1,
                1,
                $list
            );

            return $this->created($response->toArray(
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));
        }
        catch (ValidationException $ex) {
            Log::warning($ex);
            return $this->error412(array($ex->getMessage()));
        }
        catch(EntityNotFoundException $ex)
        {
            Log::warning($ex);
            return $this->error404(array('message'=> $ex->getMessage()));
        }
        catch (\HTTP401UnauthorizedException $ex) {
            Log::warning($ex);
            return $this->error401();
        }
        catch (HTTP403ForbiddenException $ex) {
            Log::warning($ex);
            return $this->error403();
        }
        catch (\Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }
}
