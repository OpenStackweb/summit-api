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
use App\Http\Utils\EpochCellFormatter;
use App\Models\Foundation\Summit\Events\Presentations\PresentationCategoryGroupConstants;
use App\Models\Foundation\Summit\Repositories\IPresentationCategoryGroupRepository;
use App\Services\Model\IPresentationCategoryGroupService;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use libs\utils\PaginationValidationRules;
use models\exceptions\ValidationException;
use models\oauth2\IResourceServerContext;
use models\summit\ISummitRepository;
use models\exceptions\EntityNotFoundException;
use ModelSerializers\SerializerRegistry;
use utils\Filter;
use utils\FilterParser;
use utils\OrderParser;
use utils\PagingInfo;
use Exception;
/**
 * Class OAuth2PresentationCategoryGroupController
 * @package App\Http\Controllers
 */
final class OAuth2PresentationCategoryGroupController
    extends OAuth2ProtectedController
{

    /**
     * @var ISummitRepository
     */
    private $summit_repository;

    /**
     * @var IPresentationCategoryGroupService
     */
    private $presentation_category_group_service;

    /**
     * OAuth2SummitsTicketTypesApiController constructor.
     * @param IPresentationCategoryGroupRepository $repository
     * @param ISummitRepository $summit_repository
     * @param IPresentationCategoryGroupService $presentation_category_group_service
     * @param IResourceServerContext $resource_server_context
     */
    public function __construct
    (
        IPresentationCategoryGroupRepository $repository,
        ISummitRepository $summit_repository,
        IPresentationCategoryGroupService $presentation_category_group_service,
        IResourceServerContext $resource_server_context
    )
    {
        parent::__construct($resource_server_context);
        $this->repository                           = $repository;
        $this->summit_repository                    = $summit_repository;
        $this->presentation_category_group_service  = $presentation_category_group_service;
    }
    /**
     * @param $summit_id
     * @return mixed
     */
    public function getAllBySummit($summit_id){
        $values = Request::all();
        $rules  = PaginationValidationRules::get();

        try {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $validation = Validator::make($values, $rules);

            if ($validation->fails()) {
                $ex = new ValidationException();
                throw $ex->setMessages($validation->messages()->toArray());
            }

            // default values
            $page     = 1;
            $per_page = PaginationValidationRules::PerPageMin;

            if (Request::has('page')) {
                $page     = intval(Request::input('page'));
                $per_page = intval(Request::input('per_page'));
            }

            $filter = null;

            if (Request::has('filter')) {
                $filter = FilterParser::parse(Request::input('filter'), [
                    'name'           => ['=@', '=='],
                    'description'    => ['=@', '=='],
                    'slug'           => ['=@', '=='],
                    'track_title'    => ['=@', '=='],
                    'track_code'     => ['=@', '=='],
                    'group_title'    => ['=@', '=='],
                    'group_code'     => ['=@', '=='],
                    'voting_visible' => ['=='],
                    'chair_visible'  => ['=='],
                    'class_name'     => ['==']
                ]);
            }

            if(is_null($filter)) $filter = new Filter();

            $filter->validate([
                'name'           => 'sometimes|string',
                'description'    => 'sometimes|string',
                'slug'           => 'sometimes|string',
                'track_title'    => 'sometimes|string',
                'track_code'     => 'sometimes|string',
                'group_title'    => 'sometimes|string',
                'group_code'     => 'sometimes|string',
                'voting_visible' => 'sometimes|boolean',
                'chair_visible'  => 'sometimes|boolean',
                'class_name'     =>  sprintf('sometimes|in:%s', implode(',',PresentationCategoryGroupConstants::$valid_class_names)),
            ],
                [
                    'class_name.in' =>  sprintf
                    (
                        ":attribute has an invalid value ( valid values are %s )",
                        implode(", ", PresentationCategoryGroupConstants::$valid_class_names)
                    ),
                ]);

            $order = null;

            if (Request::has('order'))
            {
                $order = OrderParser::parse(Request::input('order'), [
                    'id',
                    'name',
                    'slug'
                ]);
            }

            $data = $this->repository->getBySummit($summit, new PagingInfo($page, $per_page), $filter, $order);

            return $this->ok
            (
                $data->toArray
                (
                    Request::input('expand', ''),
                    [],
                    [],
                    []
                )
            );
        }
        catch (ValidationException $ex1)
        {
            Log::warning($ex1);
            return $this->error412([$ex1->getMessage()]);
        }
        catch (EntityNotFoundException $ex2)
        {
            Log::warning($ex2);
            return $this->error404(['message' => $ex2->getMessage()]);
        }
        catch(\HTTP401UnauthorizedException $ex3)
        {
            Log::warning($ex3);
            return $this->error401();
        }
        catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    /**
     * @param $summit_id
     * @return mixed
     */
    public function getAllBySummitCSV($summit_id){
        try {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            // default values
            $page     = 1;
            $per_page = PHP_INT_MAX;

            if (Request::has('page')) {
                $page     = intval(Request::input('page'));
                $per_page = intval(Request::input('per_page'));
            }

            $filter = null;

            if (Request::has('filter')) {
                $filter = FilterParser::parse(Request::input('filter'), [
                    'name'           => ['=@', '=='],
                    'description'    => ['=@', '=='],
                    'slug'           => ['=@', '=='],
                    'track_title'    => ['=@', '=='],
                    'track_code'     => ['=@', '=='],
                    'group_title'    => ['=@', '=='],
                    'group_code'     => ['=@', '=='],
                    'voting_visible' => ['=='],
                    'chair_visible'  => ['=='],
                    'class_name'     => ['==']
                ]);
            }

            if(is_null($filter)) $filter = new Filter();

            $filter->validate([
                'name'           => 'sometimes|string',
                'description'    => 'sometimes|string',
                'slug'           => 'sometimes|string',
                'track_title'    => 'sometimes|string',
                'track_code'     => 'sometimes|string',
                'group_title'    => 'sometimes|string',
                'group_code'     => 'sometimes|string',
                'voting_visible' => 'sometimes|boolean',
                'chair_visible'  => 'sometimes|boolean',
                'class_name'     =>  sprintf('sometimes|in:%s', implode(',',PresentationCategoryGroupConstants::$valid_class_names)),
            ],
                [
                    'class_name.in' =>  sprintf
                    (
                        ":attribute has an invalid value ( valid values are %s )",
                        implode(", ", PresentationCategoryGroupConstants::$valid_class_names)
                    ),
                ]);

            $order = null;

            if (Request::has('order'))
            {
                $order = OrderParser::parse(Request::input('order'), [
                    'id',
                    'name',
                    'slug'
                ]);
            }

            $data = $this->repository->getBySummit($summit, new PagingInfo($page, $per_page), $filter, $order);

            $filename = "presentation-category-groups-" . date('Ymd');
            $list     =  $data->toArray();
            return $this->export
            (
                'csv',
                $filename,
                $list['data'],
                [
                    'created'     => new EpochCellFormatter,
                    'last_edited' => new EpochCellFormatter,
                ]
            );
        }
        catch (ValidationException $ex1)
        {
            Log::warning($ex1);
            return $this->error412(array( $ex1->getMessage()));
        }
        catch (EntityNotFoundException $ex2)
        {
            Log::warning($ex2);
            return $this->error404(array('message' => $ex2->getMessage()));
        }
        catch(\HTTP401UnauthorizedException $ex3)
        {
            Log::warning($ex3);
            return $this->error401();
        }
        catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    /**
     * @param $summit_id
     * @param $track_group_id
     * @return mixed
     */
    public function getTrackGroupBySummit($summit_id, $track_group_id){
        try {
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);

            if (is_null($summit)) return $this->error404();
            $track_group = $summit->getCategoryGroupById($track_group_id);

            if(is_null($track_group))
                return $this->error404();

            return $this->ok(SerializerRegistry::getInstance()->getSerializer($track_group)->serialize( Request::input('expand', '')));
        } catch (ValidationException $ex1) {
            Log::warning($ex1);
            return $this->error412([$ex1->getMessage()]);
        } catch (EntityNotFoundException $ex2) {
            Log::warning($ex2);
            return $this->error404(['message' => $ex2->getMessage()]);
        } catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    /**
     * @param $summit_id
     * @param $track_group_id
     * @return mixed
     */
    public function updateTrackGroupBySummit($summit_id, $track_group_id){
        try {

            if(!Request::isJson()) return $this->error400();
            $data    = Request::json();
            $payload = $data->all();
            $summit  = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $rules = PresentationCategoryGroupValidationRulesFactory::build($payload, true);
            // Creates a Validator instance and validates the data.
            $validation = Validator::make($payload, $rules);

            if ($validation->fails()) {
                $messages = $validation->messages()->toArray();

                return $this->error412
                (
                    $messages
                );
            }

            $track_group = $this->presentation_category_group_service->updateTrackGroup($summit, $track_group_id, $payload);

            return $this->updated(SerializerRegistry::getInstance()->getSerializer($track_group)->serialize());
        }
        catch (ValidationException $ex1) {
            Log::warning($ex1);
            return $this->error412([$ex1->getMessage()]);
        }
        catch(EntityNotFoundException $ex2)
        {
            Log::warning($ex2);
            return $this->error404(['message'=> $ex2->getMessage()]);
        }
        catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    /**
     * @param $summit_id
     * @param $track_group_id
     * @return mixed
     */
    public function deleteTrackGroupBySummit($summit_id, $track_group_id){
        try {

            $summit  = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $this->presentation_category_group_service->deleteTrackGroup($summit, $track_group_id);

            return $this->deleted();
        }
        catch (ValidationException $ex1) {
            Log::warning($ex1);
            return $this->error412([$ex1->getMessage()]);
        }
        catch(EntityNotFoundException $ex2)
        {
            Log::warning($ex2);
            return $this->error404(['message'=> $ex2->getMessage()]);
        }
        catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    /**
     * @param $summit_id
     * @return mixed
     */
    public function addTrackGroupBySummit($summit_id){
        try {

            if(!Request::isJson()) return $this->error400();
            $data    = Request::json();
            $payload = $data->all();
            $summit  = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $rules = PresentationCategoryGroupValidationRulesFactory::build($payload);
            // Creates a Validator instance and validates the data.
            $validation = Validator::make($payload, $rules);

            if ($validation->fails()) {
                $messages = $validation->messages()->toArray();

                return $this->error412
                (
                    $messages
                );
            }

            $track_group = $this->presentation_category_group_service->addTrackGroup($summit, $payload);

            return $this->created(SerializerRegistry::getInstance()->getSerializer($track_group)->serialize());
        }
        catch (ValidationException $ex1) {
            Log::warning($ex1);
            return $this->error412([$ex1->getMessage()]);
        }
        catch(EntityNotFoundException $ex2)
        {
            Log::warning($ex2);
            return $this->error404(['message'=> $ex2->getMessage()]);
        }
        catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    /**
     * @param $summit_id
     * @param $track_group_id
     * @param $track_id
     * @return mixed
     */
    public function associateTrack2TrackGroup($summit_id, $track_group_id, $track_id){
        try {

            $summit  = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $this->presentation_category_group_service->associateTrack2TrackGroup($summit, $track_group_id, $track_id);

            return $this->updated();
        }
        catch (ValidationException $ex1) {
            Log::warning($ex1);
            return $this->error412([$ex1->getMessage()]);
        }
        catch(EntityNotFoundException $ex2)
        {
            Log::warning($ex2);
            return $this->error404(['message'=> $ex2->getMessage()]);
        }
        catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    /**
     * @param $summit_id
     * @param $track_group_id
     * @param $track_id
     * @return mixed
     */
    public function disassociateTrack2TrackGroup($summit_id, $track_group_id, $track_id){
        try {

            $summit  = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $this->presentation_category_group_service->disassociateTrack2TrackGroup($summit, $track_group_id, $track_id);

            return $this->deleted();
        }
        catch (ValidationException $ex1) {
            Log::warning($ex1);
            return $this->error412([$ex1->getMessage()]);
        }
        catch(EntityNotFoundException $ex2)
        {
            Log::warning($ex2);
            return $this->error404(['message'=> $ex2->getMessage()]);
        }
        catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    /**
     * @param $summit_id
     * @param $track_group_id
     * @param $group_id
     * @return mixed
     */
    public function associateAllowedGroup2TrackGroup($summit_id, $track_group_id, $group_id){
        try {

            $summit  = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $this->presentation_category_group_service->associateAllowedGroup2TrackGroup($summit, $track_group_id, $group_id);

            return $this->updated();
        }
        catch (ValidationException $ex1) {
            Log::warning($ex1);
            return $this->error412([$ex1->getMessage()]);
        }
        catch(EntityNotFoundException $ex2)
        {
            Log::warning($ex2);
            return $this->error404(['message'=> $ex2->getMessage()]);
        }
        catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    /**
     * @param $summit_id
     * @param $track_group_id
     * @param $group_id
     * @return mixed
     */
    public function disassociateAllowedGroup2TrackGroup($summit_id, $track_group_id, $group_id){
        try {

            $summit  = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $this->presentation_category_group_service->disassociateAllowedGroup2TrackGroup($summit, $track_group_id, $group_id);

            return $this->deleted();
        }
        catch (ValidationException $ex1) {
            Log::warning($ex1);
            return $this->error412([$ex1->getMessage()]);
        }
        catch(EntityNotFoundException $ex2)
        {
            Log::warning($ex2);
            return $this->error404(['message'=> $ex2->getMessage()]);
        }
        catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    /**
     * @param $summit_id
     * @return mixed
     */
    public function getMetadata($summit_id){
        $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
        if (is_null($summit)) return $this->error404();

        return $this->ok
        (
            $this->repository->getMetadata($summit)
        );
    }
}