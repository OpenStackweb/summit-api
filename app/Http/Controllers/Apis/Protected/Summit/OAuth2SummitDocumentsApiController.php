<?php

namespace App\Http\Controllers;

/**
 * Copyright 2020 OpenStack Foundation
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

use App\Http\Utils\MultipartFormDataCleaner;
use App\Models\Foundation\Main\IGroup;
use App\Models\Foundation\Summit\Repositories\ISummitDocumentRepository;
use App\ModelSerializers\SerializerUtils;
use App\Security\SummitScopes;
use App\Services\Model\ISummitDocumentService;
use Illuminate\Http\Request as LaravelRequest;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use libs\utils\HTMLCleaner;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use models\oauth2\IResourceServerContext;
use models\summit\ISummitRepository;
use models\summit\Summit;
use models\utils\IEntity;
use ModelSerializers\SerializerRegistry;
use OpenApi\Attributes as OA;
/**
 * Class OAuth2SummitDocumentsApiController
 * @package App\Http\Controllers
 */
class OAuth2SummitDocumentsApiController extends OAuth2ProtectedController
{
    // traits

    use GetAllBySummit;

    use GetSummitChildElementById;

    use DeleteSummitChildElement;

    use RequestProcessor;

    /**
     * @var ISummitRepository
     */
    private $summit_repository;

    /**
     * @var ISummitDocumentService
     */
    private $service;

    /**
     * OAuth2SummitDocumentsApiController constructor.
     * @param ISummitDocumentRepository $repository
     * @param ISummitRepository $summit_repository
     * @param ISummitDocumentService $service
     * @param IResourceServerContext $resource_server_context
     */
    public function __construct
    (
        ISummitDocumentRepository $repository,
        ISummitRepository $summit_repository,
        ISummitDocumentService $service,
        IResourceServerContext $resource_server_context
    )
    {
        parent::__construct($resource_server_context);
        $this->repository = $repository;
        $this->summit_repository = $summit_repository;
        $this->service = $service;
    }

