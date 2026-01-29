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

use App\Http\Exceptions\HTTP403ForbiddenException;
use App\Models\Foundation\Main\IGroup;
use App\Models\Foundation\Summit\Repositories\ISponsorAdRepository;
use App\Models\Foundation\Summit\Repositories\ISponsorExtraQuestionTypeRepository;
use App\Models\Foundation\Summit\Repositories\ISponsorMaterialRepository;
use App\Models\Foundation\Summit\Repositories\ISponsorRepository;
use App\Models\Foundation\Summit\Repositories\ISponsorSocialNetworkRepository;
use App\ModelSerializers\SerializerUtils;
use App\Security\SummitScopes;
use Illuminate\Http\Request as LaravelRequest;
use Illuminate\Http\Response;
use models\oauth2\IResourceServerContext;
use models\summit\ISummitRepository;
use models\summit\Sponsor;
use models\summit\SponsorMaterial;
use models\summit\Summit;
use models\utils\IEntity;
use ModelSerializers\SerializerRegistry;
use OpenApi\Attributes as OA;
use services\model\ISummitSponsorService;
use utils\Filter;
use utils\FilterElement;
use utils\PagingInfo;

/**
 * Class OAuth2SummitSponsorApiController
 * @package App\Http\Controllers
 */
final class OAuth2SummitSponsorApiController extends OAuth2ProtectedController
{
    /**
     * @var ISummitRepository
     */
    private $summit_repository;

    /**
     * @var ISummitSponsorService
     */
    private $service;

    /**
     * @var ISponsorAdRepository
     */
    private $sponsor_ads_repository;

    /**
     * @var ISponsorMaterialRepository
     */
    private $sponsor_materials_repository;

    /**
     * @var ISponsorSocialNetworkRepository
     */
    private $sponsor_social_network_repository;

    /**
     * @var ISponsorExtraQuestionTypeRepository
     */
    private $sponsor_extra_question_repository;

    private $serializer_version = 1;

    private $add_validation_rules_version = 1;

    private $update_validation_rules_version = 1;

    /**
     * @param ISponsorRepository $repository
     * @param ISummitRepository $summit_repository
     * @param ISponsorAdRepository $sponsor_ads_repository
     * @param ISponsorMaterialRepository $sponsor_materials_repository
     * @param ISponsorSocialNetworkRepository $sponsor_social_network_repository
     * @param ISponsorExtraQuestionTypeRepository $sponsor_extra_question_repository
     * @param ISummitSponsorService $service
     * @param IResourceServerContext $resource_server_context
     */
    public function __construct
    (
        ISponsorRepository                  $repository,
        ISummitRepository                   $summit_repository,
        ISponsorAdRepository                $sponsor_ads_repository,
        ISponsorMaterialRepository          $sponsor_materials_repository,
        ISponsorSocialNetworkRepository     $sponsor_social_network_repository,
        ISponsorExtraQuestionTypeRepository $sponsor_extra_question_repository,
        ISummitSponsorService               $service,
        IResourceServerContext              $resource_server_context
    )
    {
        parent::__construct($resource_server_context);
        $this->summit_repository = $summit_repository;
        $this->sponsor_ads_repository = $sponsor_ads_repository;
        $this->sponsor_materials_repository = $sponsor_materials_repository;
        $this->sponsor_social_network_repository = $sponsor_social_network_repository;
        $this->sponsor_extra_question_repository = $sponsor_extra_question_repository;
        $this->service = $service;
        $this->repository = $repository;
    }


    /**
     * @return array
     */
    protected function getFilterRules(): array
    {
        return [
            'company_name' => ['==', '=@', '@@'],
            'sponsorship_name' => ['==', '=@', '@@'],
            'sponsorship_size' => ['==', '=@', '@@'],
            'badge_scans_count' => ['==', '<', '>', '<=', '>=', '<>'],
            'is_published' => ['=='],
            'company_id' => ['=='],
        ];
    }

    /**
     * @return array
     */
    protected function getFilterValidatorRules(): array
    {
        return [
            'company_name' => 'sometimes|required|string',
            'company_id' => 'sometimes|required|integer',
            'sponsorship_name' => 'sometimes|required|string',
            'sponsorship_size' => 'sometimes|required|string',
            'badge_scans_count' => 'sometimes|required|integer',
            'is_published' => 'sometimes|required|boolean',
        ];
    }

    /**
     * @return array
     */
    protected function getOrderRules(): array
    {
        return [
            'id',
            'order',
            'company_name',
            'sponsorship_name',
            'sponsorship_size'
        ];
    }

    use GetAllBySummit;

    use GetSummitChildElementById;

    use AddSummitChildElement;

    use UpdateSummitChildElement;

    use DeleteSummitChildElement;

    use RequestProcessor;

    /**
     * @param Filter $filter
     * @return Filter
     */
    protected function applyExtraFilters(Filter $filter):Filter {

        // this is the authz code for sponsors users ...
        $current_member = $this->resource_server_context->getCurrentUser();

        $summit_id = intval($this->summit_id);
        $summit = SummitFinderStrategyFactory::build($this->getSummitRepository(), $this->getResourceServerContext())->find($this->summit_id);

        if (!is_null($summit)) {

            // add filter for summit .
            $filter->addFilterCondition(FilterElement::makeEqual("summit_id",$summit_id));
            if(!is_null($current_member)) {
                // check AUTHZ for sponsors
                if($current_member->isAuthzFor($summit)) return $filter;
                // add filter for sponsor user
                if ($current_member->isSponsorUser()) {
                    $sponsor_ids = $current_member->getSponsorMembershipIds($summit);
                    // is allowed sponsors are empty, add dummy value
                    if (!count($sponsor_ids)) $sponsor_ids[] = 0;
                    $filter->addFilterCondition
                    (
                        FilterElement::makeEqual
                        (
                            'sponsor_id',
                            $sponsor_ids,
                            "OR"
                        )
                    );
                }
            }
        }

        return $filter;
    }

    /**
     * @param array $payload
     * @return array
     */
    function getAddValidationRules(array $payload): array
    {
        return $this->add_validation_rules_version == 1 ?
            SponsorValidationRulesFactory::buildForAdd($payload) :
            SponsorValidationRulesFactory::buildForAddV2($payload);
    }

    protected function serializerType():string{
        return $this->serializer_version == 1 ?
            SerializerRegistry::SerializerType_Public :
            SerializerRegistry::SerializerType_PublicV2;
    }

    public function getChildSerializer():string{
        return $this->serializer_version == 1 ?
            SerializerRegistry::SerializerType_Public :
            SerializerRegistry::SerializerType_PublicV2;
    }

    protected function addSerializerType():string{
        return $this->serializer_version == 1 ?
            SerializerRegistry::SerializerType_Public :
            SerializerRegistry::SerializerType_PublicV2;
    }

    protected function updateSerializerType(): string{
        return $this->serializer_version == 1 ?
            SerializerRegistry::SerializerType_Public :
            SerializerRegistry::SerializerType_PublicV2;
    }

