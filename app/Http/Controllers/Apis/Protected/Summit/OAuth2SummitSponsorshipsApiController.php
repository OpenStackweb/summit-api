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
use Illuminate\Http\JsonResponse;
use models\oauth2\IResourceServerContext;
use models\summit\ISummitRepository;
use ModelSerializers\SerializerRegistry;
use utils\Filter;
use utils\FilterElement;
use utils\PagingInfo;

/**
 * Class OAuth2SummitSponsorshipsApiController
 * @package App\Http\Controllers
 */
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
    public function getAll($summit_id, $sponsor_id): mixed
    {
        $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
        if (is_null($summit)) return $this->error404();

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
    public function getById($summit_id, $sponsor_id, $sponsorship_id): mixed
    {
        return $this->processRequest(function () use ($summit_id, $sponsor_id, $sponsorship_id) {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

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
    public function addFromTypes($summit_id, $sponsor_id): mixed
    {
        return $this->processRequest(function () use ($summit_id, $sponsor_id) {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

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
    public function remove($summit_id, $sponsor_id, $sponsorship_id): mixed
    {
        return $this->processRequest(function () use ($summit_id, $sponsor_id, $sponsorship_id) {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

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
    public function getAllAddOns($summit_id, $sponsor_id, $sponsorship_id): mixed
    {
        $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
        if (is_null($summit)) return $this->error404();

        $sponsor = $summit->getSummitSponsorById(intval($sponsor_id));
        if (is_null($sponsor)) return $this->error404();

        $sponsorship = $sponsor->getSponsorshipById($sponsorship_id);
        if (is_null($sponsorship)) return $this->error404();

        return $this->_getAll(
            function () {
                return [
                    'name' => Filter::buildStringDefaultOperators(),
                    'type' => Filter::buildEpochDefaultOperators(),
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
    public function addNewAddOn($summit_id, $sponsor_id, $sponsorship_id): mixed
    {
         return $this->processRequest(function () use ($summit_id, $sponsor_id, $sponsorship_id) {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

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
    public function getAddOnById($summit_id, $sponsor_id, $sponsorship_id, $add_on_id): mixed
    {
        return $this->processRequest(function () use ($summit_id, $sponsor_id, $sponsorship_id, $add_on_id) {
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

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
    public function updateAddOn($summit_id, $sponsor_id, $sponsorship_id, $add_on_id): mixed
    {
        return $this->processRequest(function () use ($summit_id, $sponsor_id, $sponsorship_id, $add_on_id) {
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

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
    public function removeAddOn($summit_id, $sponsor_id, $sponsorship_id, $add_on_id): mixed
    {
        return $this->processRequest(function () use ($summit_id, $sponsor_id, $sponsorship_id, $add_on_id) {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $this->service->removeAddOn($summit, $sponsor_id, $sponsorship_id, $add_on_id);

            return $this->deleted();
        });
    }
}