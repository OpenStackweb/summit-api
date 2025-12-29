<?php namespace App\Http\Controllers;
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

use App\Models\Foundation\Elections\Election;
use App\Models\Foundation\Elections\IElectionsRepository;
use App\ModelSerializers\SerializerUtils;
use App\Security\ElectionScopes;
use App\Services\Model\IElectionService;
use libs\utils\HTMLCleaner;
use models\oauth2\IResourceServerContext;
use ModelSerializers\SerializerRegistry;
use utils\Filter;
use utils\PagingInfo;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class OAuth2ElectionsApiController
 * @package App\Http\Controllers
 */
class OAuth2ElectionsApiController extends OAuth2ProtectedController
{

    use ParametrizedGetAll;

    use RequestProcessor;

    /**
     * @var IElectionService
     */
    private $service;

    public function __construct
    (
        IElectionsRepository   $repository,
        IElectionService       $service,
        IResourceServerContext $resource_server_context
    )
    {
        parent::__construct($resource_server_context);
        $this->repository = $repository;
        $this->service = $service;
    }

    #[OA\Get(
        path: "/api/v1/elections",
        operationId: "getAllElections",
        description: "Get all elections with pagination and filtering",
        tags: ["Elections"],
        security: [['election_oauth2' => [ElectionScopes::ReadAllElections]]],
        parameters: [
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
                schema: new OA\Schema(type: "integer", default: 20),
                description: "Items per page"
            ),
            new OA\Parameter(
                name: "filter",
                in: "query",
                required: false,
                schema: new OA\Schema(type: "string"),
                description: "Filter by name, opens, or closes (epoch format). Example: name[]=Test&opens[from]=1634567890"
            ),
            new OA\Parameter(
                name: "order",
                in: "query",
                required: false,
                schema: new OA\Schema(type: "string"),
                description: "Order by: name, id, opens, or closes. Example: name,-opens"
            ),
            new OA\Parameter(
                name: "expand",
                in: "query",
                required: false,
                schema: new OA\Schema(type: "string"),
                description: "Expand relationships: "
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "Elections list retrieved successfully",
                content: new OA\JsonContent(ref: "#/components/schemas/PaginatedElectionsResponse")
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: "Bad Request"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    public function getAll()
    {
        return $this->_getAll(
            function () {
                return [
                    'name' => Filter::buildStringDefaultOperators(),
                    'opens' => Filter::buildEpochDefaultOperators(),
                    'closes' => Filter::buildEpochDefaultOperators(),
                ];
            },
            function () {
                return [
                    'name' => 'sometimes|string',
                    'opens' => 'sometimes|date_format:U|epoch_seconds',
                    'closes' => 'sometimes|date_format:U|epoch_seconds',
                ];
            },
            function () {
                return [
                    'name',
                    'id',
                    'opens',
                    'closes',
                ];
            },
            function ($filter) {
                return $filter;
            },
            function () {
                return SerializerRegistry::SerializerType_Private;
            },
            null,
            null,
            function ($page, $per_page, $filter, $order, $applyExtraFilters) {
                return $this->repository->getAllByPage
                (
                    new PagingInfo($page, $per_page),
                    call_user_func($applyExtraFilters, $filter),
                    $order
                );
            }
        );
    }

    #[OA\Get(
        path: "/api/public/v1/elections/current",
        operationId: "getCurrentElection",
        description: "Get the current active election",
        tags: ["Elections (Public)"],
        parameters: [
            new OA\Parameter(
                name: "expand",
                in: "query",
                required: false,
                schema: new OA\Schema(type: "string"),
                description: "Expand relationships"
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "Current election retrieved successfully",
                content: new OA\JsonContent(ref: "#/components/schemas/Election")
            ),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "No current election found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    public function getCurrent()
    {
        return $this->processRequest(function () {
            $election = $this->repository->getCurrent();
            if (!$election instanceof Election)
                return $this->error404();

            return $this->ok
            (
                SerializerRegistry::getInstance()
                    ->getSerializer($election)
                    ->serialize
                    (
                        SerializerUtils::getExpand(),
                        SerializerUtils::getFields(),
                        SerializerUtils::getRelations()
                    )
            );

        });
    }

    #[OA\Get(
        path: "/api/v1/elections/{election_id}",
        operationId: "getElectionById",
        description: "Get election by ID",
        tags: ["Elections"],
        security: [['election_oauth2' => [ElectionScopes::ReadAllElections]]],
        parameters: [
            new OA\Parameter(
                name: "election_id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer", format: "int64"),
                description: "Election ID"
            ),
            new OA\Parameter(
                name: "expand",
                in: "query",
                required: false,
                schema: new OA\Schema(type: "string"),
                description: "Expand relationships"
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "Election retrieved successfully",
                content: new OA\JsonContent(ref: "#/components/schemas/Election")
            ),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Election not found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    public function getById($election_id)
    {
        return $this->processRequest(function () use ($election_id) {
            $election = $this->repository->getById(intval($election_id));
            if (!$election instanceof Election)
                return $this->error404();

            return $this->ok
            (
                SerializerRegistry::getInstance()
                    ->getSerializer($election)
                    ->serialize
                    (
                        SerializerUtils::getExpand(),
                        SerializerUtils::getFields(),
                        SerializerUtils::getRelations()
                    )
            );

        });
    }

    #[OA\Get(
        path: "/api/public/v1/elections/current/candidates",
        operationId: "getCurrentElectionCandidates",
        description: "Get all accepted candidates for the current election. Supports expand parameter to include member and/or election objects",
        tags: ["Elections (Public)"],
        parameters: [
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
                schema: new OA\Schema(type: "integer", default: 20),
                description: "Items per page"
            ),
            new OA\Parameter(
                name: "filter",
                in: "query",
                required: false,
                schema: new OA\Schema(type: "string"),
                description: "Filter by first_name, last_name, or full_name"
            ),
            new OA\Parameter(
                name: "order",
                in: "query",
                required: false,
                schema: new OA\Schema(type: "string"),
                description: "Order by first_name or last_name"
            ),
            new OA\Parameter(
                name: "expand",
                in: "query",
                required: false,
                schema: new OA\Schema(type: "string"),
                description: "Expand relationships: member, election (comma-separated)"
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "Current election candidates retrieved successfully",
                content: new OA\JsonContent(ref: "#/components/schemas/PaginatedCandidatesResponse")
            ),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "No current election found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    public function getCurrentCandidates()
    {

        $election = $this->repository->getCurrent();
        if (!$election instanceof Election)
            return $this->error404();

        return $this->_getAll(
            function () {
                return [
                    'first_name' => ['=@', '=='],
                    'last_name' => ['=@', '=='],
                    'full_name' => ['=@', '=='],
                ];
            },
            function () {
                return [
                    'first_name' => 'sometimes|string',
                    'last_name' => 'sometimes|string',
                    'full_name' => 'sometimes|string',
                ];
            },
            function () {
                return [
                    'first_name',
                    'last_name',
                ];
            },
            function ($filter) use ($election) {
                return $filter;
            },
            function () {
                return SerializerRegistry::SerializerType_Public;
            },
            null,
            null,
            function ($page, $per_page, $filter, $order, $applyExtraFilters) use ($election) {
                return $this->repository->getAcceptedCandidates
                (
                    $election,
                    new PagingInfo($page, $per_page),
                    call_user_func($applyExtraFilters, $filter),
                    $order
                );
            }
        );
    }

    #[OA\Get(
        path: "/api/v1/elections/{election_id}/candidates",
        operationId: "getElectionCandidates",
        description: "Get all accepted candidates for a specific election. Supports expand parameter to include member and/or election objects",
        tags: ["Elections"],
        security: [['election_oauth2' => [ElectionScopes::ReadAllElections]]],
        parameters: [
            new OA\Parameter(
                name: "election_id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer", format: "int64"),
                description: "Election ID"
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
                schema: new OA\Schema(type: "integer", default: 20),
                description: "Items per page"
            ),
            new OA\Parameter(
                name: "filter",
                in: "query",
                required: false,
                schema: new OA\Schema(type: "string"),
                description: "Filter by first_name, last_name, or full_name"
            ),
            new OA\Parameter(
                name: "order",
                in: "query",
                required: false,
                schema: new OA\Schema(type: "string"),
                description: "Order by first_name or last_name"
            ),
            new OA\Parameter(
                name: "expand",
                in: "query",
                required: false,
                schema: new OA\Schema(type: "string"),
                description: "Expand relationships: member, election (comma-separated)"
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "Election candidates retrieved successfully",
                content: new OA\JsonContent(ref: "#/components/schemas/PaginatedCandidatesResponse")
            ),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Election not found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    public function getElectionCandidates(int $election_id)
    {

        $election = $this->repository->getById(intval($election_id));
        if (!$election instanceof Election)
            return $this->error404();

        return $this->_getAll(
            function () {
                return [
                    'first_name' => ['=@', '=='],
                    'last_name' => ['=@', '=='],
                    'full_name' => ['=@', '=='],
                ];
            },
            function () {
                return [
                    'first_name' => 'sometimes|string',
                    'last_name' => 'sometimes|string',
                    'full_name' => 'sometimes|string',
                ];
            },
            function () {
                return [
                    'first_name',
                    'last_name',
                ];
            },
            function ($filter) use ($election) {
                return $filter;
            },
            function () {
                return SerializerRegistry::SerializerType_Public;
            },
            null,
            null,
            function ($page, $per_page, $filter, $order, $applyExtraFilters) use ($election) {
                return $this->repository->getAcceptedCandidates
                (
                    $election,
                    new PagingInfo($page, $per_page),
                    call_user_func($applyExtraFilters, $filter),
                    $order
                );
            }
        );
    }

    #[OA\Get(
        path: "/api/public/v1/elections/current/candidates/gold",
        operationId: "getCurrentGoldCandidates",
        description: "Get all gold (featured) candidates for the current election. Supports expand parameter to include member and/or election objects",
        tags: ["Elections (Public)"],
        parameters: [
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
                schema: new OA\Schema(type: "integer", default: 20),
                description: "Items per page"
            ),
            new OA\Parameter(
                name: "filter",
                in: "query",
                required: false,
                schema: new OA\Schema(type: "string"),
                description: "Filter by first_name, last_name, or full_name"
            ),
            new OA\Parameter(
                name: "order",
                in: "query",
                required: false,
                schema: new OA\Schema(type: "string"),
                description: "Order by first_name or last_name"
            ),
            new OA\Parameter(
                name: "expand",
                in: "query",
                required: false,
                schema: new OA\Schema(type: "string"),
                description: "Expand relationships: member, election (comma-separated)"
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "Gold candidates retrieved successfully",
                content: new OA\JsonContent(ref: "#/components/schemas/PaginatedCandidatesResponse")
            ),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "No current election found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    public function getCurrentGoldCandidates()
    {

        $election = $this->repository->getCurrent();
        if (!$election instanceof Election)
            return $this->error404();

        return $this->_getAll(
            function () {
                return [
                    'first_name' => ['=@', '=='],
                    'last_name' => ['=@', '=='],
                    'full_name' => ['=@', '=='],
                ];
            },
            function () {
                return [
                    'first_name' => 'sometimes|string',
                    'last_name' => 'sometimes|string',
                    'full_name' => 'sometimes|string',
                ];
            },
            function () {
                return [
                    'first_name',
                    'last_name'
                ];
            },
            function ($filter) use ($election) {
                return $filter;
            },
            function () {
                return SerializerRegistry::SerializerType_Public;
            },
            null,
            null,
            function ($page, $per_page, $filter, $order, $applyExtraFilters) use ($election) {
                return $this->repository->getGoldCandidates
                (
                    $election,
                    new PagingInfo($page, $per_page),
                    call_user_func($applyExtraFilters, $filter),
                    $order
                );
            }
        );
    }

    #[OA\Get(
        path: "/api/v1/elections/{election_id}/candidates/gold",
        operationId: "getElectionGoldCandidates",
        description: "Get all gold (featured) candidates for a specific election. Supports expand parameter to include member and/or election objects",
        tags: ["Elections"],
        security: [['election_oauth2' => [ElectionScopes::ReadAllElections]]],
        parameters: [
            new OA\Parameter(
                name: "election_id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer", format: "int64"),
                description: "Election ID"
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
                schema: new OA\Schema(type: "integer", default: 20),
                description: "Items per page"
            ),
            new OA\Parameter(
                name: "filter",
                in: "query",
                required: false,
                schema: new OA\Schema(type: "string"),
                description: "Filter by first_name, last_name, or full_name"
            ),
            new OA\Parameter(
                name: "order",
                in: "query",
                required: false,
                schema: new OA\Schema(type: "string"),
                description: "Order by first_name or last_name"
            ),
            new OA\Parameter(
                name: "expand",
                in: "query",
                required: false,
                schema: new OA\Schema(type: "string"),
                description: "Expand relationships: member, election (comma-separated)"
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "Gold candidates retrieved successfully",
                content: new OA\JsonContent(ref: "#/components/schemas/PaginatedCandidatesResponse")
            ),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Election not found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    public function getElectionGoldCandidates($election_id)
    {

        $election = $this->repository->getById(intval($election_id));
        if (!$election instanceof Election)
            return $this->error404();

        return $this->_getAll(
            function () {
                return [
                    'first_name' => ['=@', '=='],
                    'last_name' => ['=@', '=='],
                    'full_name' => ['=@', '=='],
                ];
            },
            function () {
                return [
                    'first_name' => 'sometimes|string',
                    'last_name' => 'sometimes|string',
                    'full_name' => 'sometimes|string',
                ];
            },
            function () {
                return [
                    'first_name',
                    'last_name'
                ];
            },
            function ($filter) use ($election) {
                return $filter;
            },
            function () {
                return SerializerRegistry::SerializerType_Public;
            },
            null,
            null,
            function ($page, $per_page, $filter, $order, $applyExtraFilters) use ($election) {
                return $this->repository->getGoldCandidates
                (
                    $election,
                    new PagingInfo($page, $per_page),
                    call_user_func($applyExtraFilters, $filter),
                    $order
                );
            }
        );
    }

    #[OA\Put(
        path: "/api/v1/elections/current/candidates/me",
        operationId: "updateMyCandidateProfile",
        description: "Update current user's candidate profile for the current election",
        tags: ["Elections"],
        security: [['election_oauth2' => [ElectionScopes::WriteMyCandidateProfile]]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: "#/components/schemas/CandidateProfileUpdateRequest")
        ),
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "Candidate profile updated successfully",
                content: new OA\JsonContent(ref: "#/components/schemas/Member")
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: "Bad Request"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden - User not authenticated"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "No current election found"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    public function updateMyCandidateProfile()
    {
        return $this->processRequest(function () {
            $current_member = $this->resource_server_context->getCurrentUser();
            if (is_null($current_member)) return $this->error403();

            $election = $this->repository->getCurrent();
            if (!$election instanceof Election)
                return $this->error404();

            $payload = $this->getJsonPayload([
                'bio' => 'sometimes|string',
                'relationship_to_openstack' => 'sometimes|string',
                'experience' => 'sometimes|string',
                'boards_role' => 'sometimes|string',
                'top_priority' => 'sometimes|string'
            ]);

            $member = $this->service->updateCandidateProfile($current_member, $election,
                HTMLCleaner::cleanData($payload, [
                    'bio',
                    'relationship_to_openstack',
                    'experience',
                    'boards_role',
                    'top_priority'
                ])
            );

            return $this->updated(SerializerRegistry::getInstance()
                ->getSerializer($member, SerializerRegistry::SerializerType_Private)
                ->serialize(
                    SerializerUtils::getExpand(),
                    SerializerUtils::getFields(),
                    SerializerUtils::getRelations()
                )
            );
        });
    }

    #[OA\Post(
        path: "/api/v1/elections/current/candidates/{candidate_id}",
        operationId: "nominateCandidate",
        description: "Nominate a candidate for the current election. Supports expand parameter to include related objects (election, candidate, nominator)",
        tags: ["Elections"],
        security: [['election_oauth2' => [ElectionScopes::NominatesCandidates]]],
        parameters: [
            new OA\Parameter(
                name: "candidate_id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer", format: "int64"),
                description: "Candidate ID to nominate"
            ),
            new OA\Parameter(
                name: "expand",
                in: "query",
                required: false,
                schema: new OA\Schema(type: "string"),
                description: "Expand relationships: election, candidate, nominator (comma-separated)"
            ),
        ],
        requestBody: new OA\RequestBody(
            required: false,
            content: new OA\JsonContent(ref: "#/components/schemas/NominationRequest")
        ),
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "Candidate nominated successfully",
                content: new OA\JsonContent(ref: "#/components/schemas/Nomination")
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: "Bad Request"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden - User not authenticated"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Candidate or current election not found"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    public function nominateCandidate(int $candidate_id)
    {
        return $this->processRequest(function () use ($candidate_id) {
            $current_member = $this->resource_server_context->getCurrentUser();
            if (is_null($current_member)) return $this->error403();

            $election = $this->repository->getCurrent();
            if (!$election instanceof Election)
                return $this->error404();

            $nomination = $this->service->nominateCandidate($current_member, intval($candidate_id), $election);

            return $this->updated(SerializerRegistry::getInstance()
                ->getSerializer($nomination)
                ->serialize(
                    SerializerUtils::getExpand(),
                    SerializerUtils::getFields(),
                    SerializerUtils::getRelations()
                )
            );

        });
    }
}
