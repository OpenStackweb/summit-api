<?php namespace App\Http\Controllers;
/*
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

use App\Models\Foundation\Summit\Repositories\ISummitProposedScheduleAllowedDayRepository;
use App\Models\Foundation\Summit\Repositories\ISummitProposedScheduleAllowedLocationRepository;
use App\ModelSerializers\SerializerUtils;
use App\Services\Model\ISummitProposedScheduleAllowedLocationService;
use models\oauth2\IResourceServerContext;
use models\summit\ISummitRepository;
use models\summit\PresentationCategory;
use models\summit\Summit;
use ModelSerializers\SerializerRegistry;
use utils\Filter;
use utils\FilterElement;
use utils\PagingInfo;

/**
 * Class OAuth2SummitProposedScheduleAllowedLocationApiController
 * @package App\Http\Controllers
 */
final class OAuth2SummitProposedScheduleAllowedLocationApiController
    extends OAuth2ProtectedController
{

    use GetAndValidateJsonPayload;

    use RequestProcessor;

    use ParametrizedGetAll;

    /**
     * @var ISummitRepository
     */
    private $summit_repository;

    /**
     * @var ISummitProposedScheduleAllowedDayRepository
     */
    private $allowed_time_frames_repository;

    /**
     * @var ISummitProposedScheduleAllowedLocationService
     */
    private $service;

    /**
     * @param ISummitRepository $summit_repository
     * @param ISummitProposedScheduleAllowedDayRepository $allowed_time_frames_repository
     * @param ISummitProposedScheduleAllowedLocationRepository $repository
     * @param ISummitProposedScheduleAllowedLocationService $service
     * @param IResourceServerContext $resource_server_context
     */
    public function __construct
    (
        ISummitRepository $summit_repository,
        ISummitProposedScheduleAllowedDayRepository $allowed_time_frames_repository,
        ISummitProposedScheduleAllowedLocationRepository $repository,
        ISummitProposedScheduleAllowedLocationService $service,
        IResourceServerContext $resource_server_context
    )
    {
        parent::__construct($resource_server_context);
        $this->allowed_time_frames_repository = $allowed_time_frames_repository;
        $this->summit_repository = $summit_repository;
        $this->service = $service;
        $this->repository = $repository;
    }

    /**
     * @param Summit $summit
     * @param PresentationCategory $track
     * @return bool
     */
    private function isCurrentUserAuth(Summit $summit, PresentationCategory $track):bool{
        $current_member = $this->resource_server_context->getCurrentUser();
        if(is_null($current_member)) return false;
        if($current_member->isAdmin()) return true;
        if($summit->isSummitAdmin($current_member)) return true;
        if($summit->isTrackChair($current_member, $track)) return true;
        return false;
    }

    /**
     * @param $summit_id
     * @param $track_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function getAllAllowedLocationByTrack($summit_id, $track_id){

        $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find(intval($summit_id));
        if (is_null($summit)) return $this->error404();

        $track = $summit->getPresentationCategory(intval($track_id));
        if(is_null($track) || !$track->isChairVisible()) return $this->error404();

        if(!$this->isCurrentUserAuth($summit, $track))
            return $this->error403();

        return $this->_getAll(
            function () {
                return [
                    'location_id' => ['=='],
                    'track_id' => ['=='],
                ];
            },
            function () {
                return [
                    'location_id' => 'sometimes|integer',
                    'track_id' => 'sometimes|integer',
                ];
            },
            function () {
                return [
                    'location_id',
                    'track_id'
                ];
            },
            function ($filter) use ($track_id) {
                if ($filter instanceof Filter) {
                    $filter->addFilterCondition(FilterElement::makeEqual('track_id', intval($track_id)));
                }
                return $filter;
            },
            function () {
                return SerializerRegistry::SerializerType_Public;
            }
        );
    }

    /**
     * @param $summit_id
     * @param $track_id
     * @return mixed
     */
    public function addAllowedLocationToTrack($summit_id, $track_id){

        return $this->processRequest(function () use ($summit_id, $track_id) {
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find(intval($summit_id));
            if (is_null($summit)) return $this->error404();

            $track = $summit->getPresentationCategory(intval($track_id));
            if(is_null($track) || !$track->isChairVisible()) return $this->error404();

            if(!$this->isCurrentUserAuth($summit, $track))
                return $this->error403();

            $payload = $this->getJsonPayload(SummitProposedScheduleAllowedLocationValidationRulesFactory::buildForAdd());

            return $this->created(SerializerRegistry::getInstance()->getSerializer($this->service->addProposedLocationToTrack($track, $payload))
                ->serialize
                (
                    SerializerUtils::getExpand(),
                    SerializerUtils::getFields(),
                    SerializerUtils::getRelations()
                ));
        });
    }

    /**
     * @param $summit_id
     * @param $track_id
     * @param $location_id
     * @return mixed
     */
    public function getAllowedLocationFromTrack($summit_id, $track_id, $location_id){
        return $this->processRequest(function () use ($summit_id, $track_id, $location_id) {
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find(intval($summit_id));
            if (is_null($summit)) return $this->error404();

            $track = $summit->getPresentationCategory(intval($track_id));
            if(is_null($track) || !$track->isChairVisible()) return $this->error404();

            if(!$this->isCurrentUserAuth($summit, $track))
                return $this->error403();

            $allowed_location = $track->getAllowedLocationById(intval($location_id));

            if(is_null($allowed_location)) return $this->error404();

            return $this->ok(SerializerRegistry::getInstance()->getSerializer($allowed_location)
                ->serialize
                (
                    SerializerUtils::getExpand(),
                    SerializerUtils::getFields(),
                    SerializerUtils::getRelations()
                ));
        });
    }

    public function removeAllowedLocationFromTrack($summit_id, $track_id, $location_id){
        return $this->processRequest(function () use ($summit_id, $track_id, $location_id) {
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find(intval($summit_id));
            if (is_null($summit)) return $this->error404();

            $track = $summit->getPresentationCategory(intval($track_id));
            if(is_null($track) || !$track->isChairVisible()) return $this->error404();

            if(!$this->isCurrentUserAuth($summit, $track))
                return $this->error403();

            $this->service->deleteProposedLocationFromTrack($track, intval($location_id));

            return $this->deleted();
        });
    }

    /**
     * @param $summit_id
     * @param $track_id
     * @return mixed
     */
    public function removeAllAllowedLocationFromTrack($summit_id, $track_id){
        return $this->processRequest(function () use ($summit_id, $track_id) {
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find(intval($summit_id));
            if (is_null($summit)) return $this->error404();

            $track = $summit->getPresentationCategory(intval($track_id));
            if(is_null($track) || !$track->isChairVisible()) return $this->error404();

            if(!$this->isCurrentUserAuth($summit, $track))
                return $this->error403();

            $this->service->deleteAllProposedLocationFromTrack($track);

            return $this->deleted();
        });
    }

    /**
     * @param $summit_id
     * @param $track_id
     * @param $location_id
     * @return mixed
     */
    public function addTimeFrame2AllowedLocation($summit_id, $track_id, $location_id){
        return $this->processRequest(function () use ($summit_id, $track_id, $location_id) {
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find(intval($summit_id));
            if (is_null($summit)) return $this->error404();

            $track = $summit->getPresentationCategory(intval($track_id));
            if(is_null($track) || !$track->isChairVisible()) return $this->error404();

            if(!$this->isCurrentUserAuth($summit, $track))
                return $this->error403();

            $payload = $this->getJsonPayload(SummitProposedScheduleAllowedDayValidationRulesFactory::buildForAdd());

            return $this->created(SerializerRegistry::getInstance()->getSerializer($this->service->addAllowedDayToProposedLocation($track, intval($location_id), $payload))
                ->serialize
                (
                    SerializerUtils::getExpand(),
                    SerializerUtils::getFields(),
                    SerializerUtils::getRelations()
                ));
        });
    }

    /**
     * @param $summit_id
     * @param $track_id
     * @param $location_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function getAllTimeFrameFromAllowedLocation($summit_id, $track_id, $location_id){

        $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find(intval($summit_id));
        if (is_null($summit)) return $this->error404();

        $track = $summit->getPresentationCategory(intval($track_id));
        if(is_null($track) || !$track->isChairVisible()) return $this->error404();

        if(!$this->isCurrentUserAuth($summit, $track))
            return $this->error403();

        $allowed_location = $track->getAllowedLocationById(intval($location_id));
        if(is_null($allowed_location)) return $this->error404();

        return $this->_getAll(
            function () {
                return [
                    'allowed_location_id' => ['=='],
                    'track_id' => ['=='],
                    'location_id' =>  ['=='],
                    'day' => ['<','>','==','>=','<='],
                    'opening_hour' => ['<','>','==','>=','<='],
                    'closing_hour' => ['<','>','==','>=','<='],
                ];
            },
            function () {
                return [
                    'allowed_location_id' => 'sometimes|integer',
                    'track_id' => 'sometimes|integer',
                    'location_id' =>  'sometimes|integer',
                    'day' => 'sometimes|integer',
                    'opening_hour' => 'sometimes|integer',
                    'closing_hour' => 'sometimes|integer',
                ];
            },
            function () {
                return [
                    'id',
                    'day',
                    'opening_hour',
                    'closing_hour',
                    'location_id',
                    'allowed_location_id',
                    'track_id',
                ];
            },
            function ($filter) use ($allowed_location) {
                if ($filter instanceof Filter) {
                    $filter->addFilterCondition
                    (
                        FilterElement::makeEqual('allowed_location_id', $allowed_location->getId())
                    );
                }
                return $filter;
            },
            function () {
                return SerializerRegistry::SerializerType_Public;
            },
            null,
            null,
            function ($page, $per_page, $filter, $order, $applyExtraFilters) {
                return $this->allowed_time_frames_repository->getAllByPage
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
     * @param $track_id
     * @param $location_id
     * @param $time_frame_id
     * @return mixed
     */
    public function getTimeFrameFromAllowedLocation($summit_id, $track_id, $location_id, $time_frame_id){
        return $this->processRequest(function() use($summit_id, $track_id, $location_id, $time_frame_id){
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find(intval($summit_id));
            if (is_null($summit)) return $this->error404();

            $track = $summit->getPresentationCategory(intval($track_id));
            if(is_null($track) || !$track->isChairVisible()) return $this->error404();

            if(!$this->isCurrentUserAuth($summit, $track))
                return $this->error403();

            $allowed_location = $track->getAllowedLocationById(intval($location_id));

            if(is_null($allowed_location)) return $this->error404();

            $time_frame = $allowed_location->getAllowedTimeFrameById(intval($time_frame_id));
            if(is_null($time_frame)) return $this->error404();

            return $this->ok(SerializerRegistry::getInstance()->getSerializer($time_frame)
                ->serialize
                (
                    SerializerUtils::getExpand(),
                    SerializerUtils::getFields(),
                    SerializerUtils::getRelations()
                ));
        });
    }

    /**
     * @param $summit_id
     * @param $track_id
     * @param $location_id
     * @param $time_frame_id
     * @return mixed
     */
    public function removeTimeFrameFromAllowedLocation($summit_id, $track_id, $location_id, $time_frame_id){
        return $this->processRequest(function() use($summit_id, $track_id, $location_id, $time_frame_id){
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find(intval($summit_id));
            if (is_null($summit)) return $this->error404();

            $track = $summit->getPresentationCategory(intval($track_id));
            if(is_null($track) || !$track->isChairVisible()) return $this->error404();

            if(!$this->isCurrentUserAuth($summit, $track))
                return $this->error403();

            $this->service->deleteAllowedDayToProposedLocation($track, intval($location_id), intval($time_frame_id));

            return $this->deleted();
        });
    }

    /**
     * @param $summit_id
     * @param $track_id
     * @param $location_id
     * @return mixed
     */
    public function removeAllTimeFrameFromAllowedLocation($summit_id, $track_id, $location_id){
        return $this->processRequest(function() use($summit_id, $track_id, $location_id){
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find(intval($summit_id));
            if (is_null($summit)) return $this->error404();

            $track = $summit->getPresentationCategory(intval($track_id));
            if(is_null($track) || !$track->isChairVisible()) return $this->error404();

            if(!$this->isCurrentUserAuth($summit, $track))
                return $this->error403();

            $this->service->deleteAllAllowedDayToProposedLocation($track, intval($location_id));

            return $this->deleted();
        });
    }

    /**
     * @param $summit_id
     * @param $track_id
     * @param $location_id
     * @param $time_frame_id
     * @return mixed
     */
    public function updateTimeFrameFromAllowedLocation($summit_id, $track_id, $location_id, $time_frame_id){
        return $this->processRequest(function () use ($summit_id, $track_id, $location_id, $time_frame_id) {
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find(intval($summit_id));
            if (is_null($summit)) return $this->error404();

            $track = $summit->getPresentationCategory(intval($track_id));
            if(is_null($track) || !$track->isChairVisible()) return $this->error404();

            if(!$this->isCurrentUserAuth($summit, $track))
                return $this->error403();

            $payload = $this->getJsonPayload(SummitProposedScheduleAllowedDayValidationRulesFactory::buildForUpdate());

            return $this->updated(SerializerRegistry::getInstance()->getSerializer($this->service->updateAllowedDayToProposedLocation($track, intval($location_id), intval($time_frame_id), $payload))
                ->serialize
                (
                    SerializerUtils::getExpand(),
                    SerializerUtils::getFields(),
                    SerializerUtils::getRelations()
                ));
        });
    }
}