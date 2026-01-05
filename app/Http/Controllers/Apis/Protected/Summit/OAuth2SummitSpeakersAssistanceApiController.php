<?php

namespace App\Http\Controllers;

/**
 * Copyright 2018 OpenStack Foundation
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
use App\Http\Utils\BooleanCellFormatter;
use App\Http\Utils\EpochCellFormatter;
use App\Models\Foundation\Main\IGroup;
use App\Models\Foundation\Summit\Repositories\IPresentationSpeakerSummitAssistanceConfirmationRequestRepository;
use App\Security\SummitScopes;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Validator;
use libs\utils\PaginationValidationRules;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use models\oauth2\IResourceServerContext;
use models\summit\ISpeakerRepository;
use models\summit\ISummitRepository;
use ModelSerializers\SerializerRegistry;
use OpenApi\Attributes as OA;
use services\model\ISpeakerService;
use utils\FilterParser;
use utils\OrderParser;
use utils\PagingInfo;
use Exception;
/**
 * Class OAuth2SummitSpeakersAssistanceApiController
 * @package App\Http\Controllers
 */
final class OAuth2SummitSpeakersAssistanceApiController extends OAuth2ProtectedController
{
    /**
     * @var ISummitRepository
     */
    private $summit_repository;

    /**
     * @var IPresentationSpeakerSummitAssistanceConfirmationRequestRepository
     */
    private $speakers_assistance_repository;

    /**
     * @var ISpeakerRepository
     */
    private $speaker_repository;

    /**
     * @var ISpeakerService
     */
    private $service;


    public function __construct
    (
        ISummitRepository $summit_repository,
        IPresentationSpeakerSummitAssistanceConfirmationRequestRepository $speakers_assistance_repository,
        ISpeakerRepository $speaker_repository,
        ISpeakerService $service,
        IResourceServerContext $resource_server_context
    )
    {
        parent::__construct($resource_server_context);
        $this->summit_repository              = $summit_repository;
        $this->speaker_repository             = $speaker_repository;
        $this->service                        = $service;
        $this->speakers_assistance_repository = $speakers_assistance_repository;
    }