    #[OA\Get(
        path: "/api/v1/summits/{id}/summit-documents",
        description: "Get all summit documents",
        summary: "Get summit documents",
        operationId: "getAllSummitDocuments",
        tags: ['Summit Documents'],
        security: [['summit_document_oauth2' => [
            SummitScopes::ReadAllSummitData,
        ]]],
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
                explode: false,
                schema: new OA\Schema(type: 'string'),
                description: 'Filter operators: name=@/==, description=@/==, label=@/==, event_type=@/==, selection_plan_id=='
            ),
            new OA\Parameter(
                name: 'order',
                in: 'query',
                required: false,
                explode: false,
                schema: new OA\Schema(type: 'string'),
                description: 'Order by fields: id, name, label'
            ),
            new OA\Parameter(
                name: 'expand',
                in: 'query',
                required: false,
                explode: false,
                schema: new OA\Schema(type: 'string'),
                description: 'Relations to expand: event_types, summit, selection_plan'
            ),
            new OA\Parameter(
                name: 'relations',
                in: 'query',
                required: false,
                explode: false,
                schema: new OA\Schema(type: 'string'),
                description: 'Relations to include: event_types, summit, selection_plan'
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "OK",
                content: new OA\JsonContent(ref: "#/components/schemas/PaginatedSummitDocumentsResponse")
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "not found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error")
        ]
    )]

    #[OA\Get(
        path: "/api/v1/summits/{id}/summit-documents/{document_id}",
        description: "Get a specific summit document",
        summary: "Get summit document",
        operationId: "getSummitDocument",
        tags: ['Summit Documents'],
        security: [['summit_document_oauth2' => [
            SummitScopes::ReadAllSummitData,
        ]]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The summit id'
            ),
            new OA\Parameter(
                name: 'document_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The document id'
            ),
            new OA\Parameter(
                name: 'expand',
                in: 'query',
                required: false,
                explode: false,
                schema: new OA\Schema(type: 'string'),
                description: 'Relations to expand: event_types, summit, selection_plan'
            ),
            new OA\Parameter(
                name: 'relations',
                in: 'query',
                required: false,
                explode: false,
                schema: new OA\Schema(type: 'string'),
                description: 'Relations to include: event_types, summit, selection_plan'
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "OK",
                content: new OA\JsonContent(ref: "#/components/schemas/SummitDocument")
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "not found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error")
        ]
    )]

    #[OA\Post(
        path: "/api/v1/summits/{id}/summit-documents",
        description: "Create a new summit document",
        summary: "Create summit document",
        operationId: "addSummitDocument",
        tags: ['Summit Documents'],
        security: [['summit_document_oauth2' => [
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
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(ref: "#/components/schemas/SummitDocumentCreateRequest")
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Created",
                content: new OA\JsonContent(ref: "#/components/schemas/SummitDocument")
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: "Bad Request"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "not found"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error")
        ]
    )]
    public function add(LaravelRequest $request, $summit_id){
        try {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $payload = $request->all();

            $rules = [
                'file'            => 'required_without:web_link',
                'name'            => 'required|string:512',
                'label'           => 'required|string:512',
                'description'     => 'nullable|string',
                'event_types'     => 'sometimes|int_array',
                'show_always'     => 'sometimes|boolean',
                'web_link'        => 'required_without:file|url|max:256',
                'selection_plan_id' => 'sometimes|nullable|integer',
            ];

            $payload = MultipartFormDataCleaner::cleanBool('show_always', $payload);

            // Creates a Validator instance and validates the data.
            $validation = Validator::make($payload, $rules);

            if ($validation->fails()) {
                $ex = new ValidationException;
                $ex->setMessages($validation->messages()->toArray());
                throw $ex;
            }

            $fields = [
                'name',
                'description',
                'label',
            ];

            $document = $this->service->addSummitDocument
            (
                $summit,
                HTMLCleaner::cleanData($payload, $fields)

            );
            return $this->created(SerializerRegistry::getInstance()->getSerializer($document)->serialize());
        }
        catch (EntityNotFoundException $ex1)
        {
            Log::warning($ex1);
            return $this->error404();
        }
        catch (ValidationException $ex2)
        {
            Log::warning($ex2);
            return $this->error412($ex2->getMessages());
        }
        catch (\Exception $ex)
        {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    #[OA\Put(
        path: "/api/v1/summits/{id}/summit-documents/{document_id}",
        description: "Update an existing summit document",
        summary: "Update summit document",
        operationId: "updateSummitDocument",
        tags: ['Summit Documents'],
        security: [['summit_document_oauth2' => [
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
                name: 'document_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The document id'
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(ref: "#/components/schemas/SummitDocumentUpdateRequest")
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "OK",
                content: new OA\JsonContent(ref: "#/components/schemas/SummitDocument")
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: "Bad Request"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "not found"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error")
        ]
    )]
    public function update(LaravelRequest $request, $summit_id, $document_id){
        try {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $payload = $request->all();

            $rules = [
                'name'            => 'nullable|string:255',
                'label'           => 'nullable|string:255',
                'description'     => 'nullable|string',
                'event_types'     => 'sometimes|int_array',
                'show_always'     => 'sometimes|boolean',
                'web_link'        => 'sometimes|url|max:256',
                'selection_plan_id' => 'sometimes|nullable|integer',
            ];

            $payload = MultipartFormDataCleaner::cleanBool('show_always', $payload);

            // Creates a Validator instance and validates the data.
            $validation = Validator::make($payload, $rules);

            if ($validation->fails()) {
                $ex = new ValidationException;
                $ex->setMessages($validation->messages()->toArray());
                throw $ex;
            }

            $fields = [
                'name',
                'description',
                'label',
                'web_link',
            ];

            $document = $this->service->updateSummitDocument
            (
                $summit,
                $document_id,
                HTMLCleaner::cleanData($payload, $fields)
            );

            return $this->updated(SerializerRegistry::getInstance()->getSerializer($document)->serialize());
        }
        catch (EntityNotFoundException $ex1)
        {
            Log::warning($ex1);
            return $this->error404();
        }
        catch (ValidationException $ex2)
        {
            Log::warning($ex2);
            return $this->error412($ex2->getMessages());
        }
        catch (\Exception $ex)
        {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    #[OA\Delete(
        path: "/api/v1/summits/{id}/summit-documents/{document_id}",
        description: "Delete a summit document",
        summary: "Delete summit document",
        operationId: "deleteSummitDocument",
        tags: ['Summit Documents'],
        security: [['summit_document_oauth2' => [
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
                name: 'document_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The document id'
            )
        ],
        responses: [
            new OA\Response(response: 204, description: 'No Content'),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "not found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error")
        ]
    )]

    /**
     * @inheritDoc
     */
    protected function getSummitRepository(): ISummitRepository
    {
       return $this->summit_repository;
    }

    /**
     * @inheritDoc
     */
    protected function deleteChild(Summit $summit, $child_id): void
    {
       $this->service->deleteSummitDocument($summit, $child_id);
    }

    /**
     * @inheritDoc
     */
    protected function getChildFromSummit(Summit $summit, $child_id): ?IEntity
    {
       return $summit->getSummitDocumentById($child_id);
    }

    /**
     * @return array
     */
    protected function getFilterRules():array
    {
        return [
            'name' => ['=@', '=='],
            'description' => ['=@', '=='],
            'label' => ['=@', '=='],
            'event_type' => ['=@', '=='],
            'selection_plan_id' => ['=='],
        ];
    }

    /**
     * @return array
     */
    protected function getFilterValidatorRules():array{
        return [
            'name' => 'sometimes|required|string',
            'description' => 'sometimes|required|string',
            'label' => 'sometimes|required|string',
            'event_type' => 'sometimes|required|string',
            'selection_plan_id' => 'sometimes|required|integer',
        ];
    }
    /**
     * @return array
     */
    protected function getOrderRules():array{
        return [
            'id',
            'name',
            'label',
        ];
    }

    #[OA\Put(
        path: "/api/v1/summits/{id}/summit-documents/{document_id}/event-types/{event_type_id}",
        description: "Add an event type to a summit document",
        summary: "Add event type to document",
        operationId: "addEventTypeToDocument",
        tags: ['Summit Documents'],
        security: [['summit_document_oauth2' => [
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
                name: 'document_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The document id'
            ),
            new OA\Parameter(
                name: 'event_type_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The event type id'
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "OK",
                content: new OA\JsonContent(ref: "#/components/schemas/SummitDocument")
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: "Bad Request"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "not found"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error")
        ]
    )]
    public function addEventType($summit_id, $document_id, $event_type_id){
        try {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();


            $document = $this->service->addEventTypeToSummitDocument
            (
                $summit,
                $document_id,
                $event_type_id
            );
            return $this->updated(SerializerRegistry::getInstance()->getSerializer($document)->serialize());
        }
        catch (EntityNotFoundException $ex1)
        {
            Log::warning($ex1);
            return $this->error404();
        }
        catch (ValidationException $ex2)
        {
            Log::warning($ex2);
            return $this->error412($ex2->getMessages());
        }
        catch (\Exception $ex)
        {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    #[OA\Delete(
        path: "/api/v1/summits/{id}/summit-documents/{document_id}/event-types/{event_type_id}",
        description: "Remove an event type from a summit document",
        summary: "Remove event type from document",
        operationId: "removeEventTypeFromDocument",
        tags: ['Summit Documents'],
        security: [['summit_document_oauth2' => [
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
                name: 'document_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The document id'
            ),
            new OA\Parameter(
                name: 'event_type_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The event type id'
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "OK",
                content: new OA\JsonContent(ref: "#/components/schemas/SummitDocument")
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: "Bad Request"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "not found"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error")
        ]
    )]
    public function removeEventType($summit_id, $document_id, $event_type_id){
        try {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();


            $document = $this->service->removeEventTypeFromSummitDocument
            (
                $summit,
                $document_id,
                $event_type_id
            );
            return $this->updated(SerializerRegistry::getInstance()->getSerializer($document)->serialize());
        }
        catch (EntityNotFoundException $ex1)
        {
            Log::warning($ex1);
            return $this->error404();
        }
        catch (ValidationException $ex2)
        {
            Log::warning($ex2);
            return $this->error412($ex2->getMessages());
        }
        catch (\Exception $ex)
        {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    #[OA\Post(
        path: "/api/v1/summits/{id}/summit-documents/{document_id}/file",
        description: "Add or replace a file for a summit document",
        summary: "Add file to document",
        operationId: "addFileToDocument",
        tags: ['Summit Documents'],
        security: [['summit_document_oauth2' => [
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
                name: 'document_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The document id'
            )
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
                            description: 'Document file to upload'
                        )
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "OK",
                content: new OA\JsonContent(ref: "#/components/schemas/SummitDocument")
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: "Bad Request"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "not found"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error")
        ]
    )]
    public function addFile(LaravelRequest $request, $summit_id, $document_id){
        return $this->processRequest(function () use ($request, $summit_id, $document_id) {
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $file = $request->file('file');
            if (is_null($file)) {
                return $this->error412(array('file param not set!'));
            }

            $document = $this->service->addFile2SummitDocument($summit, intval($document_id), $file);

            return $this->updated(SerializerRegistry::getInstance()->getSerializer($document)->serialize(
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));
        });
    }

    #[OA\Delete(
        path: "/api/v1/summits/{id}/summit-documents/{document_id}/file",
        description: "Remove a file from a summit document",
        summary: "Remove file from document",
        operationId: "removeFileFromDocument",
        tags: ['Summit Documents'],
        security: [['summit_document_oauth2' => [
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
                name: 'document_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The document id'
            )
        ],
        responses: [
            new OA\Response(response: 204, description: 'No Content'),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "not found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error")
        ]
    )]
    public function removeFile($summit_id, $document_id){
        return $this->processRequest(function () use ($summit_id, $document_id) {
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $this->service->removeFileFromSummitDocument($summit, intval($document_id));
            return $this->deleted();
        });
    }
}
