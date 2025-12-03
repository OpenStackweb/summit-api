<?php
namespace App\Http\Controllers;
/**
 * Copyright 2016 OpenStack Foundation
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

use App\Models\Foundation\Main\IGroup;
use App\ModelSerializers\SerializerUtils;
use App\Security\MemberScopes;
use App\Services\Model\IMemberService;
use Illuminate\Http\Response;
use models\exceptions\EntityNotFoundException;
use models\main\IMemberRepository;
use models\main\Member;
use models\oauth2\IResourceServerContext;
use ModelSerializers\SerializerRegistry;
use OpenApi\Attributes as OA;
use utils\PagingInfo;
use utils\PagingResponse;

/**
 * Class OAuth2MembersApiController
 * @package App\Http\Controllers
 */
final class OAuth2MembersApiController extends OAuth2ProtectedController
{
    /**
     * @var IMemberService
     */
    private $member_service;

    use RequestProcessor;

    use ParametrizedGetAll;

    /**
     * OAuth2MembersApiController constructor.
     * @param IMemberRepository $member_repository
     * @param IMemberService $member_service
     * @param IResourceServerContext $resource_server_context
     */
    public function __construct
    (
        IMemberRepository $member_repository,
        IMemberService $member_service,
        IResourceServerContext $resource_server_context
    ) {
        parent::__construct($resource_server_context);
        $this->repository = $member_repository;
        $this->member_service = $member_service;
    }

