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

use App\Models\Exceptions\AuthzException;
use App\Services\Model\ISummitSelectedPresentationListService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Validator;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use models\main\IMemberRepository;
use models\oauth2\IResourceServerContext;
use models\summit\ISummitRepository;
use models\summit\SummitSelectedPresentation;
use ModelSerializers\SerializerRegistry;
use Exception;
/**
 * Class OAuth2SummitSelectedPresentationListApiController
 * @package App\Http\Controllers
 */
class OAuth2SummitSelectedPresentationListApiController
    extends OAuth2ProtectedController
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
     * @param $track_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function getTeamSelectionList($summit_id, $track_id){
        try {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $selection_list = $this->service->getTeamSelectionList($summit, intval($track_id));

            return $this->ok(SerializerRegistry::getInstance()->getSerializer($selection_list)->serialize(Request::input('expand', '')));
        }
        catch (ValidationException $ex) {
            Log::warning($ex);
            return $this->error412($ex->getMessages());
        }
        catch(EntityNotFoundException $ex)
        {
            Log::warning($ex);
            return $this->error404($ex->getMessage());
        }
        catch (AuthzException $ex){
            Log::warning($ex);
            return $this->error403($ex->getMessage());
        }
        catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    /**
     * @param $summit_id
     * @param $track_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function createTeamSelectionList($summit_id, $track_id){
        try {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $selection_list = $this->service->createTeamSelectionList($summit, intval($track_id));

            return $this->created(SerializerRegistry::getInstance()->getSerializer($selection_list)->serialize(Request::input('expand', '')));
        }
        catch (ValidationException $ex) {
            Log::warning($ex);
            return $this->error412($ex->getMessages());
        }
        catch(EntityNotFoundException $ex)
        {
            Log::warning($ex);
            return $this->error404($ex->getMessage());
        }
        catch (AuthzException $ex){
            Log::warning($ex);
            return $this->error403($ex->getMessage());
        }
        catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    /**
     * @param $summit_id
     * @param $track_id
     * @param $owner_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function getIndividualSelectionList($summit_id, $track_id, $owner_id){
        try {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $selection_list = $this->service->getIndividualSelectionList($summit, intval($track_id), intval($owner_id));

            return $this->ok(SerializerRegistry::getInstance()->getSerializer($selection_list)->serialize(Request::input('expand', '')));
        }
        catch (ValidationException $ex) {
            Log::warning($ex);
            return $this->error412($ex->getMessages());
        }
        catch(EntityNotFoundException $ex)
        {
            Log::warning($ex);
            return $this->error404($ex->getMessage());
        }
        catch (AuthzException $ex){
            Log::warning($ex);
            return $this->error403($ex->getMessage());
        }
        catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    /**
     * @param $summit_id
     * @param $track_id
     * @param $owner_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function createIndividualSelectionList($summit_id, $track_id){
        try {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $selection_list = $this->service->createIndividualSelectionList($summit, intval($track_id));

            return $this->created(SerializerRegistry::getInstance()->getSerializer($selection_list)->serialize(Request::input('expand', '')));
        }
        catch (ValidationException $ex) {
            Log::warning($ex);
            return $this->error412($ex->getMessages());
        }
        catch(EntityNotFoundException $ex)
        {
            Log::warning($ex);
            return $this->error404($ex->getMessage());
        }
        catch(AuthzException $ex){
            Log::warning($ex);
            return $this->error403($ex->getMessage());
        }
        catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    public function reorderSelectionList($summit_id, $track_id, $list_id){
        try {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $data = Request::json();
            $payload = $data->all();
            // Creates a Validator instance and validates the data.
            $validation = Validator::make($payload,[
                'hash' => 'sometimes|nullable|string',
                'collection' => sprintf('required|string|in:%s,%s', SummitSelectedPresentation::CollectionMaybe, SummitSelectedPresentation::CollectionSelected),
                'presentations' => 'required|int_array',
            ]);

            if ($validation->fails()) {
                $messages = $validation->messages()->toArray();
                return $this->error412
                (
                    $messages
                );
            }

            $selection_list = $this->service->reorderList($summit, intval($track_id), intval($list_id), $payload);

            return $this->updated(SerializerRegistry::getInstance()->getSerializer($selection_list)->serialize(Request::input('expand', '')));
        }
        catch (ValidationException $ex) {
            Log::warning($ex);
            return $this->error412($ex->getMessages());
        }
        catch(EntityNotFoundException $ex)
        {
            Log::warning($ex);
            return $this->error404($ex->getMessage());
        }
        catch (AuthzException $ex){
            Log::warning($ex);
            return $this->error403($ex->getMessage());
        }
        catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    /**
     * @param $summit_id
     * @param $track_id
     * @param $collection
     * @param $presentation_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function assignPresentationToMyIndividualList($summit_id, $track_id, $collection, $presentation_id){
        try {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $selection_list = $this->service->assignPresentationToMyIndividualList($summit, intval($track_id), trim($collection), intval($presentation_id));

            return $this->created(SerializerRegistry::getInstance()->getSerializer($selection_list)->serialize(Request::input('expand', '')));
        }
        catch (ValidationException $ex) {
            Log::warning($ex);
            return $this->error412($ex->getMessages());
        }
        catch(EntityNotFoundException $ex)
        {
            Log::warning($ex);
            return $this->error404($ex->getMessage());
        }
        catch(AuthzException $ex){
            Log::warning($ex);
            return $this->error403($ex->getMessage());
        }
        catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    /**
     * @param $summit_id
     * @param $track_id
     * @param $collection
     * @param $presentation_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function removePresentationFromMyIndividualList($summit_id, $track_id, $collection, $presentation_id){
        try {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $selection_list = $this->service->removePresentationFromMyIndividualList($summit, intval($track_id), intval($presentation_id));

            return $this->created(SerializerRegistry::getInstance()->getSerializer($selection_list)->serialize(Request::input('expand', '')));
        }
        catch (ValidationException $ex) {
            Log::warning($ex);
            return $this->error412($ex->getMessages());
        }
        catch(EntityNotFoundException $ex)
        {
            Log::warning($ex);
            return $this->error404($ex->getMessage());
        }
        catch (AuthzException $ex){
            Log::warning($ex);
            return $this->error403($ex->getMessage());
        }
        catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }
}