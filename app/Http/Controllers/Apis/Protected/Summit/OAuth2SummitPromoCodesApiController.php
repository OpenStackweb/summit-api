<?php namespace App\Http\Controllers;
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

use App\Http\Exceptions\HTTP403ForbiddenException;
use App\Http\Utils\BooleanCellFormatter;
use App\Http\Utils\EpochCellFormatter;
use App\Http\Utils\Filters\FiltersParams;
use App\Jobs\Emails\Registration\PromoCodes\SponsorPromoCodeEmail;
use App\Models\Foundation\Main\IGroup;
use App\Models\Foundation\Summit\PromoCodes\PromoCodesConstants;
use App\Models\Foundation\Summit\Repositories\ISpeakersRegistrationDiscountCodeRepository;
use App\Models\Foundation\Summit\Repositories\ISpeakersSummitRegistrationPromoCodeRepository;
use App\ModelSerializers\SerializerUtils;
use App\Rules\Boolean;
use App\Security\SummitScopes;
use Illuminate\Http\Request as LaravelRequest;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Request;
use models\main\IMemberRepository;
use models\oauth2\IResourceServerContext;
use models\summit\ISummitRegistrationPromoCodeRepository;
use models\summit\ISummitRepository;
use models\summit\SpeakersRegistrationDiscountCode;
use models\summit\SpeakersSummitRegistrationPromoCode;
use models\summit\SponsorSummitRegistrationDiscountCode;
use models\summit\SponsorSummitRegistrationPromoCode;
use models\summit\SummitRegistrationPromoCode;
use models\summit\SummitTicketType;
use ModelSerializers\SerializerRegistry;
use OpenApi\Attributes as OA;
use services\model\ISummitPromoCodeService;
use utils\Filter;
use utils\FilterElement;
use utils\FilterParser;
use utils\Order;
use utils\OrderElement;
use utils\PagingInfo;

/**
 * Class OAuth2SummitPromoCodesApiController
 * @package App\Http\Controllers
 */
final class OAuth2SummitPromoCodesApiController extends OAuth2ProtectedController
{

    /**
     * @var ISummitRepository
     */
    private $summit_repository;

    /**
     * @var IMemberRepository
     */
    private $member_repository;

    /**
     * @var ISpeakersSummitRegistrationPromoCodeRepository
     */
    private $speakers_promo_code_repository;

    /**
     * @var ISpeakersRegistrationDiscountCodeRepository
     */
    private $speakers_discount_code_repository;

    /**
     * @var ISummitPromoCodeService
     */
    private $promo_code_service;

    use RequestProcessor;

    use GetAndValidateJsonPayload;

    use ParametrizedGetAll;

    /**
     * OAuth2SummitPromoCodesApiController constructor.
     * @param ISummitRepository $summit_repository
     * @param ISummitRegistrationPromoCodeRepository $promo_code_repository
     * @param ISpeakersSummitRegistrationPromoCodeRepository $speakers_promo_code_repository
     * @param ISpeakersRegistrationDiscountCodeRepository $speakers_discount_code_repository
     * @param IMemberRepository $member_repository
     * @param ISummitPromoCodeService $promo_code_service
     * @param IResourceServerContext $resource_server_context
     */
    public function __construct
    (
        ISummitRepository                               $summit_repository,
        ISummitRegistrationPromoCodeRepository          $promo_code_repository,
        ISpeakersSummitRegistrationPromoCodeRepository  $speakers_promo_code_repository,
        ISpeakersRegistrationDiscountCodeRepository     $speakers_discount_code_repository,
        IMemberRepository                               $member_repository,
        ISummitPromoCodeService                         $promo_code_service,
        IResourceServerContext                          $resource_server_context
    )
    {
        parent::__construct($resource_server_context);
        $this->promo_code_service = $promo_code_service;
        $this->repository = $promo_code_repository;
        $this->summit_repository = $summit_repository;
        $this->member_repository = $member_repository;
        $this->speakers_promo_code_repository = $speakers_promo_code_repository;
        $this->speakers_discount_code_repository = $speakers_discount_code_repository;
    }