    #[OA\Get(
        path: '/api/public/v1/members',
        operationId: 'getAllMembersPublic',
        summary: 'Get all members',
        description: 'Returns a paginated list of members with optional filtering, sorting and search capabilities',
        tags: ['Members (Public)'],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', required: false, description: 'Page number', schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, description: 'Items per page', schema: new OA\Schema(type: 'integer', default: 10, maximum: 100)),
            new OA\Parameter(name: 'filter', in: 'query', required: false, description: 'Filter by irc, twitter, first_name, last_name, email, group_slug, group_id, email_verified, active, github_user, full_name, created, last_edited, membership_type', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'order', in: 'query', required: false, description: 'Order by first_name, last_name, id, created, last_edited, membership_type', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'expand', in: 'query', required: false, description: 'Expand relationships', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Successful operation',
                content: new OA\JsonContent(ref: '#/components/schemas/PaginatedMembersResponse')
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: 'Bad request'),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Unauthorized'),
        ]
    )]
    #[OA\Get(
        path: '/api/v1/members',
        operationId: 'getAllMembers',
        summary: 'Get all members',
        description: 'Returns a paginated list of members with optional filtering, sorting and search capabilities',
        tags: ['Members'],
        security: [['members_oauth2' => [
            MemberScopes::ReadMemberData,
        ]]],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', required: false, description: 'Page number', schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, description: 'Items per page', schema: new OA\Schema(type: 'integer', default: 10, maximum: 100)),
            new OA\Parameter(name: 'filter', in: 'query', required: false, description: 'Filter by irc, twitter, first_name, last_name, email, group_slug, group_id, email_verified, active, github_user, full_name, created, last_edited, membership_type', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'order', in: 'query', required: false, description: 'Order by first_name, last_name, id, created, last_edited, membership_type', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'expand', in: 'query', required: false, description: 'Expand relationships', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Successful operation',
                content: new OA\JsonContent(ref: '#/components/schemas/PaginatedMembersResponse')
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: 'Bad request'),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Unauthorized'),
        ]
    )]
    public function getAll()
    {

        $current_member = $this->resource_server_context->getCurrentUser();
        $application_type = $this->resource_server_context->getApplicationType();

        return $this->_getAll(
            function () {
                return [
                    'irc' => ['=@', '==', '@@'],
                    'twitter' => ['=@', '==', '@@'],
                    'first_name' => ['=@', '==', '@@'],
                    'last_name' => ['=@', '==', '@@'],
                    'email' => ['=@', '==', '@@'],
                    'group_slug' => ['=@', '==', '@@'],
                    'group_id' => ['=='],
                    'email_verified' => ['=='],
                    'active' => ['=='],
                    'github_user' => ['=@', '==', '@@'],
                    'full_name' => ['=@', '==', '@@'],
                    'created' => ['>', '<', '<=', '>=', '==', '[]'],
                    'last_edited' => ['>', '<', '<=', '>=', '==', '[]'],
                    'membership_type' => ['==', '=@', '@@'],
                ];
            },
            function () {
                return [
                    'irc' => 'sometimes|required|string',
                    'twitter' => 'sometimes|required|string',
                    'first_name' => 'sometimes|required|string',
                    'last_name' => 'sometimes|required|string',
                    'email' => 'sometimes|required|string',
                    'group_slug' => 'sometimes|required|string',
                    'group_id' => 'sometimes|required|integer',
                    'email_verified' => 'sometimes|required|boolean',
                    'active' => 'sometimes|required|boolean',
                    'github_user' => 'sometimes|required|string',
                    'full_name' => 'sometimes|required|string',
                    'created' => 'sometimes|required|date_format:U|epoch_seconds',
                    'last_edited' => 'sometimes|required|date_format:U|epoch_seconds',
                    'membership_type' => 'sometimes|required|string|in:' . implode(',', Member::AllowedMembershipTypes),
                ];
            },
            function () {
                return [
                    'first_name',
                    'last_name',
                    'id',
                    'created',
                    'last_edited',
                    'membership_type',
                ];
            },
            function ($filter) {
                return $filter;
            },
            function () use ($current_member, $application_type) {
                $serializer_type = SerializerRegistry::SerializerType_Public;

                if ($application_type == IResourceServerContext::ApplicationType_Service || (!is_null($current_member) && ($current_member->isAdmin() || $current_member->isSummitAdmin() || $current_member->isTrackChairAdmin()))) {
                    $serializer_type = SerializerRegistry::SerializerType_Admin;
                }
                return $serializer_type;
            }
        );
    }

    #[OA\Get(
        path: '/api/public/v1/members/all/companies',
        operationId: 'getAllMemberCompanies',
        summary: 'Get all member companies',
        description: 'Returns a paginated list of companies from member profiles',
        tags: ['Members (Public)'],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', required: false, description: 'Page number', schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, description: 'Items per page', schema: new OA\Schema(type: 'integer', default: 10, maximum: 100)),
            new OA\Parameter(name: 'filter', in: 'query', required: false, description: 'Filter by company', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'order', in: 'query', required: false, description: 'Order by company', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Successful operation',
                content: new OA\JsonContent(ref: '#/components/schemas/PaginatedMemberCompaniesResponse')
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: 'Bad request'),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Unauthorized'),
        ]
    )]
    public function getAllCompanies()
    {
        return $this->_getAll(
            function () {
                return [
                    'company' => ['=@', '@@'],
                ];
            },
            function () {
                return [
                    'company' => 'sometimes|string',
                ];
            },
            function () {
                return [
                    'company',
                ];
            },
            function ($filter) {
                return $filter;
            },
            function () {
                return SerializerRegistry::SerializerType_Public;
            },
            null,
            null,
            function ($page, $per_page, $filter, $order, $applyExtraFilters) {
                return $this->repository->getAllCompaniesByPage
                (
                    new PagingInfo($page, $per_page),
                    call_user_func($applyExtraFilters, $filter),
                    $order
                );
            }
        );
    }
    #[OA\Get(
        path: '/api/v1/members/me',
        operationId: 'getCurrentMember',
        summary: 'Get current authenticated member',
        description: 'Returns the profile of the currently authenticated member',
        tags: ['Members'],
        security: [['members_oauth2' => [
            MemberScopes::ReadMyMemberData,
        ]]],
        parameters: [
            new OA\Parameter(name: 'expand', in: 'query', required: false, description: 'Expand relationships (groups, affiliations, all_affiliations, ccla_teams, election_applications, candidate_profile, election_nominations)', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Successful operation',
                content: new OA\JsonContent(ref: '#/components/schemas/Member')
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Unauthorized'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Member not found'),
        ]
    )]
    public function getMyMember()
    {
        return $this->processRequest(function () {

            $current_member = $this->resource_server_context->getCurrentUser();

            if (is_null($current_member))
                throw new EntityNotFoundException();

            return $this->ok
            (
                SerializerRegistry::getInstance()
                    ->getSerializer($current_member, SerializerRegistry::SerializerType_Private)
                    ->serialize
                    (
                        SerializerUtils::getExpand(),
                        SerializerUtils::getFields(),
                        SerializerUtils::getRelations()
                    )
            );

        });
    }

    use GetAndValidateJsonPayload;

    #[OA\Put(
        path: '/api/v1/members/me',
        operationId: 'updateCurrentMember',
        summary: 'Update current authenticated member',
        description: 'Updates the profile of the currently authenticated member',
        tags: ['Members'],
        security: [['members_oauth2' => [
            MemberScopes::WriteMyMemberData,
        ]]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/MemberUpdateRequest')
        ),
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Member updated successfully',
                content: new OA\JsonContent(ref: '#/components/schemas/Member')
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: 'Invalid input'),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Unauthorized'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Member not found'),
        ]
    )]
    public function updateMyMember()
    {
        return $this->processRequest(function () {

            $member = $this->resource_server_context->getCurrentUser();

            if (is_null($member))
                return $this->error404();

            $payload = $this->getJsonPayload([
                'projects' => 'sometimes|string_array',
                'other_project' => 'sometimes|string|max:100',
                'display_on_site' => 'sometimes|boolean',
                'subscribed_to_newsletter' => 'sometimes|boolean',
                'shirt_size' => 'sometimes|string|in:' . implode(',', Member::AllowedShirtSizes),
                'food_preference' => 'sometimes|string_array',
                'other_food_preference' => 'sometimes|string|max:100'
            ], true);

            $me = $this->member_service->updateMyMember($member, $payload);

            return $this->updated(SerializerRegistry::getInstance()
                ->getSerializer($me, SerializerRegistry::SerializerType_Private)->serialize
                (
                    SerializerUtils::getExpand(),
                    SerializerUtils::getFields(),
                    SerializerUtils::getRelations()
                ));
        });
    }

    #[OA\Get(
        path: '/api/public/v1/members/{member_id}',
        operationId: 'getMemberById',
        summary: 'Get member by ID',
        description: 'Returns a member profile by ID',
        tags: ['Members (Public)'],
        parameters: [
            new OA\Parameter(name: 'member_id', in: 'path', required: true, description: 'Member ID', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'expand', in: 'query', required: false, description: 'Expand relationships', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Successful operation',
                content: new OA\JsonContent(ref: '#/components/schemas/Member')
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Unauthorized'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Member not found'),
        ]
    )]
    public function getById($member_id)
    {
        return $this->processRequest(function () use ($member_id) {
            $member = $this->repository->getById(intval($member_id));
            if (is_null($member))
                throw new EntityNotFoundException();

            $current_member = $this->resource_server_context->getCurrentUser();
            $application_type = $this->resource_server_context->getApplicationType();
            $serializer_type = SerializerRegistry::SerializerType_Public;

            if ($application_type == IResourceServerContext::ApplicationType_Service || (!is_null($current_member) && ($current_member->isAdmin() || $current_member->isSummitAdmin() || $current_member->isTrackChairAdmin()))) {
                $serializer_type = SerializerRegistry::SerializerType_Admin;
            }

            return $this->ok
            (
                SerializerRegistry::getInstance()
                    ->getSerializer($member, $serializer_type)
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
        path: '/api/v1/members/me/affiliations',
        operationId: 'getCurrentMemberAffiliations',
        summary: 'Get current member affiliations',
        description: 'Returns all affiliations for the currently authenticated member',
        tags: ['Members'],
        security: [['members_oauth2' => [
            MemberScopes::ReadMyMemberData,
        ]]],
        parameters: [
            new OA\Parameter(name: 'expand', in: 'query', required: false, description: 'Expand relationships (organization)', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Successful operation',
                content: new OA\JsonContent(ref: '#/components/schemas/PaginatedAffiliationsResponse')
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Unauthorized'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Member not found'),
        ]
    )]
    public function getMyMemberAffiliations()
    {
        return $this->getMemberAffiliations('me');
    }

    #[OA\Get(
        path: '/api/v1/members/{member_id}/affiliations',
        operationId: 'getMemberAffiliations',
        summary: 'Get member affiliations',
        description: 'Returns all affiliations for a specific member',
        tags: ['Members'],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
            ]
        ],
        security: [['members_oauth2' => [
            MemberScopes::ReadMemberData,
        ]]],
        parameters: [
            new OA\Parameter(name: 'member_id', in: 'path', required: true, description: 'Member ID', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'expand', in: 'query', required: false, description: 'Expand relationships (organization)', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Successful operation',
                content: new OA\JsonContent(ref: '#/components/schemas/PaginatedAffiliationsResponse')
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Unauthorized'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Member not found'),
        ]
    )]
    public function getMemberAffiliations($member_id)
    {
        return $this->processRequest(function () use ($member_id) {

            $member = (strtolower($member_id) == 'me') ?
                $this->resource_server_context->getCurrentUser() :
                $this->repository->getById($member_id);

            if (is_null($member))
                return $this->error404();
            $affiliations = $member->getAffiliations()->toArray();

            $response = new PagingResponse
            (
                count($affiliations),
                count($affiliations),
                1,
                1,
                $affiliations
            );

            return $this->ok($response->toArray(
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));

        });
    }

    #[OA\Post(
        path: '/api/v1/members/me/affiliations',
        operationId: 'addCurrentMemberAffiliation',
        summary: 'Add affiliation to current member',
        description: 'Creates a new affiliation for the currently authenticated member',
        tags: ['Members'],
        security: [['members_oauth2' => [
            MemberScopes::WriteMyMemberData,
        ]]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/AffiliationRequest')
        ),
        responses: [
            new OA\Response(
                response: Response::HTTP_CREATED,
                description: 'Affiliation created successfully',
                content: new OA\JsonContent(ref: '#/components/schemas/Affiliation')
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: 'Invalid input'),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Unauthorized'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Member not found'),
        ]
    )]
    public function addMyAffiliation()
    {
        return $this->addAffiliation('me');
    }

    #[OA\Post(
        path: '/api/v1/members/{member_id}/affiliations',
        operationId: 'addMemberAffiliation',
        summary: 'Add affiliation to member',
        description: 'Creates a new affiliation for a specific member',
        tags: ['Members'],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
            ]
        ],
        security: [['members_oauth2' => [
            MemberScopes::WriteMemberData,
        ]]],
        parameters: [
            new OA\Parameter(name: 'member_id', in: 'path', required: true, description: 'Member ID', schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/AffiliationRequest')
        ),
        responses: [
            new OA\Response(
                response: Response::HTTP_CREATED,
                description: 'Affiliation created successfully',
                content: new OA\JsonContent(ref: '#/components/schemas/Affiliation')
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: 'Invalid input'),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Unauthorized'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Member not found'),
        ]
    )]
    public function addAffiliation($member_id)
    {
        return $this->processRequest(function () use ($member_id) {

            $member = (strtolower($member_id) == 'me') ?
                $this->resource_server_context->getCurrentUser() :
                $this->repository->getById($member_id);

            if (is_null($member))
                return $this->error404();

            $payload = $this->getJsonPayload([
                'is_current' => 'required|boolean',
                'start_date' => 'required|date_format:U|epoch_seconds|valid_epoch',
                'end_date' => 'sometimes|after_or_null_epoch:start_date',
                'organization_id' => 'sometimes|integer|required_without:organization_name',
                'organization_name' => 'sometimes|string|max:255|required_without:organization_id',
                'job_title' => 'sometimes|string|max:255'
            ], true);

            $affiliation = $this->member_service->addAffiliation($member, $payload);

            return $this->created(SerializerRegistry::getInstance()->getSerializer($affiliation)->serialize
            (
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));
        });
    }

    #[OA\Put(
        path: '/api/v1/members/me/affiliations/{affiliation_id}',
        operationId: 'updateCurrentMemberAffiliation',
        summary: 'Update current member affiliation',
        description: 'Updates an affiliation for the currently authenticated member',
        tags: ['Members'],
        security: [['members_oauth2' => [
            MemberScopes::WriteMyMemberData,
        ]]],
        parameters: [
            new OA\Parameter(name: 'affiliation_id', in: 'path', required: true, description: 'Affiliation ID', schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/AffiliationRequest')
        ),
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Affiliation updated successfully',
                content: new OA\JsonContent(ref: '#/components/schemas/Affiliation')
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: 'Invalid input'),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Unauthorized'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Member or affiliation not found'),
        ]
    )]
    public function updateMyAffiliation($affiliation_id)
    {
        return $this->updateAffiliation('me', $affiliation_id);
    }

    #[OA\Put(
        path: '/api/v1/members/{member_id}/affiliations/{affiliation_id}',
        operationId: 'updateMemberAffiliation',
        summary: 'Update member affiliation',
        description: 'Updates an affiliation for a specific member',
        tags: ['Members'],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
            ]
        ],
        security: [['members_oauth2' => [
            MemberScopes::WriteMemberData,
        ]]],
        parameters: [
            new OA\Parameter(name: 'member_id', in: 'path', required: true, description: 'Member ID', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'affiliation_id', in: 'path', required: true, description: 'Affiliation ID', schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/AffiliationRequest')
        ),
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Affiliation updated successfully',
                content: new OA\JsonContent(ref: '#/components/schemas/Affiliation')
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: 'Invalid input'),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Unauthorized'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Member or affiliation not found'),
        ]
    )]
    public function updateAffiliation($member_id, $affiliation_id)
    {
        return $this->processRequest(function () use ($member_id, $affiliation_id) {

            $member = (strtolower($member_id) == 'me') ?
                $this->resource_server_context->getCurrentUser() :
                $this->repository->getById($member_id);

            if (is_null($member))
                return $this->error404();

            $payload = $this->getJsonPayload([
                'is_current' => 'sometimes|boolean',
                'start_date' => 'sometimes|date_format:U|epoch_seconds|valid_epoch',
                'end_date' => 'sometimes|after_or_null_epoch:start_date',
                'organization_id' => 'sometimes|integer',
                'organization_name' => 'sometimes|string|max:255',
                'job_title' => 'sometimes|string|max:255'
            ], true);

            $affiliation = $this->member_service->updateAffiliation($member, intval($affiliation_id), $payload);

            return $this->updated(SerializerRegistry::getInstance()->getSerializer($affiliation)->serialize(
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));
        });
    }


    #[OA\Delete(
        path: '/api/v1/members/me/affiliations/{affiliation_id}',
        operationId: 'deleteCurrentMemberAffiliation',
        summary: 'Delete current member affiliation',
        description: 'Deletes an affiliation for the currently authenticated member',
        tags: ['Members'],
        security: [['members_oauth2' => [
            MemberScopes::WriteMyMemberData,
        ]]],
        parameters: [
            new OA\Parameter(name: 'affiliation_id', in: 'path', required: true, description: 'Affiliation ID', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: Response::HTTP_NO_CONTENT, description: 'Affiliation deleted successfully'),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Unauthorized'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Member or affiliation not found'),
        ]
    )]
    public function deleteMyAffiliation($affiliation_id)
    {
        return $this->deleteAffiliation('me', $affiliation_id);
    }

    #[OA\Delete(
        path: '/api/v1/members/{member_id}/affiliations/{affiliation_id}',
        operationId: 'deleteMemberAffiliation',
        summary: 'Delete member affiliation',
        description: 'Deletes an affiliation for a specific member',
        tags: ['Members'],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
            ]
        ],
        security: [['members_oauth2' => [
            MemberScopes::WriteMemberData,
        ]]],
        parameters: [
            new OA\Parameter(name: 'member_id', in: 'path', required: true, description: 'Member ID', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'affiliation_id', in: 'path', required: true, description: 'Affiliation ID', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: Response::HTTP_NO_CONTENT, description: 'Affiliation deleted successfully'),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Unauthorized'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Member or affiliation not found'),
        ]
    )]
    public function deleteAffiliation($member_id, $affiliation_id)
    {
        return $this->processRequest(function () use ($member_id, $affiliation_id) {

            $member = (strtolower($member_id) == 'me') ?
                $this->resource_server_context->getCurrentUser() :
                $this->repository->getById($member_id);

            if (is_null($member))
                return $this->error404();

            $this->member_service->deleteAffiliation($member, $affiliation_id);

            return $this->deleted();
        });
    }

    #[OA\Delete(
        path: '/api/v1/members/{member_id}/rsvp/{rsvp_id}',
        operationId: 'deleteMemberRsvp',
        summary: 'Delete member RSVP',
        description: 'Deletes an RSVP for a specific member',
        tags: ['Members'],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
            ]
        ],
        security: [['members_oauth2' => [
            MemberScopes::WriteMemberData,
        ]]],
        parameters: [
            new OA\Parameter(name: 'member_id', in: 'path', required: true, description: 'Member ID', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'rsvp_id', in: 'path', required: true, description: 'RSVP ID', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: Response::HTTP_NO_CONTENT, description: 'RSVP deleted successfully'),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Unauthorized'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Member or RSVP not found'),
        ]
    )]
    public function deleteRSVP($member_id, $rsvp_id)
    {
        return $this->processRequest(function () use ($member_id, $rsvp_id) {

            $member = $this->repository->getById(intval($member_id));
            if (is_null($member))
                return $this->error404();

            $this->member_service->deleteRSVP($member, intval($rsvp_id));

            return $this->deleted();
        });
    }

    #[OA\Put(
        path: '/api/v1/members/me/membership/foundation',
        operationId: 'signFoundationMembership',
        summary: 'Sign foundation membership',
        description: 'Signs the currently authenticated member up for foundation membership',
        tags: ['Members'],
        security: [['members_oauth2' => [
            MemberScopes::WriteMyMemberData,
        ]]],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Foundation membership signed successfully',
                content: new OA\JsonContent(ref: '#/components/schemas/Member')
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Unauthorized'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Member not found'),
        ]
    )]
    public function signFoundationMembership()
    {
        return $this->processRequest(function () {

            $member = $this->resource_server_context->getCurrentUser();

            if (is_null($member))
                return $this->error404();

            $member = $this->member_service->signFoundationMembership($member);

            return $this->updated(SerializerRegistry::getInstance()->getSerializer
            (
                $member,
                SerializerRegistry::SerializerType_Private
            )->serialize(
                    SerializerUtils::getExpand(),
                    SerializerUtils::getFields(),
                    SerializerUtils::getRelations()
                ));
        });
    }

    #[OA\Put(
        path: '/api/v1/members/me/membership/community',
        operationId: 'signCommunityMembership',
        summary: 'Sign community membership',
        description: 'Signs the currently authenticated member up for community membership',
        tags: ['Members'],
        security: [['members_oauth2' => [
            MemberScopes::WriteMyMemberData,
        ]]],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Community membership signed successfully',
                content: new OA\JsonContent(ref: '#/components/schemas/Member')
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Unauthorized'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Member not found'),
        ]
    )]
    public function signCommunityMembership()
    {
        return $this->processRequest(function () {

            $member = $this->resource_server_context->getCurrentUser();

            if (is_null($member))
                return $this->error404();

            $member = $this->member_service->signCommunityMembership($member);

            return $this->updated(SerializerRegistry::getInstance()->getSerializer
            (
                $member,
                SerializerRegistry::SerializerType_Private
            )->serialize(
                    SerializerUtils::getExpand(),
                    SerializerUtils::getFields(),
                    SerializerUtils::getRelations()
                ));
        });
    }

    #[OA\Delete(
        path: '/api/v1/members/me/membership/resign',
        operationId: 'resignMembership',
        summary: 'Resign membership',
        description: 'Resigns the currently authenticated member from their membership',
        tags: ['Members'],
        security: [['members_oauth2' => [
            MemberScopes::WriteMyMemberData,
        ]]],
        responses: [
            new OA\Response(response: Response::HTTP_NO_CONTENT, description: 'Membership resigned successfully'),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Unauthorized'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Member not found'),
        ]
    )]
    public function resignMembership()
    {
        return $this->processRequest(function () {
            $member = $this->resource_server_context->getCurrentUser();

            if (is_null($member))
                return $this->error404();

            $this->member_service->resignMembership($member);

            return $this->deleted();
        });
    }

    #[OA\Put(
        path: '/api/v1/members/me/membership/individual',
        operationId: 'signIndividualMembership',
        summary: 'Sign individual membership',
        description: 'Signs the currently authenticated member up for individual membership',
        tags: ['Members'],
        security: [['members_oauth2' => [
            MemberScopes::WriteMyMemberData,
        ]]],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Individual membership signed successfully',
                content: new OA\JsonContent(ref: '#/components/schemas/Member')
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Unauthorized'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Member not found'),
        ]
    )]
    public function signIndividualMembership()
    {
        return $this->processRequest(function () {

            $member = $this->resource_server_context->getCurrentUser();

            if (is_null($member))
                return $this->error404();

            $member = $this->member_service->signIndividualMembership($member);

            return $this->updated(SerializerRegistry::getInstance()->getSerializer
            (
                $member,
                SerializerRegistry::SerializerType_Private
            )->serialize(
                    SerializerUtils::getExpand(),
                    SerializerUtils::getFields(),
                    SerializerUtils::getRelations()
                ));
        });
    }

}