    #[OA\Get(
        path: "/api/v2/summits/{id}/sponsors",
        description: "Get all sponsors for a summit",
        summary: 'Read Sponsors by Summit (V2)',
        operationId: 'getSponsorsBySummitV2',
        tags: ['Sponsors'],
        security: [
            [
                'summit_sponsor_oauth2' => [
                    SummitScopes::ReadSummitData,
                    SummitScopes::ReadAllSummitData,
                ]
            ]
        ],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
                IGroup::Sponsors,
                IGroup::SponsorExternalUsers,
            ]
        ],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'), description: 'The summit id'),
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 1), description: 'Page number'),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 10), description: 'Items per page'),
            new OA\Parameter(name: 'order', in: 'query', required: false, schema: new OA\Schema(type: 'string'), description: 'Order by field (e.g., +id, -company_name)'),
            new OA\Parameter(name: 'expand', in: 'query', required: false, schema: new OA\Schema(type: 'string'), description: 'Expand relationships (company)'),
            new OA\Parameter(name: 'fields', in: 'query', required: false, schema: new OA\Schema(type: 'string'), description: 'Fields to include (id,company_name)'),
            new OA\Parameter(name: 'filter', in: 'query', required: false, schema: new OA\Schema(type: 'string'), description: 'Filter expression (e.g., is_published==true)'),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Success',
                content: new OA\JsonContent(ref: '#/components/schemas/SponsorV2')
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]

    /**
     * @param $summit_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function getAllBySummitV2($summit_id){
        $this->serializer_version = 2;
        return $this->getAllBySummit($summit_id);
    }

    #[OA\Get(
        path: "/api/public/v1/summits/all/{id}/sponsors",
        description: "Get a specific sponsor by id",
        summary: 'Get Sponsors by Summit ID (Public)',
        operationId: 'getSponsorsV2BySummitPublic',
        tags: ['Sponsors (Public)'],
        security: [
            [
                'summit_sponsor_oauth2' => [
                    SummitScopes::ReadSummitData,
                    SummitScopes::ReadAllSummitData,
                ]
            ]
        ],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
                IGroup::Sponsors,
                IGroup::SponsorExternalUsers,
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
                name: 'sponsor_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The sponsor id'
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Success',
                content: new OA\JsonContent(ref: '#/components/schemas/SponsorV2')
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error")
        ]
    )]
    /**
     * @param $summit_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function getAllBySummitPublic($summit_id)
    {
        $this->serializer_version = 2;
        return $this->getAllBySummit($summit_id);
    }

    #[OA\Get(
        path: "/api/v2/summits/{id}/sponsors/{sponsor_id}",
        description: "Get all sponsors for a summit (Public API)",
        summary: 'Read Sponsors by Summit',
        operationId: 'getSponsorsV2BySummit',
        tags: ['Sponsors'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The summit id'
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Success',
                content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/SponsorV2'))
            ),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error")
        ]
    )]
    /**
     * @param $summit_id
     * @param $child_id
     * @return mixed
     */
    public function getV2($summit_id, $child_id){
        $this->serializer_version = 2;
        return $this->get($summit_id, $child_id);
    }

    /**
     * @param Summit $summit
     * @param array $payload
     * @return IEntity
     */
    protected function addChild(Summit $summit, array $payload): IEntity
    {
        // authz check ( SERVICE or authz member )
        $application_type = $this->resource_server_context->getApplicationType();
        $current_member = $this->resource_server_context->getCurrentUser();
        $is_authz = $application_type == IResourceServerContext::ApplicationType_Service ||
            (!is_null($current_member) && $current_member->isAuthzFor($summit));

        if(!$is_authz)
            throw new HTTP403ForbiddenException("You are not allowed to perform this action.");
        return $this->service->addSponsor($summit, $payload);
    }

    #[OA\Post(
        path: "/api/v2/summits/{id}/sponsors",
        description: "Create a new sponsor",
        summary: 'Create Sponsor (V2)',
        operationId: 'addSponsorV2',
        tags: ['Sponsors'],
        security: [
            [
                'summit_sponsor_oauth2' => [
                    SummitScopes::WriteSummitData,
                ]
            ]
        ],
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
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: "#/components/schemas/SponsorCreateRequest")
        ),
        responses: [
            new OA\Response(
                response: Response::HTTP_CREATED,
                description: 'Created',
                content: new OA\JsonContent(ref: '#/components/schemas/SponsorV2')
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: "Bad Request"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error")
        ]
    )]
    /**
     * @param $summit_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function addV2($summit_id){
        $this->serializer_version = 2;
        $this->add_validation_rules_version = 2;
        return $this->add($summit_id);
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
     * @return void
     */
    protected function deleteChild(Summit $summit, $child_id): void
    {
        // authz check
        $current_member = $this->resource_server_context->getCurrentUser();
        if(!$current_member->isAuthzFor($summit))
            throw new HTTP403ForbiddenException("You are not allowed to perform this action.");

        $this->service->deleteSponsor($summit, $child_id);
    }

    protected function getChildFromSummit(Summit $summit, $child_id): ?IEntity
    {
        $current_member = $this->resource_server_context->getCurrentUser();
        $sponsor = $summit->getSummitSponsorById($child_id);

        if(is_null($sponsor)) return null;
        // service account
        if(is_null($current_member)) return $sponsor;
        if($current_member->isAdmin()) return $sponsor;
        if($current_member->hasSponsorMembershipsFor($summit, $sponsor)) return $sponsor;
        if($current_member->isSummitAdmin() && $current_member->isSummitAllowed($summit)) return $sponsor;

        return null;
    }

    /**
     * @param array $payload
     * @return array
     */
    function getUpdateValidationRules(array $payload): array
    {
        return $this->add_validation_rules_version == 1 ?
            SponsorValidationRulesFactory::buildForUpdate($payload) :
            SponsorValidationRulesFactory::buildForUpdateV2($payload);
    }

    /**
     * @param Summit $summit
     * @param int $child_id
     * @param array $payload
     * @return IEntity
     */
    protected function updateChild(Summit $summit, int $child_id, array $payload): IEntity
    {
        // authz check
        $current_member = $this->resource_server_context->getCurrentUser();
        if(!$current_member->isAuthzFor($summit))
            throw new HTTP403ForbiddenException("You are not allowed to perform this action");

        return $this->service->updateSponsor($summit, $child_id, $payload);
    }

    #[OA\Put(
        path: "/api/v2/summits/{id}/sponsors/{sponsor_id}",
        description: "Update an existing sponsor",
        summary: 'Update Sponsor (V2)',
        operationId: 'updateSponsorV2',
        tags: ['Sponsors'],
        security: [
            [
                'summit_sponsor_oauth2' => [
                    SummitScopes::WriteSummitData,
                ]
            ]
        ],
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
                name: 'sponsor_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The sponsor id'
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: "#/components/schemas/SponsorUpdateRequest")
        ),
        responses: [
            new OA\Response(
                response: Response::HTTP_CREATED,
                description: 'Success',
                content: new OA\JsonContent(ref: '#/components/schemas/SponsorV2')
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: "Bad Request"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error")
        ]
    )]
    /**
     * @param $summit_id
     * @param $child_id
     * @return mixed
     */
    public function updateV2($summit_id, $child_id){
        $this->serializer_version = 2;
        $this->add_validation_rules_version = 2;
        return $this->update($summit_id, $child_id);
    }

    #[OA\Put(
        path: "/api/v1/summits/{id}/sponsors/{sponsor_id}/users/{member_id}",
        description: "Add a user to a sponsor",
        summary: 'Add Sponsor User',
        operationId: 'addSponsorUser',
        tags: ['Sponsors'],
        security: [
            [
                'summit_sponsor_oauth2' => [
                    SummitScopes::WriteSummitData,
                ]
            ]
        ],
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
                name: 'sponsor_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The sponsor id'
            ),
            new OA\Parameter(
                name: 'member_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The member id to add'
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_CREATED,
                description: 'Success',
                content: new OA\JsonContent(ref: '#/components/schemas/Sponsor')
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error")
        ]
    )]
    /**
     * @param $summit_id
     * @param $sponsor_id
     * @param $member_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function addSponsorUser($summit_id, $sponsor_id, $member_id)
    {
        return $this->processRequest(function () use ($summit_id, $sponsor_id, $member_id) {
            $summit = SummitFinderStrategyFactory::build($this->getSummitRepository(), $this->getResourceServerContext())->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $sponsor = $summit->getSummitSponsorById(intval($sponsor_id));
            if (is_null($sponsor)) return $this->error404();

            // authz check
            $current_member = $this->resource_server_context->getCurrentUser();
            if(!is_null($current_member) && !$current_member->isAuthzFor($summit))
                throw new HTTP403ForbiddenException("You are not allowed to perform this action");

            $sponsor = $this->service->addSponsorUser($summit, $sponsor_id, $member_id);
            return $this->updated(SerializerRegistry::getInstance()->getSerializer($sponsor)->serialize(
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));
        });
    }

    #[OA\Delete(
        path: "/api/v1/summits/{id}/sponsors/{sponsor_id}/users/{member_id}",
        description: "Remove a user from a sponsor",
        summary: 'Remove Sponsor User',
        operationId: 'removeSponsorUser',
        tags: ['Sponsors'],
        security: [
            [
                'summit_sponsor_oauth2' => [
                    SummitScopes::WriteSummitData,
                ]
            ]
        ],
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
                name: 'sponsor_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The sponsor id'
            ),
            new OA\Parameter(
                name: 'member_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The member id to remove'
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_CREATED,
                description: 'Success',
                content: new OA\JsonContent(ref: '#/components/schemas/Sponsor')
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error")
        ]
    )]
    /**
     * @param $summit_id
     * @param $sponsor_id
     * @param $member_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function removeSponsorUser($summit_id, $sponsor_id, $member_id)
    {
        return $this->processRequest(function () use ($summit_id, $sponsor_id, $member_id) {
            $summit = SummitFinderStrategyFactory::build($this->getSummitRepository(), $this->getResourceServerContext())->find($summit_id);
            if (is_null($summit)) return $this->error404();

            // authz check
            $current_member = $this->resource_server_context->getCurrentUser();
            if(!$current_member->isAuthzFor($summit))
                throw new HTTP403ForbiddenException("You are not allowed to perform this action");

            $sponsor = $this->service->removeSponsorUser($summit, $sponsor_id, $member_id);
            return $this->updated(SerializerRegistry::getInstance()->getSerializer($sponsor)->serialize(
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));
        });
    }

    /**
     * @param LaravelRequest $request
     * @param $summit_id
     * @param $sponsor_id
     * @return mixed
     */
    #[OA\Post(
        path: "/api/v1/summits/{id}/sponsors/{sponsor_id}/side-image",
        description: "Add a side image to a sponsor",
        summary: 'Add Sponsor Side Image',
        operationId: 'addSponsorSideImage',
        tags: ['Sponsors'],
        security: [
            [
                'summit_sponsor_oauth2' => [
                    SummitScopes::WriteSummitData,
                ]
            ]
        ],
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
                name: 'sponsor_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The sponsor id'
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    type: 'object',
                    required: ['file'],
                    properties: [
                        new OA\Property(
                            property: 'file',
                            type: 'string',
                            format: 'binary',
                            description: 'The image file'
                        ),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: Response::HTTP_CREATED,
                description: 'Created',
                content: new OA\JsonContent(ref: '#/components/schemas/File')
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error")
        ]
    )]
    public function addSponsorSideImage(LaravelRequest $request, $summit_id, $sponsor_id)
    {
        return $this->processRequest(function () use ($request, $summit_id, $sponsor_id) {

            $summit = SummitFinderStrategyFactory::build($this->getSummitRepository(), $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $file = $request->file('file');
            if (is_null($file)) {
                return $this->error412(array('file param not set!'));
            }

            $image = $this->service->addSponsorSideImage($summit, $sponsor_id, $file);

            return $this->created(SerializerRegistry::getInstance()->getSerializer($image)->serialize
            (
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));

        });
    }

    #[OA\Delete(
        path: "/api/v1/summits/{id}/sponsors/{sponsor_id}/side-image",
        description: "Delete a sponsor side image",
        summary: 'Delete Sponsor Side Image',
        operationId: 'deleteSponsorSideImage',
        tags: ['Sponsors'],
        security: [
            [
                'summit_sponsor_oauth2' => [
                    SummitScopes::WriteSummitData,
                ]
            ]
        ],
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
                name: 'sponsor_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The sponsor id'
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_NO_CONTENT,
                description: 'No Content'
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error")
        ]
    )]
    /**
     * @param $summit_id
     * @param $sponsor_id
     * @return mixed
     */
    public function deleteSponsorSideImage($summit_id, $sponsor_id)
    {
        return $this->processRequest(function () use ($summit_id, $sponsor_id) {

            $summit = SummitFinderStrategyFactory::build($this->getSummitRepository(), $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $this->service->deleteSponsorSideImage($summit, $sponsor_id);

            return $this->deleted();

        });
    }

    /**
     * @param LaravelRequest $request
     * @param $summit_id
     * @param $sponsor_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    #[OA\Post(
        path: "/api/v1/summits/{id}/sponsors/{sponsor_id}/header-image",
        description: "Add a header image to a sponsor",
        summary: 'Add Sponsor Header Image',
        operationId: 'addSponsorHeaderImage',
        tags: ['Sponsors'],
        security: [
            [
                'summit_sponsor_oauth2' => [
                    SummitScopes::WriteSummitData,
                ]
            ]
        ],
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
                name: 'sponsor_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The sponsor id'
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    type: 'object',
                    required: ['file'],
                    properties: [
                        new OA\Property(
                            property: 'file',
                            type: 'string',
                            format: 'binary',
                            description: 'The image file'
                        ),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: Response::HTTP_CREATED,
                description: 'Created',
                content: new OA\JsonContent(ref: '#/components/schemas/File')
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error")
        ]
    )]
    public function addSponsorHeaderImage(LaravelRequest $request, $summit_id, $sponsor_id)
    {
        return $this->processRequest(function () use ($request, $summit_id, $sponsor_id) {

            $summit = SummitFinderStrategyFactory::build($this->getSummitRepository(), $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $file = $request->file('file');
            if (is_null($file)) {
                return $this->error412(array('file param not set!'));
            }

            $image = $this->service->addSponsorHeaderImage($summit, intval($sponsor_id), $file);

            return $this->created(SerializerRegistry::getInstance()->getSerializer($image)->serialize
            (
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));

        });
    }

    #[OA\Delete(
        path: "/api/v1/summits/{id}/sponsors/{sponsor_id}/header-image",
        description: "Delete a sponsor header image",
        summary: 'Delete Sponsor Header Image',
        operationId: 'deleteSponsorHeaderImage',
        tags: ['Sponsors'],
        security: [
            [
                'summit_sponsor_oauth2' => [
                    SummitScopes::WriteSummitData,
                ]
            ]
        ],
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
                name: 'sponsor_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The sponsor id'
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_NO_CONTENT,
                description: 'No Content'
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error")
        ]
    )]
    /**
     * @param $summit_id
     * @param $sponsor_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function deleteSponsorHeaderImage($summit_id, $sponsor_id)
    {
        return $this->processRequest(function () use ($summit_id, $sponsor_id) {

            $summit = SummitFinderStrategyFactory::build($this->getSummitRepository(), $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $this->service->deleteSponsorHeaderImage($summit, intval($sponsor_id));

            return $this->deleted();

        });
    }

    #[OA\Post(
        path: "/api/v1/summits/{id}/sponsors/{sponsor_id}/header-image/mobile",
        description: "Add a mobile header image to a sponsor",
        summary: 'Add Sponsor Header Image Mobile',
        operationId: 'addSponsorHeaderImageMobile',
        tags: ['Sponsors'],
        security: [
            [
                'summit_sponsor_oauth2' => [
                    SummitScopes::WriteSummitData,
                ]
            ]
        ],
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
                name: 'sponsor_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The sponsor id'
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    type: 'object',
                    required: ['file'],
                    properties: [
                        new OA\Property(
                            property: 'file',
                            type: 'string',
                            format: 'binary',
                            description: 'The image file'
                        ),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: Response::HTTP_CREATED,
                description: 'Created',
                content: new OA\JsonContent(ref: '#/components/schemas/File')
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error")
        ]
    )]
    /**
     * @param LaravelRequest $request
     * @param $summit_id
     * @param $sponsor_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function addSponsorHeaderImageMobile(LaravelRequest $request, $summit_id, $sponsor_id)
    {
        return $this->processRequest(function () use ($request, $summit_id, $sponsor_id) {

            $summit = SummitFinderStrategyFactory::build($this->getSummitRepository(), $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $file = $request->file('file');
            if (is_null($file)) {
                return $this->error412(array('file param not set!'));
            }

            $image = $this->service->addSponsorHeaderImageMobile($summit, intval($sponsor_id), $file);

            return $this->created(SerializerRegistry::getInstance()->getSerializer($image)->serialize
            (
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));

        });
    }

    #[OA\Delete(
        path: "/api/v1/summits/{id}/sponsors/{sponsor_id}/header-image/mobile",
        description: "Delete a sponsor mobile header image",
        summary: 'Delete Sponsor Header Image Mobile',
        operationId: 'deleteSponsorHeaderImageMobile',
        tags: ['Sponsors'],
        security: [
            [
                'summit_sponsor_oauth2' => [
                    SummitScopes::WriteSummitData,
                ]
            ]
        ],
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
                name: 'sponsor_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The sponsor id'
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_NO_CONTENT,
                description: 'No Content'
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error")
        ]
    )]
    /**
     * @param $summit_id
     * @param $sponsor_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function deleteSponsorHeaderImageMobile($summit_id, $sponsor_id)
    {
        return $this->processRequest(function () use ($summit_id, $sponsor_id) {

            $summit = SummitFinderStrategyFactory::build($this->getSummitRepository(), $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $this->service->deleteSponsorHeaderImageMobile($summit, intval($sponsor_id));

            return $this->deleted();

        });
    }

    #[OA\Post(
        path: "/api/v1/summits/{id}/sponsors/{sponsor_id}/carousel-advertise-image",
        description: "Add a carousel advertise image to a sponsor",
        summary: 'Add Sponsor Carousel Advertise Image',
        operationId: 'addSponsorCarouselAdvertiseImage',
        tags: ['Sponsors'],
        security: [
            [
                'summit_sponsor_oauth2' => [
                    SummitScopes::WriteSummitData,
                ]
            ]
        ],
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
                name: 'sponsor_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The sponsor id'
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    type: 'object',
                    required: ['file'],
                    properties: [
                        new OA\Property(
                            property: 'file',
                            type: 'string',
                            format: 'binary',
                            description: 'The image file'
                        ),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: Response::HTTP_CREATED,
                description: 'Created',
                content: new OA\JsonContent(ref: '#/components/schemas/File')
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error")
        ]
    )]
    /**
     * @param LaravelRequest $request
     * @param $summit_id
     * @param $sponsor_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function addSponsorCarouselAdvertiseImage(LaravelRequest $request, $summit_id, $sponsor_id)
    {
        return $this->processRequest(function () use ($request, $summit_id, $sponsor_id) {

            $summit = SummitFinderStrategyFactory::build($this->getSummitRepository(), $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $file = $request->file('file');
            if (is_null($file)) {
                return $this->error412(array('file param not set!'));
            }

            $image = $this->service->addSponsorCarouselAdvertiseImage($summit, intval($sponsor_id), $file);

            return $this->created(SerializerRegistry::getInstance()->getSerializer($image)->serialize
            (
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));

        });
    }

    #[OA\Delete(
        path: "/api/v1/summits/{id}/sponsors/{sponsor_id}/carousel-advertise-image",
        description: "Delete a sponsor carousel advertise image",
        summary: 'Delete Sponsor Carousel Advertise Image',
        operationId: 'deleteSponsorCarouselAdvertiseImage',
        tags: ['Sponsors'],
        security: [
            [
                'summit_sponsor_oauth2' => [
                    SummitScopes::WriteSummitData,
                ]
            ]
        ],
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
                name: 'sponsor_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The sponsor id'
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_NO_CONTENT,
                description: 'No Content'
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error")
        ]
    )]
    /**
     * @param $summit_id
     * @param $sponsor_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function deleteSponsorCarouselAdvertiseImage($summit_id, $sponsor_id)
    {
        return $this->processRequest(function () use ($summit_id, $sponsor_id) {

            $summit = SummitFinderStrategyFactory::build($this->getSummitRepository(), $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $this->service->deleteSponsorCarouselAdvertiseImage($summit, intval($sponsor_id));

            return $this->deleted();

        });
    }

    use ParametrizedGetAll;

    use GetAndValidateJsonPayload;

    // Ads

    #[OA\Get(
        path: "/api/v1/summits/{id}/sponsors/{sponsor_id}/ads",
        description: "Get all ads for a sponsor",
        summary: 'Read Sponsor Ads',
        operationId: 'getSponsorAds',
        tags: ['Sponsors'],
        security: [
            [
                'summit_sponsor_oauth2' => [
                    SummitScopes::ReadSummitData,
                    SummitScopes::ReadAllSummitData,
                ]
            ]
        ],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
                IGroup::Sponsors,
                IGroup::SponsorExternalUsers,
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
                name: 'sponsor_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The sponsor id'
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
                name: 'order',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string'),
                description: 'Order by field (e.g., +id, -order)'
            ),
            new OA\Parameter(
                name: 'filter',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string'),
                description: 'Filter expression'
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Success'
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error")
        ]
    )]
    /**
     * @param $summit_id
     * @param $sponsor_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function getAds($summit_id, $sponsor_id)
    {

        $summit = SummitFinderStrategyFactory::build($this->getSummitRepository(), $this->resource_server_context)->find($summit_id);
        if (is_null($summit)) return $this->error404();

        $sponsor = $summit->getSummitSponsorById(intval($sponsor_id));
        if (is_null($sponsor)) return $this->error404();

        return $this->_getAll(
            function () {
                return [
                    'sponsor_id' => ['=='],
                ];
            },
            function () {
                return [
                    'sponsor_id' => 'sometimes|int',
                ];
            },
            function () {
                return [
                    'id',
                    'order',
                ];
            },
            function ($filter) use ($sponsor) {
                if ($filter instanceof Filter) {
                    $filter->addFilterCondition(FilterElement::makeEqual('sponsor_id', $sponsor->getId()));
                }
                return $filter;
            },
            function () {
                return SerializerRegistry::SerializerType_Private;
            },
            null,
            null,
            function ($page, $per_page, $filter, $order, $applyExtraFilters) {
                return $this->sponsor_ads_repository->getAllByPage
                (
                    new PagingInfo($page, $per_page),
                    call_user_func($applyExtraFilters, $filter),
                    $order
                );
            }
        );
    }

    #[OA\Post(
        path: "/api/v1/summits/{id}/sponsors/{sponsor_id}/ads",
        description: "Create a new sponsor ad",
        summary: 'Add Sponsor Ad',
        operationId: 'addSponsorAd',
        tags: ['Sponsors'],
        security: [
            [
                'summit_sponsor_oauth2' => [
                    SummitScopes::WriteSummitData,
                ]
            ]
        ],
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
                name: 'sponsor_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The sponsor id'
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: "#/components/schemas/SponsorAdCreateRequest")
        ),
        responses: [
            new OA\Response(
                response: Response::HTTP_CREATED,
                description: 'Created',
                content: new OA\JsonContent(ref: '#/components/schemas/SponsorAd')
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error")
        ]
    )]
    /**
     * @param $summit_id
     * @param $sponsor_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function addAd($summit_id, $sponsor_id)
    {
        return $this->processRequest(function () use ($summit_id, $sponsor_id) {
            $summit = SummitFinderStrategyFactory::build($this->getSummitRepository(), $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $payload = $this->getJsonPayload(SponsorAdValidationRulesFactory::buildForAdd(), true);

            $ad = $this->service->addSponsorAd($summit, intval($sponsor_id), $payload);

            return $this->created(SerializerRegistry::getInstance()
                ->getSerializer($ad, SerializerRegistry::SerializerType_Private)
                ->serialize(
                    SerializerUtils::getExpand(),
                    SerializerUtils::getFields(),
                    SerializerUtils::getRelations()
                )
            );
        });
    }

    #[OA\Post(
        path: "/api/v1/summits/{id}/sponsors/{sponsor_id}/ads/{ad_id}/image",
        description: "Add an image to a sponsor ad",
        summary: 'Add Sponsor Ad Image',
        operationId: 'addSponsorAdImage',
        tags: ['Sponsors'],
        security: [
            [
                'summit_sponsor_oauth2' => [
                    SummitScopes::WriteSummitData,
                ]
            ]
        ],
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
                name: 'sponsor_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The sponsor id'
            ),
            new OA\Parameter(
                name: 'ad_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The ad id'
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    type: 'object',
                    required: ['file'],
                    properties: [
                        new OA\Property(
                            property: 'file',
                            type: 'string',
                            format: 'binary',
                            description: 'The image file'
                        ),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: Response::HTTP_CREATED,
                description: 'Created',
                content: new OA\JsonContent(ref: '#/components/schemas/File')
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error")
        ]
    )]
    /**
     * @param LaravelRequest $request
     * @param $summit_id
     * @param $sponsor_id
     * @param $ad_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function addAdImage(LaravelRequest $request, $summit_id, $sponsor_id, $ad_id)
    {
        return $this->processRequest(function () use ($request, $summit_id, $sponsor_id, $ad_id) {

            $summit = SummitFinderStrategyFactory::build($this->getSummitRepository(), $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $file = $request->file('file');
            if (is_null($file)) {
                return $this->error412('file param not set!');
            }

            $image = $this->service->addSponsorAdImage($summit, intval($sponsor_id), intval($ad_id), $file);

            return $this->created(SerializerRegistry::getInstance()->getSerializer($image)->serialize
            (
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));

        });
    }

    #[OA\Delete(
        path: "/api/v1/summits/{id}/sponsors/{sponsor_id}/ads/{ad_id}/image",
        description: "Remove an image from a sponsor ad",
        summary: 'Remove Sponsor Ad Image',
        operationId: 'removeSponsorAdImage',
        tags: ['Sponsors'],
        security: [
            [
                'summit_sponsor_oauth2' => [
                    SummitScopes::WriteSummitData,
                ]
            ]
        ],
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
                name: 'sponsor_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The sponsor id'
            ),
            new OA\Parameter(
                name: 'ad_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The ad id'
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_NO_CONTENT,
                description: 'No Content'
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error")
        ]
    )]
    /**
     * @param $summit_id
     * @param $sponsor_id
     * @param $ad_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function removeAdImage($summit_id, $sponsor_id, $ad_id)
    {
        return $this->processRequest(function () use ($summit_id, $sponsor_id, $ad_id) {

            $summit = SummitFinderStrategyFactory::build($this->getSummitRepository(), $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $this->service->deleteSponsorAdImage($summit, intval($sponsor_id), intval($ad_id));

            return $this->deleted();

        });
    }

    #[OA\Put(
        path: "/api/v1/summits/{id}/sponsors/{sponsor_id}/ads/{ad_id}",
        description: "required-groups " . IGroup::SuperAdmins . ", " . IGroup::Administrators . ", " . IGroup::SummitAdministrators,
        summary: 'Update Sponsor Ad',
        operationId: 'updateSponsorAd',
        tags: ['Sponsors'],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
            ]
        ],
        security: [
            [
                'summit_sponsor_oauth2' => [
                    SummitScopes::WriteSummitData,
                ]
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
                name: 'sponsor_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The sponsor id'
            ),
            new OA\Parameter(
                name: 'ad_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The ad id'
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: "#/components/schemas/SponsorAdUpdateRequest")
        ),
        responses: [
            new OA\Response(
                response: Response::HTTP_CREATED,
                description: 'Ad updated successfully'
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error")
        ]
    )]
    /**
     * @param $summit_id
     * @param $sponsor_id
     * @param $ad_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function updateAd($summit_id, $sponsor_id, $ad_id)
    {
        return $this->processRequest(function () use ($summit_id, $sponsor_id, $ad_id) {
            $summit = SummitFinderStrategyFactory::build($this->getSummitRepository(), $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $payload = $this->getJsonPayload(SponsorAdValidationRulesFactory::buildForUpdate(), true);

            $ad = $this->service->updateSponsorAd($summit, intval($sponsor_id), intval($ad_id), $payload);

            return $this->updated(SerializerRegistry::getInstance()
                ->getSerializer($ad, SerializerRegistry::SerializerType_Private)
                ->serialize(
                    SerializerUtils::getExpand(),
                    SerializerUtils::getFields(),
                    SerializerUtils::getRelations()
                )
            );
        });
    }

    #[OA\Delete(
        path: "/api/v1/summits/{id}/sponsors/{sponsor_id}/ads/{ad_id}",
        description: "required-groups " . IGroup::SuperAdmins . ", " . IGroup::Administrators . ", " . IGroup::SummitAdministrators,
        summary: 'Delete Sponsor Ad',
        operationId: 'deleteSponsorAd',
        tags: ['Sponsors'],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
            ]
        ],
        security: [
            [
                'summit_sponsor_oauth2' => [
                    SummitScopes::WriteSummitData,
                ]
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
                name: 'sponsor_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The sponsor id'
            ),
            new OA\Parameter(
                name: 'ad_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The ad id'
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_NO_CONTENT,
                description: 'Ad deleted successfully'
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    /**
     * @param $summit_id
     * @param $sponsor_id
     * @param $ad_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function deleteAd($summit_id, $sponsor_id, $ad_id)
    {
        return $this->processRequest(function () use ($summit_id, $sponsor_id, $ad_id) {

            $summit = SummitFinderStrategyFactory::build($this->getSummitRepository(), $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $this->service->deleteSponsorAd($summit, intval($sponsor_id), intval($ad_id));

            return $this->deleted();

        });
    }

    #[OA\Get(
        path: "/api/v1/summits/{id}/sponsors/{sponsor_id}/ads/{ad_id}",
        description: "required-groups " . IGroup::SuperAdmins . ", " . IGroup::Administrators . ", " . IGroup::SummitAdministrators . ", " . IGroup::Sponsors . ", " . IGroup::SponsorExternalUsers,
        summary: 'Read Sponsor Ad',
        operationId: 'getSponsorAd',
        tags: ['Sponsors'],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
                IGroup::Sponsors,
                IGroup::SponsorExternalUsers,
            ]
        ],
        security: [
            [
                'summit_sponsor_oauth2' => [
                    SummitScopes::ReadSummitData,
                    SummitScopes::ReadAllSummitData,
                ]
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
                name: 'sponsor_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The sponsor id'
            ),
            new OA\Parameter(
                name: 'ad_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The ad id'
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Ad retrieved successfully'
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    /**
     * @param $summit_id
     * @param $sponsor_id
     * @param $ad_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function getAd($summit_id, $sponsor_id, $ad_id)
    {
        return $this->processRequest(function () use ($summit_id, $sponsor_id, $ad_id) {

            $summit = SummitFinderStrategyFactory::build($this->getSummitRepository(), $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $sponsor = $summit->getSummitSponsorById(intval($sponsor_id));
            if (is_null($sponsor)) return $this->error404();

            $ad = $sponsor->getAdById(intval($ad_id));
            if (is_null($ad)) return $this->error404();

            return $this->ok(SerializerRegistry::getInstance()
                ->getSerializer($ad, SerializerRegistry::SerializerType_Private)
                ->serialize(
                    SerializerUtils::getExpand(),
                    SerializerUtils::getFields(),
                    SerializerUtils::getRelations()
                )
            );
        });
    }

    // Materials

    #[OA\Get(
        path: "/api/v1/summits/{id}/sponsors/{sponsor_id}/materials",
        description: "required-groups " . IGroup::SuperAdmins . ", " . IGroup::Administrators . ", " . IGroup::SummitAdministrators . ", " . IGroup::Sponsors . ", " . IGroup::SponsorExternalUsers,
        summary: 'Read Sponsor Materials',
        operationId: 'getSponsorMaterials',
        tags: ['Sponsors'],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
                IGroup::Sponsors,
                IGroup::SponsorExternalUsers,
            ]
        ],
        security: [
            [
                'summit_sponsor_oauth2' => [
                    SummitScopes::ReadSummitData,
                    SummitScopes::ReadAllSummitData,
                ]
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
                name: 'sponsor_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The sponsor id'
            ),
            new OA\Parameter(
                name: 'page',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
            new OA\Parameter(
                name: 'per_page',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', example: 10)
            ),
            new OA\Parameter(
                name: 'order',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string'),
                description: 'Order by field (e.g., +id, -order)'
            ),
            new OA\Parameter(
                name: 'filter',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string'),
                description: 'Filter expression'
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Sponsor materials retrieved successfully'
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    /**
     * @param $summit_id
     * @param $sponsor_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */

    public function getMaterials($summit_id, $sponsor_id)
    {

        $summit = SummitFinderStrategyFactory::build($this->getSummitRepository(), $this->resource_server_context)->find($summit_id);
        if (is_null($summit)) return $this->error404();

        $sponsor = $summit->getSummitSponsorById(intval($sponsor_id));
        if (is_null($sponsor)) return $this->error404();

        return $this->_getAll(
            function () {
                return [
                    'sponsor_id' => ['=='],
                    'type' => ['==']
                ];
            },
            function () {
                return [
                    'sponsor_id' => 'sometimes|int',
                    'type' => 'sometimes|string|in:'.implode(',', SponsorMaterial::ValidTypes)
                ];
            },
            function () {
                return [
                    'id',
                    'order',
                ];
            },
            function ($filter) use ($sponsor) {
                if ($filter instanceof Filter) {
                    $filter->addFilterCondition(FilterElement::makeEqual('sponsor_id', $sponsor->getId()));
                }
                return $filter;
            },
            function () {
                return SerializerRegistry::SerializerType_Private;
            },
            null,
            null,
            function ($page, $per_page, $filter, $order, $applyExtraFilters) {
                return $this->sponsor_materials_repository->getAllByPage
                (
                    new PagingInfo($page, $per_page),
                    call_user_func($applyExtraFilters, $filter),
                    $order
                );
            }
        );
    }

    #[OA\Post(
        path: "/api/v1/summits/{id}/sponsors/{sponsor_id}/materials",
        description: "required-groups " . IGroup::SuperAdmins . ", " . IGroup::Administrators . ", " . IGroup::SummitAdministrators,
        summary: 'Add Sponsor Material',
        operationId: 'addSponsorMaterial',
        tags: ['Sponsors'],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
            ]
        ],
        security: [
            [
                'summit_sponsor_oauth2' => [
                    SummitScopes::WriteSummitData,
                ]
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
                name: 'sponsor_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The sponsor id'
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: "#/components/schemas/SponsorMaterialCreateRequest")
        ),
        responses: [
            new OA\Response(
                response: Response::HTTP_CREATED,
                description: 'Material created successfully',
                content: new OA\JsonContent(ref: '#/components/schemas/SponsorMaterial')
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error")
        ]
    )]
    /**
     * @param $summit_id
     * @param $sponsor_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function addMaterial($summit_id, $sponsor_id)
    {
        return $this->processRequest(function () use ($summit_id, $sponsor_id) {
            $summit = SummitFinderStrategyFactory::build($this->getSummitRepository(), $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $payload = $this->getJsonPayload(SponsorMaterialValidationRulesFactory::buildForAdd(), true);

            $material = $this->service->addSponsorMaterial($summit, intval($sponsor_id), $payload);

            return $this->created(SerializerRegistry::getInstance()
                ->getSerializer($material, SerializerRegistry::SerializerType_Private)
                ->serialize(
                    SerializerUtils::getExpand(),
                    SerializerUtils::getFields(),
                    SerializerUtils::getRelations()
                )
            );
        });
    }

    #[OA\Put(
        path: "/api/v1/summits/{id}/sponsors/{sponsor_id}/materials/{material_id}",
        description: "required-groups " . IGroup::SuperAdmins . ", " . IGroup::Administrators . ", " . IGroup::SummitAdministrators,
        summary: 'Update Sponsor Material',
        operationId: 'updateSponsorMaterial',
        tags: ['Sponsors'],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
            ]
        ],
        security: [
            [
                'summit_sponsor_oauth2' => [
                    SummitScopes::WriteSummitData,
                ]
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
                name: 'sponsor_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The sponsor id'
            ),
            new OA\Parameter(
                name: 'material_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The material id'
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: "#/components/schemas/SponsorMaterialUpdateRequest")
        ),
        responses: [
            new OA\Response(
                response: Response::HTTP_CREATED,
                description: 'Material updated successfully'
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error")
        ]
    )]
    /**
     * @param $summit_id
     * @param $sponsor_id
     * @param $material_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function updateMaterial($summit_id, $sponsor_id, $material_id)
    {
        return $this->processRequest(function () use ($summit_id, $sponsor_id, $material_id) {
            $summit = SummitFinderStrategyFactory::build($this->getSummitRepository(), $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $payload = $this->getJsonPayload(SponsorMaterialValidationRulesFactory::buildForUpdate(), true);

            $material = $this->service->updateSponsorMaterial($summit, intval($sponsor_id), intval($material_id), $payload);

            return $this->updated(SerializerRegistry::getInstance()
                ->getSerializer($material, SerializerRegistry::SerializerType_Private)
                ->serialize(
                    SerializerUtils::getExpand(),
                    SerializerUtils::getFields(),
                    SerializerUtils::getRelations()
                )
            );
        });
    }

    #[OA\Delete(
        path: "/api/v1/summits/{id}/sponsors/{sponsor_id}/materials/{material_id}",
        description: "required-groups " . IGroup::SuperAdmins . ", " . IGroup::Administrators . ", " . IGroup::SummitAdministrators,
        summary: 'Delete Sponsor Material',
        operationId: 'deleteSponsorMaterial',
        tags: ['Sponsors'],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
            ]
        ],
        security: [
            [
                'summit_sponsor_oauth2' => [
                    SummitScopes::WriteSummitData,
                ]
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
                name: 'sponsor_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The sponsor id'
            ),
            new OA\Parameter(
                name: 'material_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The material id'
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_NO_CONTENT,
                description: 'Material deleted successfully'
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    /**
     * @param $summit_id
     * @param $sponsor_id
     * @param $material_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function deleteMaterial($summit_id, $sponsor_id, $material_id)
    {
        return $this->processRequest(function () use ($summit_id, $sponsor_id, $material_id) {

            $summit = SummitFinderStrategyFactory::build($this->getSummitRepository(), $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $this->service->deleteSponsorMaterial($summit, intval($sponsor_id), intval($material_id));

            return $this->deleted();

        });
    }

    #[OA\Get(
        path: "/api/v1/summits/{id}/sponsors/{sponsor_id}/materials/{material_id}",
        description: "required-groups " . IGroup::SuperAdmins . ", " . IGroup::Administrators . ", " . IGroup::SummitAdministrators . ", " . IGroup::Sponsors . ", " . IGroup::SponsorExternalUsers,
        summary: 'Read Sponsor Material',
        operationId: 'getSponsorMaterial',
        tags: ['Sponsors'],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
                IGroup::Sponsors,
                IGroup::SponsorExternalUsers,
            ]
        ],
        security: [
            [
                'summit_sponsor_oauth2' => [
                    SummitScopes::ReadSummitData,
                    SummitScopes::ReadAllSummitData,
                ]
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
                name: 'sponsor_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The sponsor id'
            ),
            new OA\Parameter(
                name: 'material_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The material id'
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Material retrieved successfully'
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    /**
     * @param $summit_id
     * @param $sponsor_id
     * @param $material_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function getMaterial($summit_id, $sponsor_id, $material_id)
    {
        return $this->processRequest(function () use ($summit_id, $sponsor_id, $material_id) {

            $summit = SummitFinderStrategyFactory::build($this->getSummitRepository(), $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $sponsor = $summit->getSummitSponsorById(intval($sponsor_id));
            if (is_null($sponsor)) return $this->error404();

            $material = $sponsor->getMaterialById(intval($material_id));
            if (is_null($material)) return $this->error404();

            return $this->ok(SerializerRegistry::getInstance()
                ->getSerializer($material, SerializerRegistry::SerializerType_Private)
                ->serialize(
                    SerializerUtils::getExpand(),
                    SerializerUtils::getFields(),
                    SerializerUtils::getRelations()
                )
            );
        });
    }

    // Social Networks

    #[OA\Get(
        path: "/api/v1/summits/{id}/sponsors/{sponsor_id}/social-networks",
        description: "required-groups " . IGroup::SuperAdmins . ", " . IGroup::Administrators . ", " . IGroup::SummitAdministrators . ", " . IGroup::Sponsors . ", " . IGroup::SponsorExternalUsers,
        summary: 'Read Sponsor Social Networks',
        operationId: 'getSponsorSocialNetworks',
        tags: ['Sponsors'],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
                IGroup::Sponsors,
                IGroup::SponsorExternalUsers,
            ]
        ],
        security: [
            [
                'summit_sponsor_oauth2' => [
                    SummitScopes::ReadSummitData,
                    SummitScopes::ReadAllSummitData,
                ]
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
                name: 'sponsor_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The sponsor id'
            ),
            new OA\Parameter(
                name: 'page',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
            new OA\Parameter(
                name: 'per_page',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', example: 10)
            ),
            new OA\Parameter(
                name: 'order',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string'),
                description: 'Order by field (e.g., +id)'
            ),
            new OA\Parameter(
                name: 'filter',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string'),
                description: 'Filter expression'
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Social networks retrieved successfully'
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    /**
     * @param $summit_id
     * @param $sponsor_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function getSocialNetworks($summit_id, $sponsor_id)
    {

        $summit = SummitFinderStrategyFactory::build($this->getSummitRepository(), $this->resource_server_context)->find($summit_id);
        if (is_null($summit)) return $this->error404();

        $sponsor = $summit->getSummitSponsorById(intval($sponsor_id));
        if (is_null($sponsor)) return $this->error404();

        return $this->_getAll(
            function () {
                return [
                    'sponsor_id' => ['=='],
                ];
            },
            function () {
                return [
                    'sponsor_id' => 'sometimes|int',
                ];
            },
            function () {
                return [
                    'id',
                ];
            },
            function ($filter) use ($sponsor) {
                if ($filter instanceof Filter) {
                    $filter->addFilterCondition(FilterElement::makeEqual('sponsor_id', $sponsor->getId()));
                }
                return $filter;
            },
            function () {
                return SerializerRegistry::SerializerType_Private;
            },
            null,
            null,
            function ($page, $per_page, $filter, $order, $applyExtraFilters) {
                return $this->sponsor_social_network_repository->getAllByPage
                (
                    new PagingInfo($page, $per_page),
                    call_user_func($applyExtraFilters, $filter),
                    $order
                );
            }
        );
    }

    #[OA\Post(
        path: "/api/v1/summits/{id}/sponsors/{sponsor_id}/social-networks",
        description: "required-groups " . IGroup::SuperAdmins . ", " . IGroup::Administrators . ", " . IGroup::SummitAdministrators,
        summary: 'Add Sponsor Social Network',
        operationId: 'addSponsorSocialNetwork',
        tags: ['Sponsors'],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
            ]
        ],
        security: [
            [
                'summit_sponsor_oauth2' => [
                    SummitScopes::WriteSummitData,
                ]
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
                name: 'sponsor_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The sponsor id'
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: "#/components/schemas/SponsorSocialNetworkCreateRequest")
        ),
        responses: [
            new OA\Response(
                response: Response::HTTP_CREATED,
                description: 'Social network created successfully',
                content: new OA\JsonContent(ref: '#/components/schemas/SponsorSocialNetwork')
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error")
        ]
    )]
    /**
     * @param $summit_id
     * @param $sponsor_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function addSocialNetwork($summit_id, $sponsor_id)
    {
        return $this->processRequest(function () use ($summit_id, $sponsor_id) {
            $summit = SummitFinderStrategyFactory::build($this->getSummitRepository(), $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $payload = $this->getJsonPayload(SponsorSocialNetworkValidationRulesFactory::buildForAdd(), true);

            $social_network = $this->service->addSponsorSocialNetwork($summit, intval($sponsor_id), $payload);

            return $this->created(SerializerRegistry::getInstance()
                ->getSerializer($social_network, SerializerRegistry::SerializerType_Private)
                ->serialize(
                    SerializerUtils::getExpand(),
                    SerializerUtils::getFields(),
                    SerializerUtils::getRelations()
                )
            );
        });
    }

    #[OA\Get(
        path: "/api/v1/summits/{id}/sponsors/{sponsor_id}/social-networks/{social_network_id}",
        description: "required-groups " . IGroup::SuperAdmins . ", " . IGroup::Administrators . ", " . IGroup::SummitAdministrators . ", " . IGroup::Sponsors . ", " . IGroup::SponsorExternalUsers,
        summary: 'Read Sponsor Social Network',
        operationId: 'getSponsorSocialNetwork',
        tags: ['Sponsors'],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
                IGroup::Sponsors,
                IGroup::SponsorExternalUsers,
            ]
        ],
        security: [
            [
                'summit_sponsor_oauth2' => [
                    SummitScopes::ReadSummitData,
                    SummitScopes::ReadAllSummitData,
                ]
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
                name: 'sponsor_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The sponsor id'
            ),
            new OA\Parameter(
                name: 'social_network_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The social network id'
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Social network retrieved successfully'
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    /**
     * @param $summit_id
     * @param $sponsor_id
     * @param $social_network_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function getSocialNetwork($summit_id, $sponsor_id, $social_network_id)
    {
        return $this->processRequest(function () use ($summit_id, $sponsor_id, $social_network_id) {

            $summit = SummitFinderStrategyFactory::build($this->getSummitRepository(), $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $sponsor = $summit->getSummitSponsorById(intval($sponsor_id));
            if (is_null($sponsor)) return $this->error404();

            $social_network = $sponsor->getSocialNetworkById(intval($social_network_id));
            if (is_null($social_network)) return $this->error404();

            return $this->ok(SerializerRegistry::getInstance()
                ->getSerializer($social_network, SerializerRegistry::SerializerType_Private)
                ->serialize(
                    SerializerUtils::getExpand(),
                    SerializerUtils::getFields(),
                    SerializerUtils::getRelations()
                )
            );
        });
    }

    #[OA\Put(
        path: "/api/v1/summits/{id}/sponsors/{sponsor_id}/social-networks/{social_network_id}",
        description: "required-groups " . IGroup::SuperAdmins . ", " . IGroup::Administrators . ", " . IGroup::SummitAdministrators,
        summary: 'Update Sponsor Social Network',
        operationId: 'updateSponsorSocialNetwork',
        tags: ['Sponsors'],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
            ]
        ],
        security: [
            [
                'summit_sponsor_oauth2' => [
                    SummitScopes::WriteSummitData,
                ]
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
                name: 'sponsor_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The sponsor id'
            ),
            new OA\Parameter(
                name: 'social_network_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The social network id'
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: "#/components/schemas/SponsorSocialNetworkUpdateRequest")
        ),
        responses: [
            new OA\Response(
                response: Response::HTTP_CREATED,
                description: 'Social network updated successfully'
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error")
        ]
    )]
    /**
     * @param $summit_id
     * @param $sponsor_id
     * @param $social_network_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function updateSocialNetwork($summit_id, $sponsor_id, $social_network_id)
    {
        return $this->processRequest(function () use ($summit_id, $sponsor_id, $social_network_id) {
            $summit = SummitFinderStrategyFactory::build($this->getSummitRepository(), $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $payload = $this->getJsonPayload(SponsorSocialNetworkValidationRulesFactory::buildForUpdate(), true);

            $social_network = $this->service->updateSponsorSocialNetwork($summit, intval($sponsor_id), intval($social_network_id), $payload);

            return $this->updated(SerializerRegistry::getInstance()
                ->getSerializer($social_network, SerializerRegistry::SerializerType_Private)
                ->serialize(
                    SerializerUtils::getExpand(),
                    SerializerUtils::getFields(),
                    SerializerUtils::getRelations()
                )
            );
        });
    }

    #[OA\Delete(
        path: "/api/v1/summits/{id}/sponsors/{sponsor_id}/social-networks/{social_network_id}",
        description: "required-groups " . IGroup::SuperAdmins . ", " . IGroup::Administrators . ", " . IGroup::SummitAdministrators,
        summary: 'Delete Sponsor Social Network',
        operationId: 'deleteSponsorSocialNetwork',
        tags: ['Sponsors'],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
            ]
        ],
        security: [
            [
                'summit_sponsor_oauth2' => [
                    SummitScopes::WriteSummitData,
                ]
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
                name: 'sponsor_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The sponsor id'
            ),
            new OA\Parameter(
                name: 'social_network_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The social network id'
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_NO_CONTENT,
                description: 'Social network deleted successfully'
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    /**
     * @param $summit_id
     * @param $sponsor_id
     * @param $social_network_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function deleteSocialNetwork($summit_id, $sponsor_id, $social_network_id)
    {
        return $this->processRequest(function () use ($summit_id, $sponsor_id, $social_network_id) {

            $summit = SummitFinderStrategyFactory::build($this->getSummitRepository(), $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $this->service->deleteSponsorSocialNetwork($summit, intval($sponsor_id), intval($social_network_id));

            return $this->deleted();

        });
    }

    // Extra Questions

    #[OA\Get(
        path: "/api/v1/summits/{id}/sponsors/{sponsor_id}/extra-questions",
        description: "required-groups " . IGroup::SuperAdmins . ", " . IGroup::Administrators . ", " . IGroup::SummitAdministrators . ", " . IGroup::Sponsors,
        summary: 'Read Sponsor Extra Questions',
        operationId: 'getSponsorExtraQuestions',
        tags: ['Sponsors'],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
                IGroup::Sponsors,
            ]
        ],
        security: [
            [
                'summit_sponsor_oauth2' => [
                    SummitScopes::ReadSummitData,
                    SummitScopes::ReadAllSummitData,
                ]
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
                name: 'sponsor_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The sponsor id'
            ),
            new OA\Parameter(
                name: 'page',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
            new OA\Parameter(
                name: 'per_page',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', example: 10)
            ),
            new OA\Parameter(
                name: 'order',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string'),
                description: 'Order by field (e.g., +id, -name, +order)'
            ),
            new OA\Parameter(
                name: 'filter',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string'),
                description: 'Filter expression'
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Extra questions retrieved successfully'
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    /**
     * @param $summit_id
     * @param $sponsor_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function getExtraQuestions($summit_id, $sponsor_id)
    {
        $summit = SummitFinderStrategyFactory::build($this->getSummitRepository(), $this->resource_server_context)->find($summit_id);
        if (is_null($summit)) return $this->error404();

        $sponsor = $summit->getSummitSponsorById(intval($sponsor_id));
        if (is_null($sponsor)) return $this->error404();

        // authz check
        $current_member = $this->resource_server_context->getCurrentUser();
        if(!$current_member->isAuthzFor($summit, $sponsor))
            throw new HTTP403ForbiddenException("You are not allowed to perform this action");

        return $this->_getAll(
            function () {
                return [
                    'name' => ['==', '=@'],
                    'label' => ['==', '=@'],
                    'type' => ['==']
                ];
            },
            function () {
                return [
                    'name' => 'sometimes|string',
                    'label' => 'sometimes|string',
                    'type' => sprintf('sometimes|in:%s', implode(',', Sponsor::getAllowedQuestionTypes())),
                ];
            },
            function () {
                return [
                    'id',
                    'name',
                    'label',
                    'order',
                    'type'
                ];
            },
            function ($filter) use ($sponsor) {
                if ($filter instanceof Filter) {
                    $filter->addFilterCondition(FilterElement::makeEqual('sponsor_id', $sponsor->getId()));
                }
                return $filter;
            },
            function () {
                return SerializerRegistry::SerializerType_Private;
            },
            null,
            null,
            function ($page, $per_page, $filter, $order, $applyExtraFilters) {
                return $this->sponsor_extra_question_repository->getAllByPage
                (
                    new PagingInfo($page, $per_page),
                    call_user_func($applyExtraFilters, $filter),
                    $order
                );
            }
        );
    }

    #[OA\Get(
        path: "/api/v1/summits/{id}/sponsors/all/extra-questions/metadata",
        description: "required-groups " . IGroup::SuperAdmins . ", " . IGroup::Administrators . ", " . IGroup::SummitAdministrators . ", " . IGroup::Sponsors . ", " . IGroup::SponsorExternalUsers,
        summary: 'Read Sponsor Extra Questions Metadata',
        operationId: 'getSponsorExtraQuestionsMetadata',
        tags: ['Sponsors'],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
                IGroup::Sponsors,
                IGroup::SponsorExternalUsers,
            ]
        ],
        security: [
            [
                'summit_sponsor_oauth2' => [
                    SummitScopes::ReadSummitData,
                    SummitScopes::ReadAllSummitData,
                ]
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
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Extra questions metadata retrieved successfully'
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
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
            $this->sponsor_extra_question_repository->getQuestionsMetadata()
        );
    }

    #[OA\Post(
        path: "/api/v1/summits/{id}/sponsors/{sponsor_id}/extra-questions",
        description: "required-groups " . IGroup::SuperAdmins . ", " . IGroup::Administrators . ", " . IGroup::SummitAdministrators . ", " . IGroup::Sponsors . ", " . IGroup::SponsorExternalUsers,
        summary: 'Add Sponsor Extra Question',
        operationId: 'addSponsorExtraQuestion',
        tags: ['Sponsors'],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
                IGroup::Sponsors,
                IGroup::SponsorExternalUsers,
            ]
        ],
        security: [
            [
                'summit_sponsor_oauth2' => [
                    SummitScopes::WriteSummitData,
                ]
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
                name: 'sponsor_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The sponsor id'
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: "#/components/schemas/SponsorExtraQuestionCreateRequest")
        ),
        responses: [
            new OA\Response(
                response: Response::HTTP_CREATED,
                description: 'Extra question created successfully',
                content: new OA\JsonContent(ref: '#/components/schemas/SummitSponsorExtraQuestionType')
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error")
        ]
    )]
    /**
     * @param $summit_id
     * @param $sponsor_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function addExtraQuestion($summit_id, $sponsor_id)
    {
        return $this->processRequest(function () use ($summit_id, $sponsor_id) {
            $summit = SummitFinderStrategyFactory::build($this->getSummitRepository(), $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $sponsor = $summit->getSummitSponsorById(intval($sponsor_id));
            if (is_null($sponsor)) return $this->error404();

            // authz check
            $current_member = $this->resource_server_context->getCurrentUser();
            if(!$current_member->isAuthzFor($summit, $sponsor))
                throw new HTTP403ForbiddenException("You are not allowed to perform this action.");

            $payload = $this->getJsonPayload(SponsorExtraQuestionValidationRulesFactory::buildForAdd(), true);

            $extra_question = $this->service->addSponsorExtraQuestion($summit, intval($sponsor_id), $payload);

            return $this->created(SerializerRegistry::getInstance()
                ->getSerializer($extra_question, SerializerRegistry::SerializerType_Private)
                ->serialize(
                    SerializerUtils::getExpand(),
                    SerializerUtils::getFields(),
                    SerializerUtils::getRelations()
                )
            );
        });
    }

    #[OA\Get(
        path: "/api/v1/summits/{id}/sponsors/{sponsor_id}/extra-questions/{extra_question_id}",
        description: "required-groups " . IGroup::SuperAdmins . ", " . IGroup::Administrators . ", " . IGroup::SummitAdministrators . ", " . IGroup::Sponsors . ", " . IGroup::SponsorExternalUsers,
        summary: 'Read Sponsor Extra Question',
        operationId: 'getSponsorExtraQuestion',
        tags: ['Sponsors'],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
                IGroup::Sponsors,
                IGroup::SponsorExternalUsers,
            ]
        ],
        security: [
            [
                'summit_sponsor_oauth2' => [
                    SummitScopes::ReadSummitData,
                    SummitScopes::ReadAllSummitData,
                ]
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
                name: 'sponsor_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The sponsor id'
            ),
            new OA\Parameter(
                name: 'extra_question_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The extra question id'
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Extra question retrieved successfully'
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    /**
     * @param $summit_id
     * @param $sponsor_id
     * @param $extra_question_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function getExtraQuestion($summit_id, $sponsor_id, $extra_question_id)
    {
        return $this->processRequest(function () use ($summit_id, $sponsor_id, $extra_question_id) {

            $summit = SummitFinderStrategyFactory::build($this->getSummitRepository(), $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $sponsor = $summit->getSummitSponsorById(intval($sponsor_id));
            if (is_null($sponsor)) return $this->error404();

            // authz check
            $current_member = $this->resource_server_context->getCurrentUser();
            if(!$current_member->isAuthzFor($summit, $sponsor))
                throw new HTTP403ForbiddenException("You are not allowed to perform this action");

            $extra_question = $sponsor->getExtraQuestionById(intval($extra_question_id));
            if (is_null($extra_question)) return $this->error404();

            return $this->ok(SerializerRegistry::getInstance()
                ->getSerializer($extra_question, SerializerRegistry::SerializerType_Private)
                ->serialize(
                    SerializerUtils::getExpand(),
                    SerializerUtils::getFields(),
                    SerializerUtils::getRelations()
                )
            );
        });
    }

    #[OA\Put(
        path: "/api/v1/summits/{id}/sponsors/{sponsor_id}/extra-questions/{extra_question_id}",
        description: "required-groups " . IGroup::SuperAdmins . ", " . IGroup::Administrators . ", " . IGroup::SummitAdministrators . ", " . IGroup::Sponsors . ", " . IGroup::SponsorExternalUsers,
        summary: 'Update Sponsor Extra Question',
        operationId: 'updateSponsorExtraQuestion',
        tags: ['Sponsors'],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
                IGroup::Sponsors,
                IGroup::SponsorExternalUsers,
            ]
        ],
        security: [
            [
                'summit_sponsor_oauth2' => [
                    SummitScopes::WriteSummitData,
                ]
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
                name: 'sponsor_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The sponsor id'
            ),
            new OA\Parameter(
                name: 'extra_question_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The extra question id'
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: "#/components/schemas/SponsorExtraQuestionUpdateRequest")
        ),
        responses: [
            new OA\Response(
                response: Response::HTTP_CREATED,
                description: 'Extra question updated successfully'
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error")
        ]
    )]
    /**
     * @param $summit_id
     * @param $sponsor_id
     * @param $social_network_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function updateExtraQuestion($summit_id, $sponsor_id, $extra_question_id)
    {
        return $this->processRequest(function () use ($summit_id, $sponsor_id, $extra_question_id) {
            $summit = SummitFinderStrategyFactory::build($this->getSummitRepository(), $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $sponsor = $summit->getSummitSponsorById(intval($sponsor_id));
            if (is_null($sponsor)) return $this->error404();

            // authz check
            $current_member = $this->resource_server_context->getCurrentUser();
            if(!$current_member->isAuthzFor( $summit, $sponsor))
                throw new HTTP403ForbiddenException("You are not allowed to perform this action");

            $payload = $this->getJsonPayload(SponsorExtraQuestionValidationRulesFactory::buildForUpdate(), true);

            $extra_question = $this->service->updateSponsorExtraQuestion($summit, intval($sponsor_id), intval($extra_question_id), $payload);

            return $this->updated(SerializerRegistry::getInstance()
                ->getSerializer($extra_question, SerializerRegistry::SerializerType_Private)
                ->serialize(
                    SerializerUtils::getExpand(),
                    SerializerUtils::getFields(),
                    SerializerUtils::getRelations()
                )
            );
        });
    }

    #[OA\Delete(
        path: "/api/v1/summits/{id}/sponsors/{sponsor_id}/extra-questions/{extra_question_id}",
        description: "required-groups " . IGroup::SuperAdmins . ", " . IGroup::Administrators . ", " . IGroup::SummitAdministrators . ", " . IGroup::Sponsors . ", " . IGroup::SponsorExternalUsers,
        summary: 'Delete Sponsor Extra Question',
        operationId: 'deleteSponsorExtraQuestion',
        tags: ['Sponsors'],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
                IGroup::Sponsors,
                IGroup::SponsorExternalUsers,
            ]
        ],
        security: [
            [
                'summit_sponsor_oauth2' => [
                    SummitScopes::WriteSummitData,
                ]
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
                name: 'sponsor_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The sponsor id'
            ),
            new OA\Parameter(
                name: 'extra_question_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The extra question id'
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_NO_CONTENT,
                description: 'Extra question deleted successfully'
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    /**
     * @param $summit_id
     * @param $sponsor_id
     * @param $social_network_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function deleteExtraQuestion($summit_id, $sponsor_id, $extra_question_id)
    {
        return $this->processRequest(function () use ($summit_id, $sponsor_id, $extra_question_id) {

            $summit = SummitFinderStrategyFactory::build($this->getSummitRepository(), $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $sponsor = $summit->getSummitSponsorById(intval($sponsor_id));
            if (is_null($sponsor)) return $this->error404();

            // authz check
            $current_member = $this->resource_server_context->getCurrentUser();
            if(!$current_member->isAuthzFor($summit, $sponsor))
                throw new HTTP403ForbiddenException("You are not allowed to perform this action");

            $this->service->deleteSponsorExtraQuestion($summit, intval($sponsor_id), intval($extra_question_id));

            return $this->deleted();

        });
    }

    // Question Values
    use ParametrizedAddEntity;
    #[OA\Post(
        path: "/api/v1/summits/{id}/sponsors/{sponsor_id}/extra-questions/{extra_question_id}/values",
        description: "required-groups " . IGroup::SuperAdmins . ", " . IGroup::Administrators . ", " . IGroup::SummitAdministrators . ", " . IGroup::Sponsors . ", " . IGroup::SponsorExternalUsers,
        summary: 'Add Extra Question Value',
        operationId: 'addSponsorExtraQuestionValue',
        tags: ['Sponsors'],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
                IGroup::Sponsors,
                IGroup::SponsorExternalUsers,
            ]
        ],
        security: [
            [
                'summit_sponsor_oauth2' => [
                    SummitScopes::WriteSummitData,
                ]
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
                name: 'sponsor_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The sponsor id'
            ),
            new OA\Parameter(
                name: 'extra_question_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The extra question id'
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: "#/components/schemas/ExtraQuestionValueCreateRequest")
        ),
        responses: [
            new OA\Response(
                response: Response::HTTP_CREATED,
                description: 'Question value created successfully',
                content: new OA\JsonContent(ref: '#/components/schemas/ExtraQuestionTypeValue')
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error")
        ]
    )]
    /**
     * @param $summit_id
     * @param $sponsor_id
     * @param $extra_question_id
     * @return mixed
     */
    public function addExtraQuestionValue($summit_id, $sponsor_id, $extra_question_id)
    {
        return $this->processRequest(function () use ($summit_id, $sponsor_id, $extra_question_id) {
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $sponsor = $summit->getSummitSponsorById(intval($sponsor_id));
            if (is_null($sponsor)) return $this->error404();

            $args = [$summit, intval($sponsor_id), intval($extra_question_id)];

            // authz check
            $current_member = $this->resource_server_context->getCurrentUser();
            if(!$current_member->isAuthzFor( $summit, $sponsor))
                throw new HTTP403ForbiddenException("You are not allowed to perform this action");

            return $this->_add(
                function ($payload) {
                    return ExtraQuestionTypeValueValidationRulesFactory::buildForAdd($payload);
                },
                function ($payload, $summit, $sponsor_id,  $question_id) {
                    return $this->service->addExtraQuestionValue
                    (
                        $summit, intval($sponsor_id), intval($question_id), $payload
                    );
                },
                ...$args);
        });
    }

    use ParametrizedUpdateEntity;

    #[OA\Put(
        path: "/api/v1/summits/{id}/sponsors/{sponsor_id}/extra-questions/{extra_question_id}/values/{value_id}",
        description: "required-groups " . IGroup::SuperAdmins . ", " . IGroup::Administrators . ", " . IGroup::SummitAdministrators . ", " . IGroup::Sponsors . ", " . IGroup::SponsorExternalUsers,
        summary: 'Update Extra Question Value',
        operationId: 'updateSponsorExtraQuestionValue',
        tags: ['Sponsors'],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
                IGroup::Sponsors,
                IGroup::SponsorExternalUsers,
            ]
        ],
        security: [
            [
                'summit_sponsor_oauth2' => [
                    SummitScopes::WriteSummitData,
                ]
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
                name: 'sponsor_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The sponsor id'
            ),
            new OA\Parameter(
                name: 'extra_question_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The extra question id'
            ),
            new OA\Parameter(
                name: 'value_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The question value id'
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: "#/components/schemas/ExtraQuestionValueUpdateRequest")
        ),
        responses: [
            new OA\Response(
                response: Response::HTTP_CREATED,
                description: 'Question value updated successfully'
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error")
        ]
    )]
    /**
     * @param $summit_id
     * @param $sponsor_id
     * @param $extra_question_id
     * @param $value_id
     * @return mixed
     */
    public function updateExtraQuestionValue($summit_id, $sponsor_id, $extra_question_id, $value_id)
    {
        return $this->processRequest(function () use ($summit_id, $sponsor_id, $extra_question_id, $value_id) {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $sponsor = $summit->getSummitSponsorById(intval($sponsor_id));
            if (is_null($sponsor)) return $this->error404();

            $args = [$summit, intval($sponsor_id), intval($extra_question_id)];

            // authz check
            $current_member = $this->resource_server_context->getCurrentUser();
            if(!$current_member->isAuthzFor( $summit, $sponsor))
                throw new HTTP403ForbiddenException("You are not allowed to perform this action");

            return $this->_update($value_id, function ($payload) {
                return ExtraQuestionTypeValueValidationRulesFactory::buildForUpdate($payload);
            },
                function ($value_id, $payload, $summit, $sponsor_id,  $extra_question_id) {
                    return $this->service->updateExtraQuestionValue
                    (
                        $summit,
                        intval($sponsor_id),
                        intval($extra_question_id),
                        intval($value_id),
                        $payload
                    );
                }, ...$args);
        });
    }

    use ParametrizedDeleteEntity;
    #[OA\Delete(
        path: "/api/v1/summits/{id}/sponsors/{sponsor_id}/extra-questions/{extra_question_id}/values/{value_id}",
        description: "required-groups " . IGroup::SuperAdmins . ", " . IGroup::Administrators . ", " . IGroup::SummitAdministrators . ", " . IGroup::Sponsors . ", " . IGroup::SponsorExternalUsers,
        summary: 'Delete Extra Question Value',
        operationId: 'deleteSponsorExtraQuestionValue',
        tags: ['Sponsors'],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
                IGroup::Sponsors,
                IGroup::SponsorExternalUsers,
            ]
        ],
        security: [
            [
                'summit_sponsor_oauth2' => [
                    SummitScopes::WriteSummitData,
                ]
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
                name: 'sponsor_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The sponsor id'
            ),
            new OA\Parameter(
                name: 'extra_question_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The extra question id'
            ),
            new OA\Parameter(
                name: 'value_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The question value id'
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_NO_CONTENT,
                description: 'Question value deleted successfully'
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error")
        ]
    )]
    /**
     * @param $summit_id
     * @param $sponsor_id
     * @param $extra_question_id
     * @param $value_id
     * @return mixed
     */
    public function deleteExtraQuestionValue($summit_id, $sponsor_id, $extra_question_id, $value_id)
    {
        return $this->processRequest(function () use ($summit_id, $sponsor_id, $extra_question_id, $value_id) {
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $sponsor = $summit->getSummitSponsorById(intval($sponsor_id));
            if (is_null($sponsor)) return $this->error404();

            $args = [$summit, intval($sponsor_id), intval($extra_question_id)];

            // authz check
            $current_member = $this->resource_server_context->getCurrentUser();
            if(!$current_member->isAuthzFor($summit, $sponsor))
                throw new HTTP403ForbiddenException("You are not allowed to perform this action");

            return $this->_delete($value_id, function ($value_id, $summit, $sponsor_id, $extra_question_id) {
                $this->service->deleteExtraQuestionValue($summit, intval($sponsor_id), intval($extra_question_id), intval($value_id));
            }
                , ...$args);
        });

    }

    #[OA\Get(
        path: "/api/v1/summits/{id}/sponsors/{sponsor_id}/lead-report-settings/metadata",
        description: "required-groups " . IGroup::SuperAdmins . ", " . IGroup::Administrators . ", " . IGroup::SummitAdministrators . ", " . IGroup::Sponsors . ", " . IGroup::SponsorExternalUsers,
        summary: 'Get Lead Report Settings Metadata',
        operationId: 'getLeadReportSettingsMetadata',
        tags: ['Sponsors'],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
                IGroup::Sponsors,
                IGroup::SponsorExternalUsers,
            ]
        ],
        security: [
            [
                'summit_sponsor_oauth2' => [
                    SummitScopes::ReadSummitData,
                    SummitScopes::ReadAllSummitData,
                ]
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
                name: 'sponsor_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The sponsor id'
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Lead report settings metadata retrieved'
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error")
        ]
    )]
    /**
     * @param $summit_id
     * @param $sponsor_id
     * @return mixed
     */
    public function getLeadReportSettingsMetadata($summit_id, $sponsor_id) {
        return $this->processRequest(function () use ($summit_id, $sponsor_id) {

            $summit = SummitFinderStrategyFactory::build($this->getSummitRepository(), $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $sponsor = $summit->getSummitSponsorById(intval($sponsor_id));
            if (is_null($sponsor)) return $this->error404();

            return $this->ok($summit->getLeadReportSettingsMetadata($sponsor));
        });
    }

    #[OA\Post(
        path: "/api/v1/summits/{id}/sponsors/{sponsor_id}/lead-report-settings",
        description: "required-groups " . IGroup::SuperAdmins . ", " . IGroup::Administrators . ", " . IGroup::SummitAdministrators,
        summary: 'Add Lead Report Settings',
        operationId: 'addLeadReportSettings',
        tags: ['Sponsors'],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
            ]
        ],
        security: [
            [
                'summit_sponsor_oauth2' => [
                    SummitScopes::WriteSummitData,
                ]
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
                name: 'sponsor_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The sponsor id'
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: "#/components/schemas/LeadReportSettingsCreateRequest")
        ),
        responses: [
            new OA\Response(
                response: Response::HTTP_CREATED,
                description: 'Lead report settings created successfully',
                content: new OA\JsonContent(ref: '#/components/schemas/SummitLeadReportSetting')
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error")
        ]
    )]
    /**
     * @param $summit_id
     * @param $sponsor_id
     * @return mixed
     */
    public function addLeadReportSettings($summit_id, $sponsor_id) {
        return $this->processRequest(function () use ($summit_id, $sponsor_id) {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $sponsor = $summit->getSummitSponsorById(intval($sponsor_id));
            if (is_null($sponsor)) return $this->error404();

            $payload = $this->getJsonPayload(LeadReportSettingsValidationRulesFactory::buildForAdd(), true);

            $settings = $this->service->addLeadReportSettings($summit, $sponsor->getId(), $payload);

            return $this->created(SerializerRegistry::getInstance()
                ->getSerializer($settings)
                ->serialize(
                    SerializerUtils::getExpand(),
                    SerializerUtils::getFields(),
                    SerializerUtils::getRelations()
                )
            );
        });
    }

    #[OA\Put(
        path: "/api/v1/summits/{id}/sponsors/{sponsor_id}/lead-report-settings",
        description: "required-groups " . IGroup::SuperAdmins . ", " . IGroup::Administrators . ", " . IGroup::SummitAdministrators,
        summary: 'Update Lead Report Settings',
        operationId: 'updateLeadReportSettings',
        tags: ['Sponsors'],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
            ]
        ],
        security: [
            [
                'summit_sponsor_oauth2' => [
                    SummitScopes::WriteSummitData,
                ]
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
                name: 'sponsor_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The sponsor id'
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: "#/components/schemas/LeadReportSettingsUpdateRequest")
        ),
        responses: [
            new OA\Response(
                response: Response::HTTP_CREATED,
                description: 'Lead report settings updated successfully'
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error")
        ]
    )]
    /**
     * @param $summit_id
     * @param $sponsor_id
     * @return mixed
     */
    public function updateLeadReportSettings($summit_id, $sponsor_id) {
        return $this->processRequest(function () use ($summit_id, $sponsor_id) {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $sponsor = $summit->getSummitSponsorById(intval($sponsor_id));
            if (is_null($sponsor)) return $this->error404();

            $payload = $this->getJsonPayload(LeadReportSettingsValidationRulesFactory::buildForUpdate(), true);

            $settings = $this->service->updateLeadReportSettings($summit, $sponsor->getId(), $payload);

            return $this->updated(SerializerRegistry::getInstance()
                ->getSerializer($settings)
                ->serialize(
                    SerializerUtils::getExpand(),
                    SerializerUtils::getFields(),
                    SerializerUtils::getRelations()
                )
            );
        });
    }
}
