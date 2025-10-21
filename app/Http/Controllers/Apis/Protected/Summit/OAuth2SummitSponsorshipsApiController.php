<?php namespace App\Http\Controllers;
/*
 * Copyright 2025 OpenStack Foundation
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

use App\Models\Foundation\Summit\Repositories\ISummitSponsorshipAddOnRepository;
use App\Models\Foundation\Summit\Repositories\ISummitSponsorshipRepository;
use App\ModelSerializers\SerializerUtils;
use App\Services\Model\ISummitSponsorshipService;
use App\Swagger\Summit\SummitSponsorshipAddOnSchema;
use App\Swagger\Summit\SummitSponsorshipSchema;
use Illuminate\Http\JsonResponse;
use models\oauth2\IResourceServerContext;
use models\summit\ISummitRepository;
use ModelSerializers\SerializerRegistry;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Response;
use utils\Filter;
use utils\FilterElement;
use utils\PagingInfo;

/**
 * Class OAuth2SummitSponsorshipsApiController
 * @package App\Http\Controllers
 */
#[OA\Tag(name: "Sponsorships", description: "Sponsorships endpoints")]
final class OAuth2SummitSponsorshipsApiController
    extends OAuth2ProtectedController
{
    use ParametrizedGetAll;

    use GetAndValidateJsonPayload;

    /**
     * @var ISummitRepository
     */
    private $summit_repository;

    /**
     * @var ISummitSponsorshipAddOnRepository
     */
    private $sponsorship_add_on_repository;

    /**
     * @var ISummitSponsorshipService
     */
    private $service;

    /**
     * @param ISummitRepository $summit_repository
     * @param ISummitSponsorshipRepository $repository
     * @param ISummitSponsorshipService $service
     * @param IResourceServerContext $resource_server_context
     */
    public function __construct
    (
        ISummitRepository                 $summit_repository,
        ISummitSponsorshipAddOnRepository $sponsorship_add_on_repository,
        ISummitSponsorshipRepository      $repository,
        ISummitSponsorshipService         $service,
        IResourceServerContext            $resource_server_context
    )
    {
        $this->service = $service;
        $this->repository = $repository;
        $this->sponsorship_add_on_repository = $sponsorship_add_on_repository;
        $this->summit_repository = $summit_repository;
        parent::__construct($resource_server_context);
    }

    /**
     * @param $summit_id
     * @param $sponsor_id
     * @return JsonResponse|mixed
     */
    #[OA\Get(
        path: "/summits/{id}/sponsors/{sponsor_id}/sponsorships",
        operationId: "getAllSponsorships",
        description: "Get all sponsorships for a sponsor",
        parameters: [
            new OA\Parameter(name: "id", description: "Summit ID", in: "path", required: true, schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "sponsor_id", description: "Sponsor ID", in: "path", required: true, schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "page", description: "Page number", in: "query", schema: new OA\Schema(type: "integer", default: 1)),
            new OA\Parameter(name: "per_page", description: "Items per page", in: "query", schema: new OA\Schema(type: "integer", default: 10)),
            new OA\Parameter(name: "expand", description: "Expand relations", in: "query", schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "fields", description: "Fields to return", in: "query", schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "filter", description: "Filter conditions", in: "query", schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "order", description: "Order by", in: "query", schema: new OA\Schema(type: "string")),
        ],
        tags: ["Sponsorships"],
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: "List of sponsorships", content: new OA\JsonContent(
                type: "object",
                properties: [
                    new OA\Property(property: "data", type: "array", items: new OA\Items(ref: "#/components/schemas/SummitSponsorship")),
                    new OA\Property(property: "page", type: "integer"),
                    new OA\Property(property: "per_page", type: "integer"),
                    new OA\Property(property: "total", type: "integer"),
                ]
            )),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not found"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    public function getAll($summit_id, $sponsor_id): mixed
    {
        $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
        if (is_null($summit)) return $this->error404();

        $current_member = $this->resource_server_context->getCurrentUser();
        if (!is_null($current_member) && !$current_member->isSummitAllowed($summit))
            return $this->error403(['message' => sprintf("Member %s has not permission for this Summit", $current_member->getId())]);

        $sponsor = $summit->getSummitSponsorById(intval($sponsor_id));
        if (is_null($sponsor)) return $this->error404();

        return $this->_getAll(
            function () {
                return [
                    'id' => ['=='],
                    'not_id' => ['=='],
                    'name' => ['==', '=@', '@@'],
                    'label' => ['==', '=@', '@@'],
                    'size' => ['==', '=@', '@@'],
                ];
            },
            function () {
                return [
                    'id' => 'sometimes|integer',
                    'not_id' => 'sometimes|integer',
                    'name' => 'sometimes|string',
                    'label' => 'sometimes|string',
                    'size' => 'sometimes|string',
                ];
            },
            function () {
                return [
                    'id',
                    'name',
                    'label',
                    'size',
                ];
            },
            function ($filter) use ($summit, $sponsor) {
                if ($filter instanceof Filter) {
                    $filter->addFilterCondition(FilterElement::makeEqual('summit_id', $summit->getId()));
                    $filter->addFilterCondition(FilterElement::makeEqual('sponsor_id', $sponsor->getId()));
                }
                return $filter;
            }
        );
    }

    /**
     * @param $summit_id
     * @param $sponsor_id
     * @param $sponsorship_id
     * @return mixed
     */
    #[OA\Get(
        path: "/summits/{id}/sponsors/{sponsor_id}/sponsorships/{sponsorship_id}",
        operationId: "getSponsorshipById",
        description: "Get sponsorship by ID",
        parameters: [
            new OA\Parameter(name: "id", description: "Summit ID", in: "path", required: true, schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "sponsor_id", description: "Sponsor ID", in: "path", required: true, schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "sponsorship_id", description: "Sponsorship ID", in: "path", required: true, schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "expand", description: "Expand relations", in: "query", schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "fields", description: "Fields to return", in: "query", schema: new OA\Schema(type: "string")),
        ],
        tags: ["Sponsorships"],
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: "Sponsorship data", content: new OA\JsonContent(ref: "#/components/schemas/SummitSponsorship")),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not found"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    public function getById($summit_id, $sponsor_id, $sponsorship_id): mixed
    {
        return $this->processRequest(function () use ($summit_id, $sponsor_id, $sponsorship_id) {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $current_member = $this->resource_server_context->getCurrentUser();
            if (!is_null($current_member) && !$current_member->isSummitAllowed($summit))
                return $this->error403(['message' => sprintf("Member %s has not permission for this Summit", $current_member->getId())]);

            $sponsor = $summit->getSummitSponsorById(intval($sponsor_id));
            if (is_null($sponsor)) return $this->error404();

            $sponsorship = $sponsor->getSponsorshipById($sponsorship_id);
            if (is_null($sponsorship)) return $this->error404();

            return $this->ok(SerializerRegistry::getInstance()
                ->getSerializer($sponsorship)
                ->serialize(
                    SerializerUtils::getExpand(),
                    SerializerUtils::getFields(),
                    SerializerUtils::getRelations()
                )
            );
        });
    }

    /**
     * @param $summit_id
     * @param $sponsor_id
     * @return mixed
     */
    #[OA\Post(
        path: "/summits/{id}/sponsors/{sponsor_id}/sponsorships",
        operationId: "addSponsorshipsFromTypes",
        description: "Add sponsorships from types",
        parameters: [
            new OA\Parameter(name: "id", description: "Summit ID", in: "path", required: true, schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "sponsor_id", description: "Sponsor ID", in: "path", required: true, schema: new OA\Schema(type: "integer")),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                type: "object",
                required: ["type_ids"],
                properties: [
                    new OA\Property(property: "type_ids", type: "array", items: new OA\Items(type: "integer")),
                ]
            )
        ),
        tags: ["Sponsorships"],
        responses: [
            new OA\Response(response: Response::HTTP_CREATED, description: "Sponsorships created", content: new OA\JsonContent(
                type: "array",
                items: new OA\Items(ref: "#/components/schemas/SummitSponsorship")
            )),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not found"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    public function addFromTypes($summit_id, $sponsor_id): mixed
    {
        return $this->processRequest(function () use ($summit_id, $sponsor_id) {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $current_member = $this->resource_server_context->getCurrentUser();
            if (!is_null($current_member) && !$current_member->isSummitAllowed($summit))
                return $this->error403(['message' => sprintf("Member %s has not permission for this Summit", $current_member->getId())]);

            $sponsor = $summit->getSummitSponsorById(intval($sponsor_id));
            if (is_null($sponsor)) return $this->error404();

            $payload = $this->getJsonPayload(SummitSponsorshipsValidationRulesFactory::buildForAdd(), true);
            $sponsorship_type_ids = $payload['type_ids'];

            $sponsorships = collect($this->service->addSponsorships($summit, $sponsor->getId(), $sponsorship_type_ids));

            return $this->created($sponsorships->map(function ($sponsorship) {
                return SerializerRegistry::getInstance()
                    ->getSerializer($sponsorship)
                    ->serialize(
                        SerializerUtils::getExpand(),
                        SerializerUtils::getFields(),
                        SerializerUtils::getRelations()
                    );
            }));
        });
    }

    /**
     * @param $summit_id
     * @param $sponsor_id
     * @param $sponsorship_id
     * @return mixed
     */
    #[OA\Delete(
        path: "/summits/{id}/sponsors/{sponsor_id}/sponsorships/{sponsorship_id}",
        operationId: "removeSponsorships",
        description: "Remove a sponsorship",
        parameters: [
            new OA\Parameter(name: "id", description: "Summit ID", in: "path", required: true, schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "sponsor_id", description: "Sponsor ID", in: "path", required: true, schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "sponsorship_id", description: "Sponsorship ID", in: "path", required: true, schema: new OA\Schema(type: "integer")),
        ],
        tags: ["Sponsorships"],
        responses: [
            new OA\Response(response: Response::HTTP_NO_CONTENT, description: "Sponsorship deleted"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not found"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    public function remove($summit_id, $sponsor_id, $sponsorship_id): mixed
    {
        return $this->processRequest(function () use ($summit_id, $sponsor_id, $sponsorship_id) {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $current_member = $this->resource_server_context->getCurrentUser();
            if (!is_null($current_member) && !$current_member->isSummitAllowed($summit))
                return $this->error403(['message' => sprintf("Member %s has not permission for this Summit", $current_member->getId())]);

            $this->service->removeSponsorship($summit, $sponsor_id, $sponsorship_id);

            return $this->deleted();
        });
    }

    //Add-Ons

    /**
     * @param $summit_id
     * @param $sponsor_id
     * @param $sponsorship_id
     * @return mixed
     */
    #[OA\Get(
        path: "/summits/{id}/sponsors/{sponsor_id}/sponsorships/{sponsorship_id}/add-ons",
        operationId: "getAllAddOns",
        description: "Get all add-ons for a sponsorship",
        parameters: [
            new OA\Parameter(name: "id", description: "Summit ID", in: "path", required: true, schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "sponsor_id", description: "Sponsor ID", in: "path", required: true, schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "sponsorship_id", description: "Sponsorship ID", in: "path", required: true, schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "page", description: "Page number", in: "query", schema: new OA\Schema(type: "integer", default: 1)),
            new OA\Parameter(name: "per_page", description: "Items per page", in: "query", schema: new OA\Schema(type: "integer", default: 10)),
            new OA\Parameter(name: "expand", description: "Expand relations", in: "query", schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "fields", description: "Fields to return", in: "query", schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "filter", description: "Filter conditions", in: "query", schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "order", description: "Order by", in: "query", schema: new OA\Schema(type: "string")),
        ],
        tags: ["Sponsorships Add-Ons"],
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: "List of add-ons", content: new OA\JsonContent(
                type: "object",
                properties: [
                    new OA\Property(property: "data", type: "array", items: new OA\Items(ref: "#/components/schemas/SummitSponsorshipAddOn")),
                    new OA\Property(property: "page", type: "integer"),
                    new OA\Property(property: "per_page", type: "integer"),
                    new OA\Property(property: "total", type: "integer"),
                ]
            )),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not found"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    public function getAllAddOns($summit_id, $sponsor_id, $sponsorship_id): mixed
    {
        $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
        if (is_null($summit)) return $this->error404();

        $current_member = $this->resource_server_context->getCurrentUser();
        if (!is_null($current_member) && !$current_member->isSummitAllowed($summit))
            return $this->error403(['message' => sprintf("Member %s has not permission for this Summit", $current_member->getId())]);

        $sponsor = $summit->getSummitSponsorById(intval($sponsor_id));
        if (is_null($sponsor)) return $this->error404();

        $sponsorship = $sponsor->getSponsorshipById($sponsorship_id);
        if (is_null($sponsorship)) return $this->error404();

        return $this->_getAll(
            function () {
                return [
                    'name' => Filter::buildStringDefaultOperators(),
                    'type' => Filter::buildStringDefaultOperators(),
                ];
            },
            function () {
                return [
                    'name' => 'sometimes|string',
                    'type' => 'sometimes|string',
                ];
            },
            function () {
                return [
                    'id',
                    'name',
                    'type',
                ];
            },
            function ($filter) use ($summit, $sponsor, $sponsorship) {
                if ($filter instanceof Filter) {
                    $filter->addFilterCondition(FilterElement::makeEqual('summit_id', $summit->getId()));
                    $filter->addFilterCondition(FilterElement::makeEqual('sponsor_id', $sponsor->getId()));
                    $filter->addFilterCondition(FilterElement::makeEqual('sponsorship_id', $sponsorship->getId()));
                }
                return $filter;
            },
            function () {
                return SerializerRegistry::SerializerType_Private;
            },
            null,
            null,
            function ($page, $per_page, $filter, $order, $applyExtraFilters) {
                return $this->sponsorship_add_on_repository->getAllByPage
                (
                    new PagingInfo($page, $per_page),
                    call_user_func($applyExtraFilters, $filter),
                    $order
                );
            }
        );
    }

    /**
     * @param $summit_id
     * @param $sponsor_id
     * @param $sponsorship_id
     * @return mixed
     */
    #[OA\Post(
        path: "/summits/{id}/sponsors/{sponsor_id}/sponsorships/{sponsorship_id}/add-ons",
        operationId: "addNewAddOn",
        description: "Add a new add-on to a sponsorship",
        parameters: [
            new OA\Parameter(name: "id", description: "Summit ID", in: "path", required: true, schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "sponsor_id", description: "Sponsor ID", in: "path", required: true, schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "sponsorship_id", description: "Sponsorship ID", in: "path", required: true, schema: new OA\Schema(type: "integer")),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                type: "object",
                required: ["name", "type"],
                properties: [
                    new OA\Property(property: "name", type: "string"),
                    new OA\Property(property: "type", type: "string"),
                    new OA\Property(property: "label", type: "string"),
                    new OA\Property(property: "size", type: "string"),
                ]
            )
        ),
        tags: ["Sponsorships Add-Ons"],
        responses: [
            new OA\Response(response: Response::HTTP_CREATED, description: "Add-on created", content: new OA\JsonContent(ref: "#/components/schemas/SummitSponsorshipAddOn")),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not found"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    public function addNewAddOn($summit_id, $sponsor_id, $sponsorship_id): mixed
    {
         return $this->processRequest(function () use ($summit_id, $sponsor_id, $sponsorship_id) {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $current_member = $this->resource_server_context->getCurrentUser();
            if (!is_null($current_member) && !$current_member->isSummitAllowed($summit))
                return $this->error403(['message' => sprintf("Member %s has not permission for this Summit", $current_member->getId())]);

            $payload = $this->getJsonPayload(SummitSponsorshipAddOnsValidationRulesFactory::buildForAdd(), true);

            $add_on = $this->service->addNewAddOn($summit, $sponsor_id, $sponsorship_id, $payload);

            return $this->created(SerializerRegistry::getInstance()
                ->getSerializer($add_on)
                ->serialize(
                    SerializerUtils::getExpand(),
                    SerializerUtils::getFields(),
                    SerializerUtils::getRelations()
                ));
        });
    }

    /**
     * @param $summit_id
     * @param $sponsor_id
     * @param $sponsorship_id
     * @param $add_on_id
     * @return mixed
     */
    #[OA\Get(
        path: "/summits/{id}/sponsors/{sponsor_id}/sponsorships/{sponsorship_id}/add-ons/{add_on_id}",
        operationId: "getAddOnById",
        description: "Get add-on by ID",
        parameters: [
            new OA\Parameter(name: "id", description: "Summit ID", in: "path", required: true, schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "sponsor_id", description: "Sponsor ID", in: "path", required: true, schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "sponsorship_id", description: "Sponsorship ID", in: "path", required: true, schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "add_on_id", description: "Add-on ID", in: "path", required: true, schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "expand", description: "Expand relations", in: "query", schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "fields", description: "Fields to return", in: "query", schema: new OA\Schema(type: "string")),
        ],
        tags: ["Sponsorships Add-Ons"],
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: "Add-on data", content: new OA\JsonContent(ref: "#/components/schemas/SummitSponsorshipAddOn")),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not found"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    public function getAddOnById($summit_id, $sponsor_id, $sponsorship_id, $add_on_id): mixed
    {
        return $this->processRequest(function () use ($summit_id, $sponsor_id, $sponsorship_id, $add_on_id) {
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $current_member = $this->resource_server_context->getCurrentUser();
            if (!is_null($current_member) && !$current_member->isSummitAllowed($summit))
                return $this->error403(['message' => sprintf("Member %s has not permission for this Summit", $current_member->getId())]);

            $sponsor = $summit->getSummitSponsorById(intval($sponsor_id));
            if (is_null($sponsor)) return $this->error404();

            $sponsorship = $sponsor->getSponsorshipById($sponsorship_id);
            if (is_null($sponsorship)) return $this->error404();

            $add_on = $sponsorship->getAddOnById(intval($add_on_id));

            return $this->ok(SerializerRegistry::getInstance()
                ->getSerializer($add_on)
                ->serialize(
                    SerializerUtils::getExpand(),
                    SerializerUtils::getFields(),
                    SerializerUtils::getRelations()
                )
            );
        });
    }

    /**
     * @param $summit_id
     * @param $sponsor_id
     * @param $sponsorship_id
     * @param $add_on_id
     * @return mixed
     */
    #[OA\Put(
        path: "/summits/{id}/sponsors/{sponsor_id}/sponsorships/{sponsorship_id}/add-ons/{add_on_id}",
        operationId: "updateAddOn",
        description: "Update an add-on",
        parameters: [
            new OA\Parameter(name: "id", description: "Summit ID", in: "path", required: true, schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "sponsor_id", description: "Sponsor ID", in: "path", required: true, schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "sponsorship_id", description: "Sponsorship ID", in: "path", required: true, schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "add_on_id", description: "Add-on ID", in: "path", required: true, schema: new OA\Schema(type: "integer")),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                type: "object",
                properties: [
                    new OA\Property(property: "name", type: "string"),
                    new OA\Property(property: "type", type: "string"),
                    new OA\Property(property: "label", type: "string"),
                    new OA\Property(property: "size", type: "string"),
                ]
            )
        ),
        tags: ["Sponsorships Add-Ons"],
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: "Add-on updated", content: new OA\JsonContent(ref: "#/components/schemas/SummitSponsorshipAddOn")),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not found"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    public function updateAddOn($summit_id, $sponsor_id, $sponsorship_id, $add_on_id): mixed
    {
        return $this->processRequest(function () use ($summit_id, $sponsor_id, $sponsorship_id, $add_on_id) {
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $current_member = $this->resource_server_context->getCurrentUser();
            if (!is_null($current_member) && !$current_member->isSummitAllowed($summit))
                return $this->error403(['message' => sprintf("Member %s has not permission for this Summit", $current_member->getId())]);

            $payload = $this->getJsonPayload(SummitSponsorshipAddOnsValidationRulesFactory::buildForUpdate(), true);

            $add_on = $this->service->updateAddOn($summit, $sponsor_id, $sponsorship_id, $add_on_id, $payload);

            return $this->updated(SerializerRegistry::getInstance()
                ->getSerializer($add_on)
                ->serialize(
                    SerializerUtils::getExpand(),
                    SerializerUtils::getFields(),
                    SerializerUtils::getRelations()
                )
            );
        });
    }

    /**
     * @param $summit_id
     * @param $sponsor_id
     * @param $sponsorship_id
     * @param $add_on_id
     * @return mixed
     */
    #[OA\Delete(
        path: "/summits/{id}/sponsors/{sponsor_id}/sponsorships/{sponsorship_id}/add-ons/{add_on_id}",
        operationId: "removeAddOn",
        description: "Remove an add-on",
        parameters: [
            new OA\Parameter(name: "id", description: "Summit ID", in: "path", required: true, schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "sponsor_id", description: "Sponsor ID", in: "path", required: true, schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "sponsorship_id", description: "Sponsorship ID", in: "path", required: true, schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "add_on_id", description: "Add-on ID", in: "path", required: true, schema: new OA\Schema(type: "integer")),
        ],
        tags: ["Sponsorships Add-Ons"],
        responses: [
            new OA\Response(response: Response::HTTP_NO_CONTENT, description: "Add-on deleted"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not found"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    public function removeAddOn($summit_id, $sponsor_id, $sponsorship_id, $add_on_id): mixed
    {
        return $this->processRequest(function () use ($summit_id, $sponsor_id, $sponsorship_id, $add_on_id) {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $current_member = $this->resource_server_context->getCurrentUser();
            if (!is_null($current_member) && !$current_member->isSummitAllowed($summit))
                return $this->error403(['message' => sprintf("Member %s has not permission for this Summit", $current_member->getId())]);

            $this->service->removeAddOn($summit, $sponsor_id, $sponsorship_id, $add_on_id);

            return $this->deleted();
        });
    }

    //Add-Ons metadata

    /**
     * @param $summit_id
     * @return mixed
     */
    #[OA\Get(
        path: "/summits/{id}/add-ons/metadata",
        operationId: "getAddOnsMetadata",
        description: "Get add-ons metadata",
        parameters: [
            new OA\Parameter(name: "id", description: "Summit ID", in: "path", required: true, schema: new OA\Schema(type: "integer")),
        ],
        tags: ["Sponsorships Add-Ons"],
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: "Add-ons metadata", content: new OA\JsonContent(type: "object")),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    public function getMetadata($summit_id): mixed
    {
        $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
        if (is_null($summit)) return $this->error404();

        return $this->ok
        (
            $this->sponsorship_add_on_repository->getMetadata($summit)
        );
    }
}