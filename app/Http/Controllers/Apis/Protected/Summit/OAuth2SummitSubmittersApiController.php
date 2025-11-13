<?php namespace App\Http\Controllers;
/**
 * Copyright 2023 OpenStack Foundation
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

use App\ModelSerializers\IMemberSerializerTypes;
use App\Models\Foundation\Main\IGroup;
use App\Security\SummitScopes;
use Illuminate\Support\Facades\Request;
use models\main\IMemberRepository;
use models\oauth2\IResourceServerContext;
use models\summit\ISummitRepository;
use services\model\ISubmitterService;
use utils\Filter;
use utils\FilterParser;
use utils\PagingInfo;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class OAuth2SummitSubmittersApiController
 * @package App\Http\Controllers
 */
final class OAuth2SummitSubmittersApiController
    extends OAuth2ProtectedController
{
    use ParametrizedGetAll;

    use GetAndValidateJsonPayload;

    use RequestProcessor;

    /**
     * @var ISubmitterService
     */
    private $service;

    /**
     * @var ISummitRepository
     */
    private $summit_repository;

    /**
     * OAuth2SummitSubmittersApiController constructor.
     * @param IMemberRepository $repository
     * @param ISummitRepository $summit_repository
     * @param ISubmitterService $service
     * @param IResourceServerContext $resource_server_context
     */
    public function __construct
    (
        IMemberRepository      $repository,
        ISummitRepository      $summit_repository,
        ISubmitterService      $service,
        IResourceServerContext $resource_server_context
    )
    {
        parent::__construct($resource_server_context);
        $this->repository = $repository;
        $this->summit_repository = $summit_repository;
        $this->service = $service;
    }

    #[OA\Get(
        path: "/api/v1/summits/{id}/submitters",
        summary: "Get all submitters for a summit",
        operationId: "getSubmittersBySummit",
        tags: ["SummitSubmitters"],
        security: [['summit_submitters_oauth2' => [
            SummitScopes::ReadSummitData,
            SummitScopes::ReadAllSummitData,
        ]]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "Summit ID or slug",
                schema: new OA\Schema(type: "string")
            ),
            new OA\Parameter(
                name: "page",
                in: "query",
                required: false,
                description: "Page number",
                schema: new OA\Schema(type: "integer", default: 1)
            ),
            new OA\Parameter(
                name: "per_page",
                in: "query",
                required: false,
                description: "Items per page",
                schema: new OA\Schema(type: "integer", default: 10)
            ),
            new OA\Parameter(
                name: "filter",
                in: "query",
                required: false,
                description: "Filter query (supports multiple operators)",
                schema: new OA\Schema(type: "string", example: "first_name=@John")
            ),
            new OA\Parameter(
                name: "order",
                in: "query",
                required: false,
                description: "Order by field (prefix with - for descending)",
                schema: new OA\Schema(type: "string", example: "first_name,-created")
            ),
            new OA\Parameter(
                name: "expand",
                in: "query",
                required: false,
                description: "Expand relations (presentations, member)",
                schema: new OA\Schema(type: "string", example: "presentations,member")
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "Successful response with paginated submitters",
                content: new OA\JsonContent(ref: '#/components/schemas/PaginatedSubmittersResponse')
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: "Bad Request"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Summit not found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    public function getAllBySummit($summit_id)
    {
        $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->getResourceServerContext())->find(intval($summit_id));
        if (is_null($summit)) return $this->error404();

        return $this->_getAll(
            function () {
                return [
                    'id' => ['=='],
                    'not_id' => ['=='],
                    'first_name' => ['=@', '@@', '=='],
                    'last_name' => ['=@', '@@', '=='],
                    'email' => ['=@', '@@', '=='],
                    'full_name' => ['=@', '@@', '=='],
                    'member_id' => ['=='],
                    'member_user_external_id' => ['=='],
                    'has_accepted_presentations' => ['=='],
                    'has_alternate_presentations' => ['=='],
                    'has_rejected_presentations' => ['=='],
                    'presentations_track_id' => ['=='],
                    'presentations_selection_plan_id' => ['=='],
                    'presentations_type_id' => ['=='],
                    'presentations_title' => ['=@', '@@', '=='],
                    'presentations_abstract' => ['=@', '@@', '=='],
                    'presentations_submitter_full_name' => ['=@', '@@', '=='],
                    'presentations_submitter_email' => ['=@', '@@', '=='],
                    'is_speaker' => ['=='],
                    'has_media_upload_with_type' => ['=='],
                    'has_not_media_upload_with_type' => ['=='],
                ];
            },
            function () {
                return [
                    'id' => 'sometimes|integer',
                    'not_id' => 'sometimes|integer',
                    'first_name' => 'sometimes|string',
                    'last_name' => 'sometimes|string',
                    'email' => 'sometimes|string',
                    'full_name' => 'sometimes|string',
                    'member_id' => 'sometimes|integer',
                    'member_user_external_id' => 'sometimes|integer',
                    'has_accepted_presentations' => 'sometimes|string|in:true,false',
                    'has_alternate_presentations' => 'sometimes|string|in:true,false',
                    'has_rejected_presentations' => 'sometimes|string|in:true,false',
                    'presentations_track_id' => 'sometimes|integer',
                    'presentations_selection_plan_id' => 'sometimes|integer',
                    'presentations_type_id' => 'sometimes|integer',
                    'presentations_title' => 'sometimes|string',
                    'presentations_abstract' => 'sometimes|string',
                    'presentations_submitter_full_name' => 'sometimes|string',
                    'presentations_submitter_email' => 'sometimes|string',
                    'is_speaker' => 'sometimes|string|in:true,false',
                    'has_media_upload_with_type' => 'sometimes|integer',
                    'has_not_media_upload_with_type' => 'sometimes|integer',
                ];
            },
            function () {
                return [
                    'id',
                    'first_name',
                    'last_name',
                    'full_name',
                    'email',
                    'created',
                    'last_edited',
                ];
            },
            function ($filter) use ($summit) {
                return $filter;
            },
            function () {
                return IMemberSerializerTypes::Submitter;
            },
            null,
            null,
            function ($page, $per_page, $filter, $order, $applyExtraFilters) use ($summit) {
                return $this->repository->getSubmittersBySummit
                (
                    $summit,
                    new PagingInfo($page, $per_page),
                    call_user_func($applyExtraFilters, $filter),
                    $order
                );
            },
            ["summit" => $summit]
        );
    }

    #[OA\Get(
        path: "/api/v1/summits/{id}/submitters/csv",
        description: "required-groups " . IGroup::SummitAdministrators . ", " . IGroup::SuperAdmins . ", " . IGroup::Administrators,
        summary: "Get all submitters for a summit in CSV format",
        operationId: "getSubmittersCSV",
        tags: ["SummitSubmitters"],
        security: [['summit_submitters_oauth2' => [
            SummitScopes::ReadSummitData,
            SummitScopes::ReadAllSummitData,
        ]]],
        x: [
            'required-groups' => [
                IGroup::SummitAdministrators,
                IGroup::SuperAdmins,
                IGroup::Administrators,
            ]
        ],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "Summit ID or slug",
                schema: new OA\Schema(type: "string")
            ),
            new OA\Parameter(
                name: "filter",
                in: "query",
                required: false,
                description: "Filter query to select specific submitters",
                schema: new OA\Schema(type: "string", example: "first_name=@John")
            ),
            new OA\Parameter(
                name: "order",
                in: "query",
                required: false,
                description: "Order by field (prefix with - for descending)",
                schema: new OA\Schema(type: "string", example: "first_name,-created")
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "CSV file with submitter data",
                content: new OA\MediaType(
                    mediaType: "text/csv",
                    schema: new OA\Schema(type: "string", format: "binary")
                )
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: "Bad Request"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Summit not found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    public function getAllBySummitCSV($summit_id)
    {
        $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->getResourceServerContext())->find(intval($summit_id));
        if (is_null($summit)) return $this->error404();

        return $this->_getAllCSV(
            function () {
                return [
                    'id' => ['=='],
                    'not_id' => ['=='],
                    'first_name' => ['=@', '@@', '=='],
                    'last_name' => ['=@', '@@', '=='],
                    'email' => ['=@', '@@', '=='],
                    'full_name' => ['=@', '@@', '=='],
                    'member_id' => ['=='],
                    'member_user_external_id' => ['=='],
                    'has_accepted_presentations' => ['=='],
                    'has_alternate_presentations' => ['=='],
                    'has_rejected_presentations' => ['=='],
                    'presentations_track_id' => ['=='],
                    'presentations_selection_plan_id' => ['=='],
                    'presentations_type_id' => ['=='],
                    'presentations_title' => ['=@', '@@', '=='],
                    'presentations_abstract' => ['=@', '@@', '=='],
                    'presentations_submitter_full_name' => ['=@', '@@', '=='],
                    'presentations_submitter_email' => ['=@', '@@', '=='],
                    'is_speaker' => ['=='],
                    'has_media_upload_with_type' => ['=='],
                    'has_not_media_upload_with_type' => ['=='],
                ];
            },
            function () {
                return [
                    'id' => 'sometimes|integer',
                    'not_id' => 'sometimes|integer',
                    'first_name' => 'sometimes|string',
                    'last_name' => 'sometimes|string',
                    'email' => 'sometimes|string',
                    'full_name' => 'sometimes|string',
                    'member_id' => 'sometimes|integer',
                    'member_user_external_id' => 'sometimes|integer',
                    'has_accepted_presentations' => 'sometimes|string|in:true,false',
                    'has_alternate_presentations' => 'sometimes|string|in:true,false',
                    'has_rejected_presentations' => 'sometimes|string|in:true,false',
                    'presentations_track_id' => 'sometimes|integer',
                    'presentations_selection_plan_id' => 'sometimes|integer',
                    'presentations_type_id' => 'sometimes|integer',
                    'presentations_title' => 'sometimes|string',
                    'presentations_abstract' => 'sometimes|string',
                    'presentations_submitter_full_name' => 'sometimes|string',
                    'presentations_submitter_email' => 'sometimes|string',
                    'is_speaker' => 'sometimes|string|in:true,false',
                    'has_media_upload_with_type' => 'sometimes|integer',
                    'has_not_media_upload_with_type' => 'sometimes|integer',
                ];
            },
            function () {
                return [
                    'id',
                    'first_name',
                    'last_name',
                    'full_name',
                    'email',
                    'created',
                    'last_edited',
                ];
            },
            function ($filter) use ($summit) {
                return $filter;
            },
            function () {
                return IMemberSerializerTypes::SubmitterCSV;
            },
            function () {
                return [];
            },
            function () {
                return [];
            },
            'submitters-',
            ["summit" => $summit],
            function ($page, $per_page, $filter, $order, $applyExtraFilters) use ($summit) {
                return $this->repository->getSubmittersBySummit
                (
                    $summit,
                    new PagingInfo($page, $per_page),
                    call_user_func($applyExtraFilters, $filter),
                    $order
                );
            }
        );
    }

    #[OA\Put(
        path: "/api/v1/summits/{id}/submitters/all/send",
        description: "required-groups " . IGroup::SummitAdministrators . ", " . IGroup::SuperAdmins . ", " . IGroup::Administrators . ", " . IGroup::SummitRegistrationAdmins,
        summary: "Send bulk emails to submitters",
        operationId: "sendSubmittersBulkEmails",
        tags: ["SummitSubmitters"],
        security: [['summit_submitters_oauth2' => [
            SummitScopes::WriteSummitData,
            SummitScopes::WriteSpeakersData,
        ]]],
        x: [
            'required-groups' => [
                IGroup::SummitAdministrators,
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitRegistrationAdmins,
            ]
        ],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "Summit ID or slug",
                schema: new OA\Schema(type: "string")
            ),
            new OA\Parameter(
                name: "filter",
                in: "query",
                required: false,
                description: "Filter query to select specific submitters",
                schema: new OA\Schema(type: "string", example: "has_accepted_presentations==true")
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: "#/components/schemas/SendSubmittersEmailsRequest")
        ),
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "Emails sent successfully"
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: "Bad Request"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Summit not found"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    public function send($summit_id)
    {
        return $this->processRequest(function () use ($summit_id) {

            if (!Request::isJson()) return $this->error400();

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->getResourceServerContext())->find(intval($summit_id));
            if (is_null($summit)) return $this->error404();

            $payload = $this->getJsonPayload(SummitSubmittersEmailsValidationRulesFactory::buildForAdd());

            $filter = null;

            if (Request::has('filter')) {
                $filter = FilterParser::parse(Request::input('filter'), [
                    'id' => ['=='],
                    'not_id' => ['=='],
                    'first_name' => ['=@', '@@', '=='],
                    'last_name' => ['=@', '@@', '=='],
                    'email' => ['=@', '@@', '=='],
                    'full_name' => ['=@', '@@', '=='],
                    'member_id' => ['=='],
                    'member_user_external_id' => ['=='],
                    'has_accepted_presentations' => ['=='],
                    'has_alternate_presentations' => ['=='],
                    'has_rejected_presentations' => ['=='],
                    'presentations_track_id' => ['=='],
                    'presentations_selection_plan_id' => ['=='],
                    'presentations_type_id' => ['=='],
                    'presentations_title' => ['=@', '@@', '=='],
                    'presentations_abstract' => ['=@', '@@', '=='],
                    'presentations_submitter_full_name' => ['=@', '@@', '=='],
                    'presentations_submitter_email' => ['=@', '@@', '=='],
                    'is_speaker' => ['=='],
                    'has_media_upload_with_type' => ['=='],
                    'has_not_media_upload_with_type' => ['=='],
                ]);
            }

            if (is_null($filter))
                $filter = new Filter();

            $filter->validate([
                'id' => 'sometimes|integer',
                'not_id' => 'sometimes|integer',
                'first_name' => 'sometimes|string',
                'last_name' => 'sometimes|string',
                'email' => 'sometimes|string',
                'full_name' => 'sometimes|string',
                'member_id' => 'sometimes|integer',
                'member_user_external_id' => 'sometimes|integer',
                'has_accepted_presentations' => 'sometimes|string|in:true,false',
                'has_alternate_presentations' => 'sometimes|string|in:true,false',
                'has_rejected_presentations' => 'sometimes|string|in:true,false',
                'presentations_track_id' => 'sometimes|integer',
                'presentations_selection_plan_id' => 'sometimes|integer',
                'presentations_type_id' => 'sometimes|integer',
                'presentations_title' => 'sometimes|string',
                'presentations_abstract' => 'sometimes|string',
                'presentations_submitter_full_name' => 'sometimes|string',
                'presentations_submitter_email' => 'sometimes|string',
                'is_speaker' => 'sometimes|string|in:true,false',
                'has_media_upload_with_type' => 'sometimes|integer',
                'has_not_media_upload_with_type' => 'sometimes|integer',
            ]);

            $this->service->triggerSendEmails($summit, $payload, Request::input('filter', null));

            return $this->ok();
        });
    }
}