    #[OA\Get(
        path: "/api/v1/summits/{id}/promo-codes",
        summary: "Get all promo codes for a summit",
        description: "Get all promo codes for a summit with filtering and pagination",
        operationId: "getAllPromoCodesBySummit",
        tags: ["Promo Codes"],
        security: [['summit_promo_codes_oauth2' => [SummitScopes::ReadAllSummitData]]],
        x: ['required-groups' => [IGroup::SuperAdmins, IGroup::Administrators, IGroup::SummitAdministrators, IGroup::SummitRegistrationAdmins]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "page", in: "query", required: false, schema: new OA\Schema(type: "integer", default: 1)),
            new OA\Parameter(name: "per_page", in: "query", required: false, schema: new OA\Schema(type: "integer", default: 10)),
            new OA\Parameter(name: "filter", in: "query", required: false, schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "order", in: "query", required: false, schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "expand", in: "query", required: false, schema: new OA\Schema(type: "string")),
        ],
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: "OK"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Summit not found"),
        ]
    )]
    /**
     * @param $summit_id
     * @return mixed
     */
    public function getAllBySummit($summit_id)
    {

        $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
        if (is_null($summit)) return $this->error404();

        return $this->_getAll(
            function () {
                return [
                    'code' => ['@@', '=@', '=='],
                    'description' => ['@@', '=@'],
                    'notes' => ['@@', '=@'],
                    'creator' => ['@@', '=@', '=='],
                    'creator_email' => ['@@', '=@', '=='],
                    'owner' => ['@@', '=@', '=='],
                    'owner_email' => ['@@', '=@', '=='],
                    'speaker' => ['@@', '=@', '=='],
                    'speaker_email' => ['@@', '=@', '=='],
                    'class_name' => ['=='],
                    'type' => ['=='],
                    'tag' => ['@@','=@', '=='],
                    'tag_id' => ['=='],
                    'sponsor_company_name' => ['@@', '=@', '=='],
                    'sponsor_id' => ['=='],
                    'contact_email' =>  ['@@', '=@', '=='],
                    'tier_name' =>  ['@@', '=@', '=='],
                    'email_sent' => ['=='],
                    'allows_to_delegate' => ['=='],
                ];
            },
            function () {
                return [
                    'class_name' => sprintf('sometimes|required|in:%s', implode(',', PromoCodesConstants::$valid_class_names)),
                    'code' => 'sometimes|string',
                    'description' => 'sometimes|string',
                    'notes' => 'sometimes|string',
                    'creator' => 'sometimes|string',
                    'creator_email' => 'sometimes|string',
                    'owner' => 'sometimes|string',
                    'owner_email' => 'sometimes|string',
                    'speaker' => 'sometimes|string',
                    'speaker_email' => 'sometimes|string',
                    'type' => sprintf('sometimes|in:%s', implode(',', PromoCodesConstants::getValidTypes())),
                    'tag' => 'sometimes|required|string',
                    'tag_id' => 'sometimes|integer',
                    'sponsor_company_name' => 'sometimes|string',
                    'contact_email' => 'sometimes|string',
                    'sponsor_id' => 'sometimes|integer',
                    'tier_name' =>  'sometimes|string',
                    'email_sent' => ['sometimes', new Boolean()],
                    'allows_to_delegate' => ['sometimes', new Boolean()],
                ];
            },
            function () {
                return [
                    'id',
                    'code',
                    'redeemed',
                    'tier_name',
                    'email_sent',
                    'quantity_available',
                    'quantity_used',
                    'sponsor_company_name',
                ];
            },
            function ($filter) {
                return $filter;
            },
            function () {
                return SerializerRegistry::SerializerType_Private;
            },
            function () {
                return new Order([
                    OrderElement::buildAscFor("id"),
                ]);
            },
            null,
            function ($page, $per_page, $filter, $order, $applyExtraFilters) use ($summit) {
                return $this->repository->getBySummit
                (
                    $summit,
                    new PagingInfo($page, $per_page),
                    call_user_func($applyExtraFilters, $filter),
                    $order
                );
            },
            ['serializer_type' => SerializerRegistry::SerializerType_Private]
        );
    }

    #[OA\Get(
        path: "/api/v1/summits/{id}/sponsor-promo-codes",
        summary: "Get all sponsor promo codes for a summit",
        description: "Get all sponsor promo codes for a summit with filtering and pagination",
        operationId: "getAllSponsorPromoCodesBySummit",
        tags: ["Promo Codes"],
        security: [['summit_promo_codes_oauth2' => [SummitScopes::ReadAllSummitData]]],
        x: ['required-groups' => [IGroup::SuperAdmins, IGroup::Administrators, IGroup::SummitAdministrators, IGroup::SummitRegistrationAdmins]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "page", in: "query", required: false, schema: new OA\Schema(type: "integer", default: 1)),
            new OA\Parameter(name: "per_page", in: "query", required: false, schema: new OA\Schema(type: "integer", default: 10)),
            new OA\Parameter(name: "filter", in: "query", required: false, schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "order", in: "query", required: false, schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "expand", in: "query", required: false, schema: new OA\Schema(type: "string")),
        ],
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: "OK"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Summit not found"),
        ]
    )]
    /**
     * @param $summit_id
     * @return mixed
     */
    public function getAllSponsorPromoCodesBySummit($summit_id)
    {

        $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
        if (is_null($summit)) return $this->error404();

        // authz check
        $current_member = $this->resource_server_context->getCurrentUser();
        if(!$current_member->isAuthzFor($summit))
            return $this->error403();

        return $this->_getAll(
            function () {
                return [
                    'code' => ['@@', '=@', '=='],
                    'notes' => ['@@', '=@', '=='],
                    'description' => ['@@', '=@'],
                    'tag' => ['@@','=@', '=='],
                    'tag_id' => ['=='],
                    'sponsor_company_name' => ['@@', '=@', '=='],
                    'sponsor_id' => ['=='],
                    'contact_email' =>  ['@@', '=@', '=='],
                    'tier_name' =>  ['@@', '=@', '=='],
                    'email_sent' => ['=='],
                    'allows_to_delegate' => ['=='],
                ];
            },
            function () {
                return [
                    'code' => 'sometimes|string',
                    'notes' => 'sometimes|string',
                    'description' => 'sometimes|string',
                    'tag' => 'sometimes|required|string',
                    'tag_id' => 'sometimes|integer',
                    'sponsor_company_name' => 'sometimes|string',
                    'contact_email' => 'sometimes|string',
                    'sponsor_id' => 'sometimes|integer',
                    'tier_name' =>  'sometimes|string',
                    'email_sent' => ['sometimes', new Boolean()],
                    'allows_to_delegate' => ['sometimes', new Boolean()],
                ];
            },
            function () {
                return [
                    'id',
                    'code',
                    'redeemed',
                    'tier_name',
                    'email_sent',
                    'quantity_available',
                    'quantity_used',
                    'sponsor_company_name',
                ];
            },
            function ($filter) {
                $filter = $filter->addFilterCondition(FilterElement::makeEqual('class_name', [
                    SponsorSummitRegistrationDiscountCode::ClassName,
                    SponsorSummitRegistrationPromoCode::ClassName
                ], 'OR'));
                return $filter;
            },
            function () {
                return SerializerRegistry::SerializerType_Private;
            },
            function () {
                return new Order([
                    OrderElement::buildAscFor("id"),
                ]);
            },
            null,
            function ($page, $per_page, $filter, $order, $applyExtraFilters) use ($summit) {
                return $this->repository->getBySummit
                (
                    $summit,
                    new PagingInfo($page, $per_page),
                    call_user_func($applyExtraFilters, $filter),
                    $order
                );
            },
            [ 'serializer_type' => SerializerRegistry::SerializerType_Private ],
        );
    }

    #[OA\Get(
        path: "/api/v1/summits/{id}/promo-codes/csv",
        summary: "Export promo codes to CSV",
        description: "Export all promo codes for a summit to CSV format",
        operationId: "getAllPromoCodesBySummitCSV",
        tags: ["Promo Codes"],
        security: [['summit_promo_codes_oauth2' => [SummitScopes::ReadAllSummitData]]],
        x: ['required-groups' => [IGroup::SuperAdmins, IGroup::Administrators, IGroup::SummitAdministrators, IGroup::SummitRegistrationAdmins]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "filter", in: "query", required: false, schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "order", in: "query", required: false, schema: new OA\Schema(type: "string")),
        ],
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: "CSV file"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Summit not found"),
        ]
    )]
    /**
     * @param $summit_id
     * @return \Illuminate\Http\Response|mixed
     */
    public function getAllBySummitCSV($summit_id)
    {

        $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->getResourceServerContext())->find($summit_id);
        if (is_null($summit)) return $this->error404();

        return $this->_getAllCSV(
            function () {
                return [
                    'code' => ['@@', '=@', '=='],
                    'notes' => ['@@', '=@', '=='],
                    'description' => ['@@', '=@'],
                    'creator' => ['@@', '=@', '=='],
                    'creator_email' => ['@@', '=@', '=='],
                    'owner' => ['@@', '=@', '=='],
                    'owner_email' => ['@@', '=@', '=='],
                    'speaker' => ['@@', '=@', '=='],
                    'speaker_email' => ['@@', '=@', '=='],
                    'class_name' => ['=='],
                    'type' => ['=='],
                    'tag' => ['@@','=@', '=='],
                    'tag_id' => ['=='],
                    'sponsor_company_name' => ['@@', '=@', '=='],
                    'sponsor_id' =>  ['=='],
                    'contact_email' =>  ['@@', '=@', '=='],
                    'tier_name' =>  ['@@', '=@', '=='],
                    'email_sent' => ['=='],
                    'allows_to_delegate' => ['=='],
                ];
            },
            function () {
                return [
                    'class_name' => sprintf('sometimes|in:%s', implode(',', PromoCodesConstants::$valid_class_names)),
                    'code' => 'sometimes|string',
                    'description' => 'sometimes|string',
                    'notes' => 'sometimes|string',
                    'creator' => 'sometimes|string',
                    'creator_email' => 'sometimes|string',
                    'owner' => 'sometimes|string',
                    'owner_email' => 'sometimes|string',
                    'speaker' => 'sometimes|string',
                    'speaker_email' => 'sometimes|string',
                    'type' => sprintf('sometimes|in:%s', implode(',', PromoCodesConstants::getValidTypes())),
                    'tag' => 'sometimes|required|string',
                    'tag_id' => 'sometimes|integer',
                    'sponsor_company_name' => 'sometimes|string',
                    'sponsor_id' => 'sometimes|integer',
                    'contact_email' => 'sometimes|string',
                    'tier_name' =>  'sometimes|string',
                    'email_sent' => ['sometimes', new Boolean()],
                    'allows_to_delegate' => ['sometimes', new Boolean()],
                ];
            },
            function () {
                return [
                    'id',
                    'code',
                    'redeemed',
                    'tier_name',
                    'email_sent',
                    'quantity_available',
                    'quantity_used',
                    'sponsor_company_name',
                ];
            },
            function ($filter) use ($summit) {
                return $filter;
            },
            function () {
                return SerializerRegistry::SerializerType_CSV;
            },
            function () use ($summit) {
                return [
                    'created' => new EpochCellFormatter(EpochCellFormatter::DefaultFormat, $summit->getTimeZone(), true),
                    'last_edited' => new EpochCellFormatter(EpochCellFormatter::DefaultFormat, $summit->getTimeZone(), true),
                    'redeemed' => new BooleanCellFormatter,
                    'email_sent' => new BooleanCellFormatter,
                ];
            },
            function () {
                return [
                    "id",
                    "created",
                    "last_edited",
                    "code",
                    "redeemed",
                    "email_sent",
                    "source",
                    "summit_id",
                    "creator_id",
                    "class_name",
                    "type",
                    "speaker_id",
                    "owner_name",
                    "owner_email",
                    "sponsor_name"
                ];
            },
            'promocodes-',
            [ 'serializer_type' => SerializerRegistry::SerializerType_Private ],
            function ($page, $per_page, $filter, $order, $applyExtraFilters) use ($summit) {
                return $this->repository->getBySummit
                (
                    $summit,
                    new PagingInfo($page, $per_page),
                    call_user_func($applyExtraFilters, $filter),
                    $order
                );
            },
        );
    }

    #[OA\Get(
        path: "/api/v1/summits/{id}/sponsor-promo-codes/csv",
        summary: "Export sponsor promo codes to CSV",
        description: "Export all sponsor promo codes for a summit to CSV format",
        operationId: "getSponsorPromoCodesAllBySummitCSV",
        tags: ["Promo Codes"],
        security: [['summit_promo_codes_oauth2' => [SummitScopes::ReadAllSummitData]]],
        x: ['required-groups' => [IGroup::SuperAdmins, IGroup::Administrators, IGroup::SummitAdministrators, IGroup::SummitRegistrationAdmins]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "filter", in: "query", required: false, schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "order", in: "query", required: false, schema: new OA\Schema(type: "string")),
        ],
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: "CSV file"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Summit not found"),
        ]
    )]
    public function getSponsorPromoCodesAllBySummitCSV($summit_id)
    {

        $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->getResourceServerContext())->find($summit_id);
        if (is_null($summit)) return $this->error404();

        // authz check
        $current_member = $this->resource_server_context->getCurrentUser();
        if(!$current_member->isAuthzFor($summit))
            return $this->error403();

        return $this->_getAllCSV(
            function () {
                return [
                    'code' => ['@@', '=@', '=='],
                    'description' => ['@@', '=@'],
                    'tag' => ['@@','=@', '=='],
                    'tag_id' => ['=='],
                    'sponsor_company_name' => ['@@', '=@', '=='],
                    'sponsor_id' =>  ['=='],
                    'contact_email' =>  ['@@', '=@', '=='],
                    'tier_name' =>  ['@@', '=@', '=='],
                    'email_sent' => ['=='],
                    'allows_to_delegate' => ['=='],
                ];
            },
            function () {
                return [
                    'code' => 'sometimes|string',
                    'description' => 'sometimes|string',
                    'tag' => 'sometimes|required|string',
                    'tag_id' => 'sometimes|integer',
                    'sponsor_company_name' => 'sometimes|string',
                    'sponsor_id' => 'sometimes|integer',
                    'contact_email' => 'sometimes|string',
                    'tier_name' =>  'sometimes|string',
                    'email_sent' => ['sometimes', new Boolean()],
                    'allows_to_delegate' => ['sometimes', new Boolean()],
                ];
            },
            function () {
                return [
                    'id',
                    'code',
                    'redeemed',
                    'tier_name',
                    'email_sent',
                    'quantity_available',
                    'quantity_used',
                    'sponsor_company_name',
                ];
            },
            function ($filter) use ($summit) {
                $filter = $filter->addFilterCondition(FilterElement::makeEqual('class_name', [
                    SponsorSummitRegistrationDiscountCode::ClassName,
                    SponsorSummitRegistrationPromoCode::ClassName
                ], 'OR'));
                return $filter;
            },
            function () {
                return SerializerRegistry::SerializerType_CSV;
            },
            function () {
                return [
                    'created' => new EpochCellFormatter,
                    'last_edited' => new EpochCellFormatter,
                    'redeemed' => new BooleanCellFormatter,
                    'email_sent' => new BooleanCellFormatter,
                ];
            },
            function () {
                return [
                    "id",
                    "created",
                    "last_edited",
                    "code",
                    "redeemed",
                    "email_sent",
                    "source",
                    "summit_id",
                    "creator_id",
                    "class_name",
                    "type",
                    "speaker_id",
                    "owner_name",
                    "owner_email",
                    "sponsor_name"
                ];
            },
            'sponsor-promocodes-',
            [ 'serializer_type' => SerializerRegistry::SerializerType_Private ],
            function ($page, $per_page, $filter, $order, $applyExtraFilters) use ($summit) {
                return $this->repository->getBySummit
                (
                    $summit,
                    new PagingInfo($page, $per_page),
                    call_user_func($applyExtraFilters, $filter),
                    $order
                );
            }
        );
    }

    #[OA\Get(
        path: "/api/v1/summits/{id}/promo-codes/metadata",
        summary: "Get promo codes metadata",
        description: "Get metadata about promo codes for a summit",
        operationId: "getPromoCodesMetadata",
        tags: ["Promo Codes"],
        security: [['summit_promo_codes_oauth2' => [SummitScopes::ReadAllSummitData]]],
        x: ['required-groups' => [IGroup::SuperAdmins, IGroup::Administrators, IGroup::SummitAdministrators, IGroup::SummitRegistrationAdmins]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer")),
        ],
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: "OK"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Summit not found"),
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
            $this->repository->getMetadata($summit)
        );
    }

    #[OA\Post(
        path: "/api/v1/summits/{id}/promo-codes",
        summary: "Create a promo code",
        description: "Create a new promo code for a summit",
        operationId: "addPromoCodeBySummit",
        tags: ["Promo Codes"],
        security: [['summit_promo_codes_oauth2' => [SummitScopes::WritePromoCodeData, SummitScopes::WriteSummitData]]],
        x: ['required-groups' => [IGroup::SuperAdmins, IGroup::Administrators, IGroup::SummitAdministrators, IGroup::SummitRegistrationAdmins]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer")),
        ],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/PromoCodeAddRequest')),
        responses: [
            new OA\Response(response: Response::HTTP_CREATED, description: "Created"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Summit not found"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation error"),
        ]
    )]
    /**
     * @param $summit_id
     * @return mixed
     */
    public function addPromoCodeBySummit($summit_id)
    {
        return $this->processRequest(function () use ($summit_id) {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $payload = $this->getJsonPayload(PromoCodesValidationRulesFactory::buildForAdd($this->getJsonData()), true);

            $promo_code = $this->promo_code_service->addPromoCode($summit, $payload, $this->resource_server_context->getCurrentUser());

            return $this->created(SerializerRegistry::getInstance()->getSerializer($promo_code)->serialize(
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations(),
            ));
        });
    }

    #[OA\Put(
        path: "/api/v1/summits/{id}/promo-codes/{promo_code_id}",
        summary: "Update a promo code",
        description: "Update an existing promo code",
        operationId: "updatePromoCodeBySummit",
        tags: ["Promo Codes"],
        security: [['summit_promo_codes_oauth2' => [SummitScopes::WritePromoCodeData, SummitScopes::WriteSummitData]]],
        x: ['required-groups' => [IGroup::SuperAdmins, IGroup::Administrators, IGroup::SummitAdministrators, IGroup::SummitRegistrationAdmins]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "promo_code_id", in: "path", required: true, schema: new OA\Schema(type: "integer")),
        ],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/PromoCodeUpdateRequest')),
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: "OK"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not found"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation error"),
        ]
    )]
    /**
     * @param $summit_id
     * @param $promo_code_id
     * @return mixed
     */
    public function updatePromoCodeBySummit($summit_id, $promo_code_id)
    {
        return $this->processRequest(function () use ($summit_id, $promo_code_id) {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $payload = $this->getJsonPayload(PromoCodesValidationRulesFactory::buildForUpdate($this->getJsonData()), true);

            $promo_code = $this->promo_code_service->updatePromoCode($summit, intval($promo_code_id), $payload, $this->resource_server_context->getCurrentUser());

            return $this->updated(SerializerRegistry::getInstance()->getSerializer($promo_code)->serialize(
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations(),
            ));

        });
    }

    #[OA\Delete(
        path: "/api/v1/summits/{id}/promo-codes/{promo_code_id}",
        summary: "Delete a promo code",
        description: "Delete a promo code from a summit",
        operationId: "deletePromoCodeBySummit",
        tags: ["Promo Codes"],
        security: [['summit_promo_codes_oauth2' => [SummitScopes::WritePromoCodeData, SummitScopes::WriteSummitData]]],
        x: ['required-groups' => [IGroup::SuperAdmins, IGroup::Administrators, IGroup::SummitAdministrators, IGroup::SummitRegistrationAdmins]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "promo_code_id", in: "path", required: true, schema: new OA\Schema(type: "integer")),
        ],
        responses: [
            new OA\Response(response: Response::HTTP_NO_CONTENT, description: "Deleted"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not found"),
        ]
    )]
    /**
     * @param $summit_id
     * @param $promo_code_id
     * @return mixed
     */
    public function deletePromoCodeBySummit($summit_id, $promo_code_id)
    {
        return $this->processRequest(function () use ($summit_id, $promo_code_id) {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $this->promo_code_service->deletePromoCode($summit, intval($promo_code_id));

            return $this->deleted();
        });
    }

    #[OA\Post(
        path: "/api/v1/summits/{id}/promo-codes/{promo_code_id}/mail",
        summary: "Send promo code email",
        description: "Send an email with the promo code",
        operationId: "sendPromoCodeMail",
        tags: ["Promo Codes"],
        security: [['summit_promo_codes_oauth2' => [SummitScopes::WritePromoCodeData, SummitScopes::WriteSummitData]]],
        x: ['required-groups' => [IGroup::SuperAdmins, IGroup::Administrators, IGroup::SummitAdministrators, IGroup::SummitRegistrationAdmins]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "promo_code_id", in: "path", required: true, schema: new OA\Schema(type: "integer")),
        ],
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: "OK"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not found"),
        ]
    )]
    /**
     * @param $summit_id
     * @param $promo_code_id
     * @return mixed
     */
    public function sendPromoCodeMail($summit_id, $promo_code_id)
    {
        return $this->processRequest(function () use ($summit_id, $promo_code_id) {
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();
            $this->promo_code_service->sendPromoCodeMail($summit, intval($promo_code_id));
            return $this->ok();
        });
    }

    #[OA\Get(
        path: "/api/v1/summits/{id}/promo-codes/{promo_code_id}",
        summary: "Get a promo code",
        description: "Get a specific promo code by ID",
        operationId: "getPromoCodeBySummit",
        tags: ["Promo Codes"],
        security: [['summit_promo_codes_oauth2' => [SummitScopes::ReadAllSummitData]]],
        x: ['required-groups' => [IGroup::SuperAdmins, IGroup::Administrators, IGroup::SummitAdministrators, IGroup::SummitRegistrationAdmins]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "promo_code_id", in: "path", required: true, schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "expand", in: "query", required: false, schema: new OA\Schema(type: "string")),
        ],
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: "OK"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not found"),
        ]
    )]
    /**
     * @param $summit_id
     * @param $promo_code_id
     * @return mixed
     */
    public function getPromoCodeBySummit($summit_id, $promo_code_id)
    {
        return $this->processRequest(function () use ($summit_id, $promo_code_id) {
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();
            $promo_code = $summit->getPromoCodeById(intval($promo_code_id));
            if (is_null($promo_code))
                return $this->error404();
            return $this->ok(SerializerRegistry::getInstance()->getSerializer($promo_code)->serialize(
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations(),
            ));
        });
    }

    #[OA\Put(
        path: "/api/v1/summits/{id}/promo-codes/{promo_code_id}/badge-features/{badge_feature_id}",
        summary: "Add badge feature to promo code",
        description: "Add a badge feature to a promo code",
        operationId: "addBadgeFeatureToPromoCode",
        tags: ["Promo Codes"],
        security: [['summit_promo_codes_oauth2' => [SummitScopes::WritePromoCodeData, SummitScopes::WriteSummitData]]],
        x: ['required-groups' => [IGroup::SuperAdmins, IGroup::Administrators, IGroup::SummitAdministrators, IGroup::SummitRegistrationAdmins]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "promo_code_id", in: "path", required: true, schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "badge_feature_id", in: "path", required: true, schema: new OA\Schema(type: "integer")),
        ],
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: "OK"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not found"),
        ]
    )]
    /**
     * @param $summit_id
     * @param $promo_code_id
     * @param $badge_feature_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function addBadgeFeatureToPromoCode($summit_id, $promo_code_id, $badge_feature_id)
    {

        return $this->processRequest(function () use ($summit_id, $promo_code_id, $badge_feature_id) {
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();
            $promo_code = $this->promo_code_service->addPromoCodeBadgeFeature($summit, intval($promo_code_id), intval($badge_feature_id));
            return $this->updated(SerializerRegistry::getInstance()->getSerializer($promo_code)->serialize(
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations(),
            ));
        });
    }


    #[OA\Delete(
        path: "/api/v1/summits/{id}/promo-codes/{promo_code_id}/badge-features/{badge_feature_id}",
        summary: "Remove badge feature from promo code",
        description: "Remove a badge feature from a promo code",
        operationId: "removeBadgeFeatureFromPromoCode",
        tags: ["Promo Codes"],
        security: [['summit_promo_codes_oauth2' => [SummitScopes::WritePromoCodeData, SummitScopes::WriteSummitData]]],
        x: ['required-groups' => [IGroup::SuperAdmins, IGroup::Administrators, IGroup::SummitAdministrators, IGroup::SummitRegistrationAdmins]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "promo_code_id", in: "path", required: true, schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "badge_feature_id", in: "path", required: true, schema: new OA\Schema(type: "integer")),
        ],
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: "OK"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not found"),
        ]
    )]
    /**
     * @param $summit_id
     * @param $promo_code_id
     * @param $badge_feature_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function removeBadgeFeatureFromPromoCode($summit_id, $promo_code_id, $badge_feature_id)
    {

        return $this->processRequest(function () use ($summit_id, $promo_code_id, $badge_feature_id) {
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();
            $promo_code = $this->promo_code_service->removePromoCodeBadgeFeature($summit, intval($promo_code_id), intval($badge_feature_id));
            return $this->updated(SerializerRegistry::getInstance()->getSerializer($promo_code)->serialize(
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations(),
            ));
        });
    }

    #[OA\Put(
        path: "/api/v1/summits/{id}/promo-codes/{promo_code_id}/ticket-types/{ticket_type_id}",
        summary: "Add ticket type to promo code",
        description: "Add a ticket type rule to a promo code",
        operationId: "addTicketTypeToPromoCode",
        tags: ["Promo Codes"],
        security: [['summit_promo_codes_oauth2' => [SummitScopes::WritePromoCodeData, SummitScopes::WriteSummitData]]],
        x: ['required-groups' => [IGroup::SuperAdmins, IGroup::Administrators, IGroup::SummitAdministrators, IGroup::SummitRegistrationAdmins]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "promo_code_id", in: "path", required: true, schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "ticket_type_id", in: "path", required: true, schema: new OA\Schema(type: "integer")),
        ],
        requestBody: new OA\RequestBody(required: false, content: new OA\JsonContent(ref: '#/components/schemas/PromoCodeTicketTypeRuleRequest')),
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: "OK"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not found"),
        ]
    )]
    /**
     * @param $summit_id
     * @param $promo_code_id
     * @param $ticket_type_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function addTicketTypeToPromoCode($summit_id, $promo_code_id, $ticket_type_id)
    {

        return $this->processRequest(function () use ($summit_id, $promo_code_id, $ticket_type_id) {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $payload = $this->getJsonPayload(
                [
                    'amount' => 'sometimes|required_without:rate|numeric|min:0',
                    'rate' => 'sometimes|required_without:amount|numeric|min:0',
                ]
            );

            $promo_code = $this->promo_code_service->addPromoCodeTicketTypeRule($summit, intval($promo_code_id), intval($ticket_type_id), $payload);
            return $this->updated(SerializerRegistry::getInstance()->getSerializer($promo_code)->serialize(
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations(),
            ));
        });
    }


    #[OA\Delete(
        path: "/api/v1/summits/{id}/promo-codes/{promo_code_id}/ticket-types/{ticket_type_id}",
        summary: "Remove ticket type from promo code",
        description: "Remove a ticket type rule from a promo code",
        operationId: "removeTicketTypeFromPromoCode",
        tags: ["Promo Codes"],
        security: [['summit_promo_codes_oauth2' => [SummitScopes::WritePromoCodeData, SummitScopes::WriteSummitData]]],
        x: ['required-groups' => [IGroup::SuperAdmins, IGroup::Administrators, IGroup::SummitAdministrators, IGroup::SummitRegistrationAdmins]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "promo_code_id", in: "path", required: true, schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "ticket_type_id", in: "path", required: true, schema: new OA\Schema(type: "integer")),
        ],
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: "OK"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not found"),
        ]
    )]
    /**
     * @param $summit_id
     * @param $promo_code_id
     * @param $ticket_type_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function removeTicketTypeFromPromoCode($summit_id, $promo_code_id, $ticket_type_id)
    {
        return $this->processRequest(function () use ($summit_id, $promo_code_id, $ticket_type_id) {
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();
            $promo_code = $this->promo_code_service->removePromoCodeTicketTypeRule($summit, intval($promo_code_id), intval($ticket_type_id));
            return $this->updated(SerializerRegistry::getInstance()->getSerializer($promo_code)->serialize(
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations(),
            ));
        });
    }

    #[OA\Post(
        path: "/api/v1/summits/{id}/promo-codes/csv",
        summary: "Import promo codes from CSV",
        description: "Import promo codes from a CSV file",
        operationId: "ingestPromoCodes",
        tags: ["Promo Codes"],
        security: [['summit_promo_codes_oauth2' => [SummitScopes::WritePromoCodeData, SummitScopes::WriteSummitData]]],
        x: ['required-groups' => [IGroup::SuperAdmins, IGroup::Administrators, IGroup::SummitAdministrators, IGroup::SummitRegistrationAdmins]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer")),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: "multipart/form-data",
                schema: new OA\Schema(properties: [new OA\Property(property: "file", type: "string", format: "binary")])
            )
        ),
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: "OK"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Summit not found"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation error"),
        ]
    )]
    /**
     * @param LaravelRequest $request
     * @param $summit_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function ingestPromoCodes(LaravelRequest $request, $summit_id)
    {
        return $this->processRequest(function () use ($summit_id, $request) {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->getResourceServerContext())->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $current_member = $this->resource_server_context->getCurrentUser();
            if (is_null($current_member)) return $this->error403();

            $file = $request->file('file');
            if (is_null($file)) {
                return $this->error412(array('file param not set!'));
            }

            $this->promo_code_service->importPromoCodes($summit, $file, $this->resource_server_context->getCurrentUser());
            return $this->ok();

        });
    }

    #[OA\Post(
        path: "/api/v1/summits/{id}/sponsor-promo-codes/csv",
        summary: "Import sponsor promo codes from CSV",
        description: "Import sponsor promo codes from a CSV file",
        operationId: "ingestSponsorPromoCodes",
        tags: ["Promo Codes"],
        security: [['summit_promo_codes_oauth2' => [SummitScopes::WritePromoCodeData, SummitScopes::WriteSummitData]]],
        x: ['required-groups' => [IGroup::SuperAdmins, IGroup::Administrators, IGroup::SummitAdministrators, IGroup::SummitRegistrationAdmins]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer")),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: "multipart/form-data",
                schema: new OA\Schema(properties: [new OA\Property(property: "file", type: "string", format: "binary")])
            )
        ),
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: "OK"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Summit not found"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation error"),
        ]
    )]
    /**
     * @param LaravelRequest $request
     * @param $summit_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function ingestSponsorPromoCodes(LaravelRequest $request, $summit_id)
    {
        return $this->processRequest(function () use ($summit_id, $request) {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->getResourceServerContext())->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $current_member = $this->resource_server_context->getCurrentUser();
            if (is_null($current_member)) return $this->error403();

            $file = $request->file('file');
            if (is_null($file)) {
                return $this->error412(array('file param not set!'));
            }

            $this->promo_code_service->importSponsorPromoCodes($summit, $file, $this->resource_server_context->getCurrentUser());
            return $this->ok();

        });
    }

    #[OA\Get(
        path: "/api/v1/summits/{id}/speakers-promo-codes/{promo_code_id}/speakers",
        summary: "Get speakers for a promo code",
        description: "Get all speakers associated with a promo code",
        operationId: "getPromoCodeSpeakers",
        tags: ["Promo Codes"],
        security: [['summit_promo_codes_oauth2' => [SummitScopes::ReadAllSummitData]]],
        x: ['required-groups' => [IGroup::SuperAdmins, IGroup::Administrators, IGroup::SummitAdministrators]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "promo_code_id", in: "path", required: true, schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "page", in: "query", required: false, schema: new OA\Schema(type: "integer", default: 1)),
            new OA\Parameter(name: "per_page", in: "query", required: false, schema: new OA\Schema(type: "integer", default: 10)),
            new OA\Parameter(name: "filter", in: "query", required: false, schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "order", in: "query", required: false, schema: new OA\Schema(type: "string")),
        ],
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: "OK"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not found"),
        ]
    )]
    /**
     * @param $summit_id
     * @param $promo_code_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function getPromoCodeSpeakers($summit_id, $promo_code_id)
    {
        $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
        if (is_null($summit)) return $this->error404();

        $promo_code = $this->repository->getById($promo_code_id);

        if (!$promo_code instanceof SpeakersSummitRegistrationPromoCode ) {
            return $this->error404();
        }

        return $this->_getAll(
            function () {
                return [
                    'email' => ['@@', '=@', '=='],
                    'full_name' => ['@@', '=@', '=='],
                    'first_name' => ['@@', '=@', '=='],
                    'last_name' => ['@@', '=@', '=='],
                ];
            },
            function () {
                return [
                    'email' => 'sometimes|string',
                    'full_name' => 'sometimes|string',
                    'first_name' => 'sometimes|string',
                    'last_name' => 'sometimes|string',
                ];
            },
            function () {
                return [
                    'id',
                    'email',
                    'full_name',
                    'email_sent',
                    'redeemed'
                ];
            },
            function ($filter) {
                return $filter;
            },
            function () {
                return SerializerRegistry::SerializerType_Private;
            },
            function () {
                return new Order([
                    OrderElement::buildAscFor("id"),
                ]);
            },
            null,
            function ($page, $per_page, $filter, $order, $applyExtraFilters) use ($promo_code) {
                return $this->speakers_promo_code_repository->getPromoCodeSpeakers
                (
                    $promo_code,
                    new PagingInfo($page, $per_page),
                    call_user_func($applyExtraFilters, $filter),
                    $order
                );
            }
        );
    }

    #[OA\Get(
        path: "/api/v1/summits/{id}/speakers-discount-codes/{discount_code_id}/speakers",
        summary: "Get speakers for a discount code",
        description: "Get all speakers associated with a discount code",
        operationId: "getDiscountCodeSpeakers",
        tags: ["Promo Codes"],
        security: [['summit_promo_codes_oauth2' => [SummitScopes::ReadAllSummitData]]],
        x: ['required-groups' => [IGroup::SuperAdmins, IGroup::Administrators, IGroup::SummitAdministrators]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "discount_code_id", in: "path", required: true, schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "page", in: "query", required: false, schema: new OA\Schema(type: "integer", default: 1)),
            new OA\Parameter(name: "per_page", in: "query", required: false, schema: new OA\Schema(type: "integer", default: 10)),
            new OA\Parameter(name: "filter", in: "query", required: false, schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "order", in: "query", required: false, schema: new OA\Schema(type: "string")),
        ],
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: "OK"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not found"),
        ]
    )]
    /**
     * @param $summit_id
     * @param $discount_code_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function getDiscountCodeSpeakers($summit_id, $discount_code_id)
    {
        $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
        if (is_null($summit)) return $this->error404();

        $discount_code = $this->repository->getById(intval($discount_code_id));

        if (!$discount_code instanceof SpeakersRegistrationDiscountCode) {
            return $this->error404();
        }

        return $this->_getAll(
            function () {
                return [
                    'email' => ['@@', '=@', '=='],
                    'full_name' => ['@@', '=@', '=='],
                    'first_name' => ['@@', '=@', '=='],
                    'last_name' => ['@@', '=@', '=='],
                ];
            },
            function () {
                return [
                    'email' => 'sometimes|string',
                    'full_name' => 'sometimes|string',
                    'first_name' => 'sometimes|string',
                    'last_name' => 'sometimes|string',
                ];
            },
            function () {
                return [
                    'id',
                    'email',
                    'full_name',
                    'email_sent',
                    'redeemed'
                ];
            },
            function ($filter) {
                return $filter;
            },
            function () {
                return SerializerRegistry::SerializerType_Private;
            },
            function () {
                return new Order([
                    OrderElement::buildAscFor("id"),
                ]);
            },
            null,
            function ($page, $per_page, $filter, $order, $applyExtraFilters) use ($discount_code) {
                return $this->speakers_discount_code_repository->getDiscountCodeSpeakers
                (
                    $discount_code,
                    new PagingInfo($page, $per_page),
                    call_user_func($applyExtraFilters, $filter),
                    $order
                );
            }
        );
    }

    #[OA\Post(
        path: "/api/v1/summits/{id}/speakers-promo-codes/{promo_code_id}/speakers/{speaker_id}",
        summary: "Add speaker to promo code",
        description: "Associate a speaker with a promo code",
        operationId: "addPromoCodeSpeaker",
        tags: ["Promo Codes"],
        security: [['summit_promo_codes_oauth2' => [SummitScopes::WritePromoCodeData, SummitScopes::WriteSummitData]]],
        x: ['required-groups' => [IGroup::SuperAdmins, IGroup::Administrators, IGroup::SummitAdministrators]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "promo_code_id", in: "path", required: true, schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "speaker_id", in: "path", required: true, schema: new OA\Schema(type: "integer")),
        ],
        responses: [
            new OA\Response(response: Response::HTTP_CREATED, description: "Created"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not found"),
        ]
    )]
    #[OA\Post(
        path: "/api/v1/summits/{id}/speakers-discount-codes/{discount_code_id}/speakers/{speaker_id}",
        summary: "Add speaker to promo code",
        description: "Associate a speaker with a promo code",
        operationId: "addDiscountCodeSpeaker",
        tags: ["Promo Codes"],
        security: [['summit_promo_codes_oauth2' => [SummitScopes::WritePromoCodeData, SummitScopes::WriteSummitData]]],
        x: ['required-groups' => [IGroup::SuperAdmins, IGroup::Administrators, IGroup::SummitAdministrators]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "discount_code_id", in: "path", required: true, schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "speaker_id", in: "path", required: true, schema: new OA\Schema(type: "integer")),
        ],
        responses: [
            new OA\Response(response: Response::HTTP_CREATED, description: "Created"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not found"),
        ]
    )]
    /**
     * @param $summit_id
     * @param $promo_code_id
     * @param $speaker_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function addSpeaker($summit_id, $promo_code_id, $speaker_id)
    {
        return $this->processRequest(function () use ($summit_id, $promo_code_id, $speaker_id) {
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit))
                return $this->error404("Summit not found.");

            $promo_code = $this->repository->getById($promo_code_id);
            if (!$promo_code instanceof SummitRegistrationPromoCode)
                return $this->error404("Promo Code not found.");

            $this->promo_code_service->addPromoCodeSpeaker($promo_code, intval($speaker_id));

            return $this->created();
        });
    }

    #[OA\Delete(
        path: "/api/v1/summits/{id}/speakers-promo-codes/{promo_code_id}/speakers/{speaker_id}",
        summary: "Remove speaker from promo code",
        description: "Remove a speaker from a promo code",
        operationId: "removePromoCodeSpeaker",
        tags: ["Promo Codes"],
        security: [['summit_promo_codes_oauth2' => [SummitScopes::WritePromoCodeData, SummitScopes::WriteSummitData]]],
        x: ['required-groups' => [IGroup::SuperAdmins, IGroup::Administrators, IGroup::SummitAdministrators]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "promo_code_id", in: "path", required: true, schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "speaker_id", in: "path", required: true, schema: new OA\Schema(type: "integer")),
        ],
        responses: [
            new OA\Response(response: Response::HTTP_NO_CONTENT, description: "Deleted"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not found"),
        ]
    )]
    #[OA\Delete(
        path: "/api/v1/summits/{id}/speakers-discount-codes/{discount_code_id}/speakers/{speaker_id}",
        summary: "Remove speaker from promo code",
        description: "Remove a speaker from a promo code",
        operationId: "removeDiscountCodeSpeaker",
        tags: ["Promo Codes"],
        security: [['summit_promo_codes_oauth2' => [SummitScopes::WritePromoCodeData, SummitScopes::WriteSummitData]]],
        x: ['required-groups' => [IGroup::SuperAdmins, IGroup::Administrators, IGroup::SummitAdministrators]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "discount_code_id", in: "path", required: true, schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "speaker_id", in: "path", required: true, schema: new OA\Schema(type: "integer")),
        ],
        responses: [
            new OA\Response(response: Response::HTTP_NO_CONTENT, description: "Deleted"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not found"),
        ]
    )]
    /**
     * @param $summit_id
     * @param $promo_code_id
     * @param $speaker_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function removeSpeaker($summit_id, $promo_code_id, $speaker_id)
    {
        return $this->processRequest(function () use ($summit_id, $promo_code_id, $speaker_id) {
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit))
                return $this->error404("Summit not found.");

            $promo_code = $this->repository->getById($promo_code_id);
            if (!$promo_code instanceof SummitRegistrationPromoCode)
                return $this->error404("Promo Code not found.");

            $this->promo_code_service->removePromoCodeSpeaker($promo_code, intval($speaker_id));

            return $this->deleted();
        });
    }

    #[OA\Get(
        path: "/api/v1/summits/{id}/promo-codes/{promo_code_val}/apply",
        summary: "Pre-validate promo code",
        description: "Pre-validate a promo code before applying it",
        operationId: "preValidatePromoCode",
        tags: ["Promo Codes"],
        security: [['summit_promo_codes_oauth2' => [SummitScopes::ReadSummitData, SummitScopes::ReadAllSummitData]]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "promo_code_val", in: "path", required: true, schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "filter", in: "query", required: false, schema: new OA\Schema(type: "string")),
        ],
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: "OK"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not found"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation error"),
        ]
    )]
    /**
     * @param $summit_id
     * @param $promo_code_val
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function preValidatePromoCode($summit_id, $promo_code_val)
    {
        return $this->processRequest(function () use ($summit_id, $promo_code_val) {
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit))
                return $this->error404("Summit not found.");

            $filter = null;

            if (Request::has('filter')) {
                $filter = FilterParser::parse(Request::input('filter'), [
                    'ticket_type_id'      => ['=='],
                    'ticket_type_qty'     => ['=='],
                    'ticket_type_subtype' => ['=='],
                ]);
            }

            if (is_null($filter)) $filter = new Filter();

            $filter->validate([
                'ticket_type_id'      => 'required|integer',
                'ticket_type_qty'     => [
                    'required',
                    'integer',
                    function ($attribute, $value, $fail) use ($filter) {
                        $ticket_type_subtype = $filter->getUniqueFilter('ticket_type_subtype')->getValue();
                        if ($ticket_type_subtype === SummitTicketType::Subtype_PrePaid && $value != 1) {
                            $fail('The ticket_type_qty must be 1 for prepaid promo codes.');
                        }
                    },
                ],
                'ticket_type_subtype' => 'required|string|in:'.join(",", SummitTicketType::SubTypes),
            ]);

            $promo_code = $this->promo_code_service
                ->preValidatePromoCode($summit, $this->resource_server_context->getCurrentUser(), $promo_code_val, $filter);

            return $this->ok(SerializerRegistry::getInstance()
                ->getSerializer($promo_code, SerializerRegistry::SerializerType_PreValidation)
                ->serialize());
        });
    }

    #[OA\Put(
        path: "/api/v1/summits/{id}/sponsors/all/promo-codes/all/send",
        summary: "Send sponsor promo codes emails",
        description: "Trigger sending sponsor promo codes via email",
        operationId: "sendSponsorPromoCodesMail",
        tags: ["Promo Codes"],
        security: [['summit_promo_codes_oauth2' => [SummitScopes::WritePromoCodeData, SummitScopes::WriteSummitData]]],
        x: ['required-groups' => [IGroup::SuperAdmins, IGroup::Administrators, IGroup::SummitAdministrators]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "filter", in: "query", required: false, schema: new OA\Schema(type: "string")),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/SendSponsorPromoCodesRequest')
        ),
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: "OK"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Summit not found"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation error"),
        ]
    )]
    /**
     * @param $summit_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function sendSponsorPromoCodes($summit_id)
    {
        return $this->processRequest(function () use ($summit_id) {

            if (!Request::isJson()) return $this->error400();

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            // authz check
            $current_member = $this->resource_server_context->getCurrentUser();
            if(!$current_member->isAuthzFor($summit))
                throw new HTTP403ForbiddenException("You are not allowed to perform this action.");

            $payload = $this->getJsonPayload([
                'email_flow_event' => 'required|string|in:' . join(',', [
                        SponsorPromoCodeEmail::EVENT_SLUG,
                    ]),
                'promo_code_ids'          => 'sometimes|int_array',
                'excluded_promo_code_ids' => 'sometimes|int_array',
                'test_email_recipient'    => 'sometimes|email',
                'outcome_email_recipient' => 'sometimes|email',
            ]);

            $filter = null;

            if (Request::has('filter')) {
                $filter = FilterParser::parse(Request::input('filter'), [
                    'id'            => ['=='],
                    'not_id'        => ['=='],
                    'code' => ['@@', '=@', '=='],
                    'notes' => ['@@', '=@', '=='],
                    'description' => ['@@', '=@'],
                    'tag' => ['@@','=@', '=='],
                    'tag_id' => ['=='],
                    'sponsor_company_name' => ['@@', '=@', '=='],
                    'sponsor_id' => ['=='],
                    'contact_email' =>  ['@@', '=@', '=='],
                    'tier_name' =>  ['@@', '=@', '=='],
                    'email_sent' => ['=='],
                    'allows_to_delegate' => ['=='],
                ]);
            }

            if (is_null($filter))
                $filter = new Filter();

            $filter->validate([
                'id'            => 'sometimes|integer',
                'not_id'        => 'sometimes|integer',
                'code' => 'sometimes|string',
                'notes' => 'sometimes|string',
                'description' => 'sometimes|string',
                'tag' => 'sometimes|required|string',
                'tag_id' => 'sometimes|integer',
                'sponsor_company_name' => 'sometimes|string',
                'contact_email' => 'sometimes|string',
                'sponsor_id' => 'sometimes|integer',
                'tier_name' =>  'sometimes|string',
                'email_sent' => ['sometimes', new Boolean()],
                'allows_to_delegate' => ['sometimes', new Boolean()],
            ]);

            $this->promo_code_service->triggerSendSponsorPromoCodes($summit, $payload, FiltersParams::getFilterParam());

            return $this->ok();
        });
    }
}
