<?php namespace App\Http\Controllers;
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

use App\ModelSerializers\SerializerUtils;
use App\Rules\Boolean;
use App\Services\Model\ISummitMetricService;
use models\main\IMemberRepository;
use models\oauth2\IResourceServerContext;
use models\summit\ISummitMetricType;
use models\summit\ISummitRepository;
use ModelSerializers\SerializerRegistry;

/**
 * Class OAuth2SummitMetricsApiController
 * @package App\Http\Controllers
 */
final class OAuth2SummitMetricsApiController extends OAuth2ProtectedController
{
    use GetAndValidateJsonPayload;

    use RequestProcessor;

    /**
     * @var ISummitRepository
     */
    private $summit_repository;

    /**
     * @var ISummitMetricService
     */
    private $service;

    /**
     * OAuth2SummitMembersApiController constructor.
     * @param IMemberRepository $member_repository
     * @param ISummitRepository $summit_repository
     * @param ISummitMetricService $service
     * @param IResourceServerContext $resource_server_context
     */
    public function __construct
    (
        IMemberRepository      $member_repository,
        ISummitRepository      $summit_repository,
        ISummitMetricService   $service,
        IResourceServerContext $resource_server_context
    )
    {
        parent::__construct($resource_server_context);
        $this->summit_repository = $summit_repository;
        $this->repository = $member_repository;
        $this->service = $service;
    }

    /**
     * @param $summit_id
     * @param $member_id
     * @param $event_id
     * @return mixed
     */
    public function enter($summit_id)
    {
        return $this->processRequest(function () use ($summit_id) {
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $current_member = $this->resource_server_context->getCurrentUser();
            if (is_null($current_member)) return $this->error403();

            $payload = $this->getJsonPayload(
                [
                    'type' => 'required|string|in:' . implode(",", ISummitMetricType::ValidTypes),
                    'source_id' => 'sometimes|integer',
                    'location' => 'sometimes|string',
                ]
            );

            $metric = $this->service->enter($summit, $current_member, $payload);

            return $this->updated(SerializerRegistry::getInstance()->getSerializer($metric)->serialize(
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));
        });
    }

    /**
     * @param $summit_id
     * @param $member_id
     * @param $event_id
     * @return mixed
     */
    public function leave($summit_id)
    {
        return $this->processRequest(function () use ($summit_id) {
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $current_member = $this->resource_server_context->getCurrentUser();
            if (is_null($current_member)) return $this->error403();


            $payload = $this->getJsonPayload(
                [
                    'type' => 'required|string|in:' . implode(",", ISummitMetricType::ValidTypes),
                    'source_id' => 'sometimes|integer',
                    'location' => 'sometimes|string',
                ]
            );
            $metric = $this->service->leave($summit, $current_member, $payload);

            return $this->updated(SerializerRegistry::getInstance()->getSerializer($metric)->serialize(
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));
        });
    }

    /**
     * @param $summit_id
     * @param $member_id
     * @param $event_id
     * @return mixed
     */
    public function enterToEvent($summit_id, $member_id, $event_id)
    {
        return $this->processRequest(function () use ($summit_id, $member_id, $event_id) {
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $current_member = $this->resource_server_context->getCurrentUser();
            if (is_null($current_member)) return $this->error403();

            $metric = $this->service->enter($summit, $current_member, [
                'type' => ISummitMetricType::Event,
                'source_id' => intval($event_id)
            ]);

            return $this->updated(SerializerRegistry::getInstance()->getSerializer($metric)->serialize(
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));
        });
    }

    /**
     * @param $summit_id
     * @param $member_id
     * @param $event_id
     * @return mixed
     */
    public function leaveFromEvent($summit_id, $member_id, $event_id)
    {
        return $this->processRequest(function () use ($summit_id, $member_id, $event_id) {
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $current_member = $this->resource_server_context->getCurrentUser();
            if (is_null($current_member)) return $this->error403();

            $metric = $this->service->leave($summit, $current_member, [
                'type' => ISummitMetricType::Event,
                'source_id' => intval($event_id)
            ]);

            return $this->updated(SerializerRegistry::getInstance()->getSerializer($metric)->serialize(
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));
        });
    }

    public function onSiteEnter($summit_id)
    {
        return $this->processRequest(function () use ($summit_id) {
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $current_member = $this->resource_server_context->getCurrentUser();
            if (is_null($current_member)) return $this->error403();

            $payload = $this->getJsonPayload([
                'attendee_id' => 'required|integer',
                'room_id' => 'sometimes|integer',
                'event_id' => 'sometimes|integer',
                'required_access_levels' => 'sometimes|int_array',
                'check_ingress' =>  ['sometimes', new Boolean],
            ]);

            $metric = $this->service->onSiteEnter($summit, $current_member, $payload);

            return $this->created(SerializerRegistry::getInstance()->getSerializer($metric)->serialize
            (
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));
        });
    }

    public function onSiteLeave($summit_id)
    {
        return $this->processRequest(function () use ($summit_id) {
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $current_member = $this->resource_server_context->getCurrentUser();
            if (is_null($current_member)) return $this->error403();

            $payload = $this->getJsonPayload([
                'attendee_id' => 'required|integer',
                'room_id' => 'sometimes|integer',
                'event_id' => 'sometimes|integer',
                'required_access_levels' => 'sometimes|int_array',
            ]);

            $metric = $this->service->onSiteLeave($summit, $current_member, $payload);

            return $this->created(SerializerRegistry::getInstance()->getSerializer($metric)->serialize(
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));
        });
    }
}