    /**
     * @param $summit_id
     * @return mixed
     */
    #[OA\Get(
        path: "/api/v1/summits/{id}/speakers-assistances",
        operationId: 'getBySummit',
        summary: "Get all speaker assistances for a summit",
        security: [["summit_speaker_assistances_oauth2" => [SummitScopes::ReadAllSummitData]]],
        tags: ["Summit Speakers Assistances"],
        x: [
            "required-groups" => [IGroup::SuperAdmins, IGroup::Administrators, IGroup::SummitAdministrators]
        ],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer"),
                description: "The summit id"
            ),
            new OA\Parameter(
                name: "page",
                in: "query",
                required: false,
                schema: new OA\Schema(type: "integer", default: 1),
                description: "Page number"
            ),
            new OA\Parameter(
                name: "per_page",
                in: "query",
                required: false,
                schema: new OA\Schema(type: "integer", default: 10),
                description: "Items per page"
            ),
            new OA\Parameter(
                name: "filter",
                in: "query",
                description: "Filter query. Available operators: id==, on_site_phone==/=@, speaker_email==/=@, speaker==/=@, is_confirmed==, registered==, confirmation_date>/</>=/<= (epoch)",
                schema: new OA\Schema(type: "string")
            ),
            new OA\Parameter(
                name: "order",
                in: "query",
                description: "Order by field. Available fields: id, is_confirmed, confirmation_date, created, registered",
                schema: new OA\Schema(type: "string")
            ),
            new OA\Parameter(
                name: "expand",
                in: "query",
                description: "Comma-separated list of relations to expand. Available: speaker",
                schema: new OA\Schema(type: "string")
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "OK",
                content: new OA\JsonContent(ref: "#/components/schemas/PaginatedPresentationSpeakerSummitAssistanceConfirmationRequestsResponse")
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error")
        ]
    )]
    public function getBySummit($summit_id)
    {
        try {
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $values = Request::all();

            $rules = PaginationValidationRules::get();

            $validation = Validator::make($values, $rules);

            if ($validation->fails()) {
                $messages = $validation->messages()->toArray();

                return $this->error412($messages);
            }

            // default values
            $page = 1;
            $per_page = 10;

            if (Request::has('page')) {
                $page = intval(Request::input('page'));
                $per_page = intval(Request::input('per_page'));
            }

            $filter = null;

            if (Request::has('filter')) {
                $filter = FilterParser::parse(Request::input('filter'), [
                    'id'                => ['=='],
                    'on_site_phone'     => ['==', '=@'],
                    'speaker_email'     => ['==', '=@'],
                    'speaker'           => ['==', '=@'],
                    'is_confirmed'      => ['=='],
                    'registered'        => ['=='],
                    'confirmation_date' => ['>', '<', '>=', '<=']
                ]);
            }

            $order = null;
            if (Request::has('order')) {
                $order = OrderParser::parse(Request::input('order'), [
                    'id',
                    'is_confirmed',
                    'confirmation_date',
                    'created',
                    'registered',
                ]);
            }

            $serializer_type = SerializerRegistry::SerializerType_Private;
            $result = $this->speakers_assistance_repository->getBySummit($summit, new PagingInfo($page, $per_page), $filter, $order);

            return $this->ok
            (
                $result->toArray
                (
                    Request::input('expand', ''),
                    [],
                    [],
                    [
                        'summit' => $summit,
                        'serializer_type' => $serializer_type
                    ],
                    $serializer_type
                )
            );
        } catch (ValidationException $ex1) {
            Log::warning($ex1);
            return $this->error412($ex1->getMessages());
        } catch (EntityNotFoundException $ex2) {
            Log::warning($ex2);
            return $this->error404(array('message' => $ex2->getMessage()));
        } catch (\Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    /**
     * @param $summit_id
     * @return mixed
     */
    #[OA\Get(
        path: "/api/v1/summits/{id}/speakers-assistances/csv",
        operationId: 'getBySummitCSV',
        summary: "Export speaker assistances to CSV",
        security: [["summit_speaker_assistances_oauth2" => [SummitScopes::ReadAllSummitData]]],
        tags: ["Summit Speakers Assistances"],
        x: [
            "required-groups" => [IGroup::SuperAdmins, IGroup::Administrators, IGroup::SummitAdministrators]
        ],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer"),
                description: "The summit id"
            ),
            new OA\Parameter(
                name: "filter",
                in: "query",
                description: "Filter query. Available operators: id==, on_site_phone==/=@, speaker_email==/=@, speaker==/=@, is_confirmed==, registered==, confirmation_date>/</>=/<= (epoch)",
                schema: new OA\Schema(type: "string")
            ),
            new OA\Parameter(
                name: "order",
                in: "query",
                description: "Order by field. Available fields: id, is_confirmed, confirmation_date, created, registered",
                schema: new OA\Schema(type: "string")
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "OK",
                content: new OA\MediaType(mediaType: "text/csv", schema: new OA\Schema(type: "string"))
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error")
        ]
    )]
    public function getBySummitCSV($summit_id){
        try {
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            // default values
            $page = 1;
            $per_page = PHP_INT_MAX;

            if (Request::has('page')) {
                $page = intval(Request::input('page'));
                $per_page = intval(Request::input('per_page'));
            }

            $filter = null;

            if (Request::has('filter')) {
                $filter = FilterParser::parse(Request::input('filter'), [
                    'id'                => ['=='],
                    'on_site_phone'     => ['==', '=@'],
                    'speaker_email'     => ['==', '=@'],
                    'speaker'           => ['==', '=@'],
                    'is_confirmed'      => ['=='],
                    'registered'        => ['=='],
                    'confirmation_date' => ['>', '<', '>=', '<=']
                ]);
            }

            $order = null;
            if (Request::has('order')) {
                $order = OrderParser::parse(Request::input('order'), [
                    'id',
                    'is_confirmed',
                    'confirmation_date',
                    'created',
                    'registered',
                ]);
            }

            $serializer_type = SerializerRegistry::SerializerType_Private;
            $data = $this->speakers_assistance_repository->getBySummit($summit, new PagingInfo($page, $per_page), $filter, $order);

            $filename = "summit-speaker-assistances-" . date('Ymd');
            $list     =  $data->toArray();
            return $this->export(
                'csv',
                $filename,
                $list['data'],
                [
                    'created'           => new EpochCellFormatter,
                    'last_edited'       => new EpochCellFormatter,
                    'confirmation_date' => new EpochCellFormatter,
                    'registered'        => new BooleanCellFormatter,
                    'is_confirmed'      => new BooleanCellFormatter,
                    'checked_in'        => new BooleanCellFormatter,
                ]
            );

        } catch (ValidationException $ex1) {
            Log::warning($ex1);
            return $this->error412($ex1->getMessages());
        } catch (EntityNotFoundException $ex2) {
            Log::warning($ex2);
            return $this->error404(array('message' => $ex2->getMessage()));
        } catch (\Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    /**
     * @param $summit_id
     * @return mixed
     */
    #[OA\Post(
        path: "/api/v1/summits/{id}/speakers-assistances",
        operationId: 'addSpeakerSummitAssistance',
        summary: "Create a speaker assistance confirmation request",
        security: [["summit_speaker_assistances_oauth2" => [SummitScopes::WriteSummitSpeakerAssistanceData, SummitScopes::WriteSummitData]]],
        tags: ["Summit Speakers Assistances"],
        x: [
            "required-groups" => [IGroup::SuperAdmins, IGroup::Administrators, IGroup::SummitAdministrators]
        ],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer"),
                description: "The summit id"
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: "application/json",
                schema: new OA\Schema(ref: "#/components/schemas/PresentationSpeakerSummitAssistanceConfirmationRequestCreateRequest")
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Created",
                content: new OA\JsonContent(ref: "#/components/schemas/PresentationSpeakerSummitAssistanceConfirmationRequest")
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: "Bad Request"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error")
        ]
    )]
    public function addSpeakerSummitAssistance($summit_id)
    {
        try {
            if(!Request::isJson()) return $this->error400();
            $data = Request::json()->all();

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $rules = [
                'speaker_id'        => 'required:integer',
                'on_site_phone'     => 'sometimes|string|max:50',
                'registered'        => 'sometimes|boolean',
                'is_confirmed'      => 'sometimes|boolean',
                'checked_in'        => 'sometimes|boolean',
            ];
            // Creates a Validator instance and validates the data.
            $validation = Validator::make($data, $rules);

            if ($validation->fails()) {
                $messages = $validation->messages()->toArray();

                return $this->error412
                (
                    $messages
                );
            }

            $speaker_assistance  = $this->service->addSpeakerAssistance($summit, $data);

            return $this->created(SerializerRegistry::getInstance()->getSerializer($speaker_assistance)->serialize());
        }
        catch (ValidationException $ex1) {
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
     * @param $assistance_id
     * @return mixed
     */
    #[OA\Put(
        path: "/api/v1/summits/{id}/speakers-assistances/{assistance_id}",
        operationId: 'updateSpeakerSummitAssistance',
        summary: "Update a speaker assistance confirmation request",
        security: [["summit_speaker_assistances_oauth2" => [SummitScopes::WriteSummitSpeakerAssistanceData, SummitScopes::WriteSummitData]]],
        tags: ["Summit Speakers Assistances"],
        x: [
            "required-groups" => [IGroup::SuperAdmins, IGroup::Administrators, IGroup::SummitAdministrators]
        ],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer"),
                description: "The summit id"
            ),
            new OA\Parameter(
                name: "assistance_id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer"),
                description: "The assistance id"
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: "application/json",
                schema: new OA\Schema(ref: "#/components/schemas/PresentationSpeakerSummitAssistanceConfirmationRequestUpdateRequest")
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "OK",
                content: new OA\JsonContent(ref: "#/components/schemas/PresentationSpeakerSummitAssistanceConfirmationRequest")
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: "Bad Request"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error")
        ]
    )]
    public function updateSpeakerSummitAssistance($summit_id, $assistance_id)
    {
        try {
            if(!Request::isJson()) return $this->error400();
            $data = Request::json()->all();

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $rules = [
                'on_site_phone'     => 'sometimes|string|max:50',
                'registered'        => 'sometimes|boolean',
                'is_confirmed'      => 'sometimes|boolean',
                'checked_in'        => 'sometimes|boolean',
            ];
            // Creates a Validator instance and validates the data.
            $validation = Validator::make($data, $rules);

            if ($validation->fails()) {
                $messages = $validation->messages()->toArray();

                return $this->error412
                (
                    $messages
                );
            }

            $speaker_assistance  = $this->service->updateSpeakerAssistance($summit, $assistance_id, $data);

            return $this->updated(SerializerRegistry::getInstance()->getSerializer($speaker_assistance)->serialize());
        }
        catch (ValidationException $ex1) {
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
     * @param $assistance_id
     * @return mixed
     */
    #[OA\Delete(
        path: "/api/v1/summits/{id}/speakers-assistances/{assistance_id}",
        operationId: 'deleteSpeakerSummitAssistance',
        summary: "Delete a speaker assistance confirmation request",
        security: [["summit_speaker_assistances_oauth2" => [SummitScopes::WriteSummitSpeakerAssistanceData, SummitScopes::WriteSummitData]]],
        tags: ["Summit Speakers Assistances"],
        x: [
            "required-groups" => [IGroup::SuperAdmins, IGroup::Administrators, IGroup::SummitAdministrators]
        ],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer"),
                description: "The summit id"
            ),
            new OA\Parameter(
                name: "assistance_id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer"),
                description: "The assistance id"
            )
        ],
        responses: [
            new OA\Response(response: 204, description: "No Content"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error")
        ]
    )]
    public function deleteSpeakerSummitAssistance($summit_id, $assistance_id)
    {
        try {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $this->service->deleteSpeakerAssistance($summit, $assistance_id);

            return $this->deleted();
        } catch (ValidationException $ex1) {
            Log::warning($ex1);
            return $this->error412(array($ex1->getMessage()));
        } catch (EntityNotFoundException $ex2) {
            Log::warning($ex2);
            return $this->error404(array('message' => $ex2->getMessage()));
        } catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }

    }

    /**
     * @param $summit_id
     * @param $assistance_id
     * @return mixed
     */
    #[OA\Get(
        path: "/api/v1/summits/{id}/speakers-assistances/{assistance_id}",
        operationId: 'getSpeakerSummitAssistanceBySummit',
        summary: "Get a speaker assistance confirmation request by id",
        security: [["summit_speaker_assistances_oauth2" => [SummitScopes::ReadAllSummitData]]],
        tags: ["Summit Speakers Assistances"],
        x: [
            "required-groups" => [IGroup::SuperAdmins, IGroup::Administrators, IGroup::SummitAdministrators]
        ],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer"),
                description: "The summit id"
            ),
            new OA\Parameter(
                name: "assistance_id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer"),
                description: "The assistance id"
            ),
            new OA\Parameter(
                name: "expand",
                in: "query",
                description: "Comma-separated list of relations to expand. Available: speaker",
                schema: new OA\Schema(type: "string")
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "OK",
                content: new OA\JsonContent(ref: "#/components/schemas/PresentationSpeakerSummitAssistanceConfirmationRequest")
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error")
        ]
    )]
    public function getSpeakerSummitAssistanceBySummit($summit_id, $assistance_id)
    {
        try {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $speaker_assistance = $summit->getSpeakerAssistanceById($assistance_id);

            if (is_null($speaker_assistance)) return $this->error404();

            return $this->ok
            (
                SerializerRegistry::getInstance()->getSerializer($speaker_assistance)->serialize
                (
                    Request::input('expand', '')
                )
            );
        } catch (ValidationException $ex1) {
            Log::warning($ex1);
            return $this->error412(array($ex1->getMessage()));
        } catch (EntityNotFoundException $ex2) {
            Log::warning($ex2);
            return $this->error404(array('message' => $ex2->getMessage()));
        } catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

}
