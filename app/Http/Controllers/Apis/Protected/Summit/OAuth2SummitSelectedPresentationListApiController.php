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

use App\ModelSerializers\SerializerUtils;
use App\Services\Model\ISummitSelectedPresentationListService;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Validator;
use models\main\IMemberRepository;
use models\oauth2\IResourceServerContext;
use models\summit\ISummitRepository;
use models\summit\SummitSelectedPresentation;
use ModelSerializers\SerializerRegistry;

/**
 * Class OAuth2SummitSelectedPresentationListApiController
 * @package App\Http\Controllers
 */
class OAuth2SummitSelectedPresentationListApiController
    extends OAuth2ProtectedController
{
    use RequestProcessor;
    /**
     * @var ISummitRepository
     */
    private $summit_repository;

    /**
     * @var IMemberRepository
     */
    private $member_repository;

    /**
     * @var ISummitSelectedPresentationListService
     */
    private $service;

    /**
     * OAuth2SummitSelectedPresentationListApiController constructor.
     * @param ISummitRepository $summit_repository
     * @param IMemberRepository $member_repository
     * @param ISummitSelectedPresentationListService $service
     * @param IResourceServerContext $resource_server_context
     */
    public function __construct
    (
        ISummitRepository $summit_repository,
        IMemberRepository $member_repository,
        ISummitSelectedPresentationListService $service,
        IResourceServerContext $resource_server_context
    )
    {
        parent::__construct($resource_server_context);
        $this->summit_repository = $summit_repository;
        $this->member_repository = $member_repository;
        $this->service = $service;
    }

    /**
     * @param $summit_id
     * @param $selection_plan_id
     * @param $track_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function getTeamSelectionList($summit_id, $selection_plan_id, $track_id){
        return $this->processRequest(function () use($summit_id, $selection_plan_id, $track_id){

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $selection_list = $this->service->getTeamSelectionList
            (
                $summit,
                intval($selection_plan_id),
                intval($track_id)
            );

            return $this->ok(SerializerRegistry::getInstance()->getSerializer($selection_list)->serialize
            (
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));
        });
    }

    /**
     * @param $summit_id
     * @param $selection_plan_id
     * @param $track_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function createTeamSelectionList($summit_id, $selection_plan_id, $track_id){
        return $this->processRequest(function () use($summit_id, $selection_plan_id, $track_id){

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $selection_list = $this->service->createTeamSelectionList($summit, intval($selection_plan_id), intval($track_id));

            return $this->created(SerializerRegistry::getInstance()->getSerializer($selection_list)->serialize
            (
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));
        });
    }

    /**
     * @param $summit_id
     * @param $selection_plan_id
     * @param $track_id
     * @param $owner_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function getIndividualSelectionList($summit_id, $selection_plan_id, $track_id, $owner_id){
        return $this->processRequest(function () use($summit_id, $selection_plan_id, $track_id, $owner_id){

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $selection_list = $this->service->getIndividualSelectionList($summit, intval($selection_plan_id), intval($track_id), intval($owner_id));

            return $this->ok(SerializerRegistry::getInstance()->getSerializer($selection_list)->serialize
            (
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));
        });
    }

    /**
     * @param $summit_id
     * @param $selection_plan_id
     * @param $track_id
     * @param $owner_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function createIndividualSelectionList($summit_id, $selection_plan_id, $track_id){
        return $this->processRequest(function () use($summit_id, $selection_plan_id, $track_id) {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $selection_list = $this->service->createIndividualSelectionList($summit, intval($selection_plan_id), intval($track_id), $this->resource_server_context->getCurrentUserId());

            return $this->created(SerializerRegistry::getInstance()->getSerializer($selection_list)->serialize(
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));
        });
    }

    /**
     * @param $summit_id
     * @param $selection_plan_id
     * @param $track_id
     * @param $list_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function reorderSelectionList($summit_id, $selection_plan_id, $track_id, $list_id){
        return $this->processRequest(function () use($summit_id, $selection_plan_id, $track_id, $list_id) {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $data = Request::json();
            $payload = $data->all();
            // Creates a Validator instance and validates the data.
            $validation = Validator::make($payload,[
                'hash' => 'sometimes|nullable|string',
                'collection' => sprintf('required|string|in:%s,%s', SummitSelectedPresentation::CollectionMaybe, SummitSelectedPresentation::CollectionSelected),
                'presentations' => 'nullable|sometimes|int_array',
            ]);

            if ($validation->fails()) {
                $messages = $validation->messages()->toArray();
                return $this->error412
                (
                    $messages
                );
            }

            $selection_list = $this->service->reorderList($summit, intval($selection_plan_id), intval($track_id), intval($list_id), $payload);

            return $this->updated(SerializerRegistry::getInstance()->getSerializer($selection_list)->serialize
            (
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));
        });
    }

    /**
     * @param $summit_id
     * @param $selection_plan_id
     * @param $track_id
     * @param $collection
     * @param $presentation_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function assignPresentationToMyIndividualList($summit_id, $selection_plan_id, $track_id, $collection, $presentation_id){
        return $this->processRequest(function () use($summit_id, $selection_plan_id, $track_id, $collection,$presentation_id) {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $selection_list = $this->service->assignPresentationToMyIndividualList($summit, intval($selection_plan_id), intval($track_id), trim($collection), intval($presentation_id));

            return $this->created(SerializerRegistry::getInstance()->getSerializer($selection_list)->serialize
            (
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));
        });
    }

    /**
     * @param $summit_id
     * @param $selection_plan_id
     * @param $track_id
     * @param $collection
     * @param $presentation_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function removePresentationFromMyIndividualList($summit_id, $selection_plan_id, $track_id, $collection, $presentation_id){
        return $this->processRequest(function () use($summit_id, $selection_plan_id, $track_id, $collection,$presentation_id) {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $selection_list = $this->service->removePresentationFromMyIndividualList($summit, intval($selection_plan_id), intval($track_id), intval($presentation_id));

            return $this->created(SerializerRegistry::getInstance()->getSerializer($selection_list)->serialize
            (
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));
        });
    }
}