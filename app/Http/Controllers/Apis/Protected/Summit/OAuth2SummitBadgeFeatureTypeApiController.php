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
use App\Models\Foundation\Summit\Repositories\ISummitBadgeFeatureTypeRepository;
use App\Security\SummitScopes;
use App\Services\Model\ISummitBadgeFeatureTypeService;
use Illuminate\Http\Request as LaravelRequest;
use Illuminate\Support\Facades\Log;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use models\oauth2\IResourceServerContext;
use models\summit\ISummitRepository;
use models\summit\Summit;
use models\utils\IBaseRepository;
use models\utils\IEntity;
use ModelSerializers\SerializerRegistry;
use OpenApi\Attributes as OA;
use Exception;
/**
 * Class OAuth2SummitBadgeFeatureTypeApiController
 * @package App\Http\Controllers
 */
final class OAuth2SummitBadgeFeatureTypeApiController
    extends OAuth2ProtectedController

{
    /**
     * @var ISummitRepository
     */
    private $summit_repository;

    /**
     * @var ISummitBadgeFeatureTypeService
     */
    private $service;

    /**
     * OAuth2SummitBadgeFeatureTypeApiController constructor.
     * @param ISummitBadgeFeatureTypeRepository $repository
     * @param ISummitRepository $summit_repository
     * @param ISummitBadgeFeatureTypeService $service
     * @param IResourceServerContext $resource_server_context
     */
    public function __construct
    (
        ISummitBadgeFeatureTypeRepository $repository,
        ISummitRepository $summit_repository,
        ISummitBadgeFeatureTypeService $service,
        IResourceServerContext $resource_server_context
    )
    {
        parent::__construct($resource_server_context);
        $this->repository = $repository;
        $this->summit_repository = $summit_repository;
        $this->service = $service;
    }

    // OpenAPI Documentation

    #[OA\Get(
        path: '/api/v1/summits/{id}/badge-feature-types',
        summary: 'Get all badge feature types for a summit',
        description: 'Retrieves a paginated list of badge feature types for a specific summit. Badge feature types define visual elements and features that can be applied to attendee badges (e.g., speaker ribbons, sponsor logos, special access indicators).',
        security: [['oauth2_security_scope' => [SummitScopes::ReadAllSummitData]]],
        tags: ['Badge Feature Types'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'Summit ID',
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'page',
                in: 'query',
                required: false,
                description: 'Page number for pagination',
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
            new OA\Parameter(
                name: 'per_page',
                in: 'query',
                required: false,
                description: 'Items per page',
                schema: new OA\Schema(type: 'integer', example: 10, maximum: 100)
            ),
            new OA\Parameter(
                name: 'filter[]',
                in: 'query',
                required: false,
                description: 'Filter expressions. Format: field<op>value. Available field: name (=@, ==). Operators: == (equals), =@ (starts with)',
                style: 'form',
                explode: true,
                schema: new OA\Schema(
                    type: 'array',
                    items: new OA\Items(type: 'string', example: 'name@@speaker')
                )
            ),
            new OA\Parameter(
                name: 'order',
                in: 'query',
                required: false,
                description: 'Order by field(s). Available fields: name, id. Use "-" prefix for descending order.',
                schema: new OA\Schema(type: 'string', example: 'name')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Badge feature types retrieved successfully',
                content: new OA\JsonContent(ref: '#/components/schemas/PaginatedSummitBadgeFeatureTypesResponse')
            ),
            new OA\Response(response: 400, ref: '#/components/responses/400'),
            new OA\Response(response: 401, ref: '#/components/responses/401'),
            new OA\Response(response: 403, ref: '#/components/responses/403'),
            new OA\Response(response: 404, ref: '#/components/responses/404'),
            new OA\Response(response: 412, ref: '#/components/responses/412'),
            new OA\Response(response: 500, ref: '#/components/responses/500'),
        ]
    )]

    #[OA\Get(
        path: '/api/v1/summits/{id}/badge-feature-types/{feature_id}',
        summary: 'Get a badge feature type by ID',
        description: 'Retrieves detailed information about a specific badge feature type.',
        security: [['oauth2_security_scope' => [SummitScopes::ReadAllSummitData]]],
        tags: ['Badge Feature Types'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'Summit ID',
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'feature_id',
                in: 'path',
                required: true,
                description: 'Badge Feature Type ID',
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Badge feature type retrieved successfully',
                content: new OA\JsonContent(ref: '#/components/schemas/SummitBadgeFeatureType')
            ),
            new OA\Response(response: 400, ref: '#/components/responses/400'),
            new OA\Response(response: 401, ref: '#/components/responses/401'),
            new OA\Response(response: 403, ref: '#/components/responses/403'),
            new OA\Response(response: 404, ref: '#/components/responses/404'),
            new OA\Response(response: 412, ref: '#/components/responses/412'),
            new OA\Response(response: 500, ref: '#/components/responses/500'),
        ]
    )]

    #[OA\Post(
        path: '/api/v1/summits/{id}/badge-feature-types',
        summary: 'Create a new badge feature type',
        description: 'Creates a new badge feature type for the summit.',
        security: [['oauth2_security_scope' => [SummitScopes::WriteSummitData]]],
        tags: ['Badge Feature Types'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'Summit ID',
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/SummitBadgeFeatureTypeCreateRequest')
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Badge feature type created successfully',
                content: new OA\JsonContent(ref: '#/components/schemas/SummitBadgeFeatureType')
            ),
            new OA\Response(response: 400, ref: '#/components/responses/400'),
            new OA\Response(response: 401, ref: '#/components/responses/401'),
            new OA\Response(response: 403, ref: '#/components/responses/403'),
            new OA\Response(response: 404, ref: '#/components/responses/404'),
            new OA\Response(response: 412, ref: '#/components/responses/412'),
            new OA\Response(response: 422, ref: '#/components/responses/422'),
            new OA\Response(response: 500, ref: '#/components/responses/500'),
        ]
    )]

    #[OA\Put(
        path: '/api/v1/summits/{id}/badge-feature-types/{feature_id}',
        summary: 'Update a badge feature type',
        description: 'Updates an existing badge feature type.',
        security: [['oauth2_security_scope' => [SummitScopes::WriteSummitData]]],
        tags: ['Badge Feature Types'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'Summit ID',
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'feature_id',
                in: 'path',
                required: true,
                description: 'Badge Feature Type ID',
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/SummitBadgeFeatureTypeUpdateRequest')
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Badge feature type updated successfully',
                content: new OA\JsonContent(ref: '#/components/schemas/SummitBadgeFeatureType')
            ),
            new OA\Response(response: 400, ref: '#/components/responses/400'),
            new OA\Response(response: 401, ref: '#/components/responses/401'),
            new OA\Response(response: 403, ref: '#/components/responses/403'),
            new OA\Response(response: 404, ref: '#/components/responses/404'),
            new OA\Response(response: 412, ref: '#/components/responses/412'),
            new OA\Response(response: 422, ref: '#/components/responses/422'),
            new OA\Response(response: 500, ref: '#/components/responses/500'),
        ]
    )]

    #[OA\Delete(
        path: '/api/v1/summits/{id}/badge-feature-types/{feature_id}',
        summary: 'Delete a badge feature type',
        description: 'Deletes an existing badge feature type from the summit.',
        security: [['oauth2_security_scope' => [SummitScopes::WriteSummitData]]],
        tags: ['Badge Feature Types'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'Summit ID',
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'feature_id',
                in: 'path',
                required: true,
                description: 'Badge Feature Type ID',
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        responses: [
            new OA\Response(
                response: 204,
                description: 'Badge feature type deleted successfully'
            ),
            new OA\Response(response: 400, ref: '#/components/responses/400'),
            new OA\Response(response: 401, ref: '#/components/responses/401'),
            new OA\Response(response: 403, ref: '#/components/responses/403'),
            new OA\Response(response: 404, ref: '#/components/responses/404'),
            new OA\Response(response: 412, ref: '#/components/responses/412'),
            new OA\Response(response: 500, ref: '#/components/responses/500'),
        ]
    )]

    #[OA\Post(
        path: '/api/v1/summits/{id}/badge-feature-types/{feature_id}/image',
        summary: 'Add an image to a badge feature type',
        description: 'Uploads and associates an image file with a badge feature type. This image is typically displayed on attendee badges.',
        security: [['oauth2_security_scope' => [SummitScopes::WriteSummitData]]],
        tags: ['Badge Feature Types'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'Summit ID',
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'feature_id',
                in: 'path',
                required: true,
                description: 'Badge Feature Type ID',
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['file'],
                    properties: [
                        new OA\Property(
                            property: 'file',
                            type: 'string',
                            format: 'binary',
                            description: 'Image file to upload'
                        )
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Image uploaded successfully',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'id', type: 'integer', example: 1),
                        new OA\Property(property: 'url', type: 'string', example: 'https://example.com/images/badge-feature.png'),
                    ]
                )
            ),
            new OA\Response(response: 400, ref: '#/components/responses/400'),
            new OA\Response(response: 401, ref: '#/components/responses/401'),
            new OA\Response(response: 403, ref: '#/components/responses/403'),
            new OA\Response(response: 404, ref: '#/components/responses/404'),
            new OA\Response(response: 412, ref: '#/components/responses/412'),
            new OA\Response(response: 500, ref: '#/components/responses/500'),
        ]
    )]

    #[OA\Delete(
        path: '/api/v1/summits/{id}/badge-feature-types/{feature_id}/image',
        summary: 'Delete the image from a badge feature type',
        description: 'Removes the associated image from a badge feature type.',
        security: [['oauth2_security_scope' => [SummitScopes::WriteSummitData]]],
        tags: ['Badge Feature Types'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'Summit ID',
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'feature_id',
                in: 'path',
                required: true,
                description: 'Badge Feature Type ID',
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        responses: [
            new OA\Response(
                response: 204,
                description: 'Image deleted successfully'
            ),
            new OA\Response(response: 400, ref: '#/components/responses/400'),
            new OA\Response(response: 401, ref: '#/components/responses/401'),
            new OA\Response(response: 403, ref: '#/components/responses/403'),
            new OA\Response(response: 404, ref: '#/components/responses/404'),
            new OA\Response(response: 412, ref: '#/components/responses/412'),
            new OA\Response(response: 500, ref: '#/components/responses/500'),
        ]
    )]

    /**
     * @return array
     */
    protected function getFilterRules():array
    {
        return [
            'name' => ['=@', '=='],
        ];
    }

    /**
     * @return array
     */
    protected function getFilterValidatorRules():array{
        return [
            'name' => 'sometimes|required|string',
        ];
    }
    /**
     * @return array
     */
    protected function getOrderRules():array{
        return [
            'id',
            'name',
        ];
    }

    use GetAllBySummit;

    use GetSummitChildElementById;

    use AddSummitChildElement;

    use UpdateSummitChildElement;

    use DeleteSummitChildElement;

    /**
     * @param array $payload
     * @return array
     */
    function getAddValidationRules(array $payload): array
    {
       return SummitBadgeFeatureTypeValidationRulesFactory::build($payload);
    }

    /**
     * @param Summit $summit
     * @param array $payload
     * @return IEntity
     */
    protected function addChild(Summit $summit, array $payload): IEntity
    {
        return $this->service->addBadgeFeatureType($summit, $payload);
    }

    /**
     * @return ISummitRepository
     */
    protected function getSummitRepository(): ISummitRepository
    {
       return $this->summit_repository;
    }

    /**
     * @return IResourceServerContext
     */
    protected function getResourceServerContext(): IResourceServerContext
    {
        return $this->resource_server_context;
    }

    /**
     * @return IBaseRepository
     */
    protected function getRepository(): IBaseRepository
    {
        return $this->repository;
    }

    /**
     * @param Summit $summit
     * @param $child_id
     * @return void
     */
    protected function deleteChild(Summit $summit, $child_id): void
    {
       $this->service->deleteBadgeFeatureType($summit, $child_id);
    }

    /**
     * @param Summit $summit
     * @param $child_id
     * @return IEntity|null
     */
    protected function getChildFromSummit(Summit $summit,$child_id): ?IEntity
    {
       return $summit->getFeatureTypeById($child_id);
    }

    /**
     * @param array $payload
     * @return array
     */
    function getUpdateValidationRules(array $payload): array
    {
        return SummitBadgeFeatureTypeValidationRulesFactory::build($payload, true);
    }

    /**
     * @param Summit $summit
     * @param int $child_id
     * @param array $payload
     * @return IEntity
     */
    protected function updateChild(Summit $summit, int $child_id, array $payload): IEntity
    {
        return $this->service->updateBadgeFeatureType($summit, $child_id, $payload);
    }

    /**
     * @param LaravelRequest $request
     * @param $summit_id
     * @param $feature_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function addFeatureImage(LaravelRequest $request, $summit_id, $feature_id){
        try {
            $summit = SummitFinderStrategyFactory::build($this->getSummitRepository(), $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $file = $request->file('file');
            if (is_null($file)) {
                return $this->error412(array('file param not set!'));
            }

            $image = $this->service->addFeatureImage($summit, $feature_id, $file);

            return $this->created(SerializerRegistry::getInstance()->getSerializer($image)->serialize());

        }
        catch (ValidationException $ex1)
        {
            Log::warning($ex1);
            return $this->error412(array($ex1->getMessage()));
        }
        catch(EntityNotFoundException $ex2)
        {
            Log::warning($ex2);
            return $this->error404(array('message'=> $ex2->getMessage()));
        }
        catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    /**
     * @param $summit_id
     * @param $feature_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function deleteFeatureImage($summit_id, $feature_id) {
        try {
            $summit = SummitFinderStrategyFactory::build($this->getSummitRepository(), $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();
            $this->service->removeFeatureImage($summit, $feature_id);
            return $this->deleted();
        }
        catch (ValidationException $ex1)
        {
            Log::warning($ex1);
            return $this->error412(array($ex1->getMessage()));
        }
        catch(EntityNotFoundException $ex2)
        {
            Log::warning($ex2);
            return $this->error404(array('message'=> $ex2->getMessage()));
        }
        catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }
}
