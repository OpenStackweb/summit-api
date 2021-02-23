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

use App\Http\Exceptions\HTTP403ForbiddenException;
use App\Http\Utils\EpochCellFormatter;
use App\Models\Foundation\Summit\Repositories\ISummitTrackChairRepository;
use App\Services\Model\ITrackChairService;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use models\oauth2\IResourceServerContext;
use models\summit\ISummitRepository;
use models\summit\Summit;
use models\utils\IEntity;
use ModelSerializers\SerializerRegistry;
use utils\Filter;
use utils\FilterElement;
use Exception;
/**
 * Class OAuth2SummitTrackChairsApiController
 * @package App\Http\Controllers
 */
final class OAuth2SummitTrackChairsApiController
    extends OAuth2ProtectedController
{
    /**
     * @var ISummitRepository
     */
    private $summit_repository;


    /**
     * @var ITrackChairService
     */
    private $service;

    /**
     * OAuth2SummitTrackChairsApiController constructor.
     * @param ISummitRepository $summit_repository
     * @param ISummitTrackChairRepository $repository
     * @param ITrackChairService $service
     * @param IResourceServerContext $resource_server_context
     */
    public function __construct
    (
        ISummitRepository $summit_repository,
        ISummitTrackChairRepository $repository,
        ITrackChairService $service,
        IResourceServerContext $resource_server_context
    )
    {
        parent::__construct($resource_server_context);
        $this->repository = $repository;
        $this->service = $service;
        $this->summit_repository = $summit_repository;
    }

    use ParametrizedGetAll;

    /**
     * @param $summit_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function getAllBySummit($summit_id){
        $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->getResourceServerContext())->find($summit_id);
        if (is_null($summit)) return $this->error404();

        return $this->_getAll(
            function () {
                return [
                    'member_first_name' => ['=@', '=='],
                    'member_last_name' => ['=@', '=='],
                    'member_full_name' => ['=@', '=='],
                    'member_email' => ['=@', '=='],
                    'member_id' => ['=='],
                    'track_id' => ['=='],
                    'summit_id' => ['=='],
                ];
            },
            function () {
                return [
                    'member_first_name' => 'sometimes|string',
                    'member_last_name' => 'sometimes|string',
                    'member_full_name' => 'sometimes|string',
                    'member_email' => 'sometimes|string',
                    'member_id' => 'sometimes|integer',
                    'track_id' => 'sometimes|integer',
                    'summit_id' => 'sometimes|integer',
                ];
            },
            function () {
                return [
                    'member_first_name',
                    'member_last_name',
                    'member_email',
                    'member_full_name',
                    'id',
                    'track_id',
                ];
            },
            function ($filter) use ($summit) {
                if ($filter instanceof Filter) {
                    $filter->addFilterCondition(FilterElement::makeEqual('summit_id', $summit->getId()));
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
     * @return mixed
     */
    public function getAllBySummitCSV($summit_id){

        $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->getResourceServerContext())->find($summit_id);
        if (is_null($summit)) return $this->error404();

        $current_member = $this->resource_server_context->getCurrentUser();
        if (is_null($current_member)) return $this->error403();

        return $this->_getAllCSV(
            function () {
                return [
                    'member_first_name' => ['=@', '=='],
                    'member_last_name' => ['=@', '=='],
                    'member_full_name' => ['=@', '=='],
                    'member_email' => ['=@', '=='],
                    'member_id' => ['=='],
                    'track_id' => ['=='],
                    'summit_id' => ['=='],
                ];
            },
            function () {
                return [
                    'member_first_name' => 'sometimes|string',
                    'member_last_name' => 'sometimes|string',
                    'member_full_name' => 'sometimes|string',
                    'member_email' => 'sometimes|string',
                    'member_id' => 'sometimes|integer',
                    'track_id' => 'sometimes|integer',
                    'summit_id' => 'sometimes|integer',
                ];
            },
            function () {
                return [
                    'member_first_name',
                    'member_last_name',
                    'member_email',
                    'member_full_name',
                    'id',
                    'track_id',
                ];
            },
            function ($filter) use ($summit) {
                if ($filter instanceof Filter) {
                    $filter->addFilterCondition(FilterElement::makeEqual('summit_id', $summit->getId()));
                }
                return $filter;
            },
            function(){
                return SerializerRegistry::SerializerType_CSV;
            },
            function(){
                return [
                    'created' => new EpochCellFormatter(),
                    'last_edited' => new EpochCellFormatter(),
                ];
            },
            function(){

                $allowed_columns = [
                    'created',
                    'last_edited',
                    'member_first_name',
                    'member_last_name',
                    'member_email',
                    'member_id',
                    'categories',
                    'summit_id'
                ];

                $columns_param = Input::get("columns", "");
                $columns = [];
                if(!empty($columns_param))
                    $columns  = explode(',', $columns_param);
                $diff     = array_diff($columns, $allowed_columns);
                if(count($diff) > 0){
                    throw new ValidationException(sprintf("columns %s are not allowed!", implode(",", $diff)));
                }
                if(empty($columns))
                    $columns = $allowed_columns;
                return $columns;
            },
            'track-chairs-'
        );
    }

    use AddSummitChildElement;

    /**
     * @param array $payload
     * @return array
     */
    function getAddValidationRules(array $payload): array
    {
        return [
            'member_id'   => 'required|int',
            'categories' => 'required|int_array',
        ];
    }

    /**
     * @param Summit $summit
     * @param array $payload
     * @return IEntity
     */
    protected function addChild(Summit $summit, array $payload): IEntity
    {
        return $this->service->addTrackChair($summit, $payload);
    }

    /**
     * @inheritDoc
     */
    protected function getSummitRepository(): ISummitRepository
    {
        return $this->summit_repository;
    }

    use UpdateSummitChildElement;

    function getUpdateValidationRules(array $payload): array{
        return [
            'categories' => 'required|int_array',
        ];
    }

    /**
     * @param Summit $summit
     * @param int $child_id
     * @param array $payload
     * @return IEntity
     */
    protected function updateChild(Summit $summit,int $child_id, array $payload):IEntity{
        return $this->service->updateTrackChair($summit, $child_id, $payload);
    }

    use DeleteSummitChildElement;

    /**
     * @param Summit $summit
     * @param $child_id
     * @return void
     */
    protected function deleteChild(Summit $summit, $child_id):void{
        $this->service->deleteTrackChair($summit, $child_id);
    }

    use GetSummitChildElementById;

    /**
     * @param Summit $summit
     * @param $child_id
     * @return IEntity|null
     */
    protected function getChildFromSummit(Summit $summit, $child_id):?IEntity{
        return $summit->getTrackChair(intval($child_id));
    }

    /**
     * @param $summit_id
     * @param $track_chair_id
     * @param $track_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function addTrack2TrackChair($summit_id, $track_chair_id, $track_id){
        try{
            $summit = SummitFinderStrategyFactory::build($this->getSummitRepository(), $this->getResourceServerContext())->find($summit_id);
            if (is_null($summit)) return $this->error404();
            $track_chair = $this->service->addTrack2TrackChair($summit, intval($track_chair_id), intval($track_id));
            return $this->updated(SerializerRegistry::getInstance()->getSerializer($track_chair)->serialize(Request::input('expand', '')));
        }
        catch (ValidationException $ex) {
            Log::warning($ex);
            return $this->error412($ex->getMessages());
        }
        catch (EntityNotFoundException $ex) {
            Log::warning($ex);
            return $this->error404(array('message' => $ex->getMessage()));
        }
        catch (\HTTP401UnauthorizedException $ex) {
            Log::warning($ex);
            return $this->error401();
        }
        catch(HTTP403ForbiddenException $ex){
            Log::warning($ex);
            return $this->error403();
        }
        catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    /**
     * @param $summit_id
     * @param $track_chair_id
     * @param $track_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function removeFromTrackChair($summit_id, $track_chair_id, $track_id){
        try{
            $summit = SummitFinderStrategyFactory::build($this->getSummitRepository(), $this->getResourceServerContext())->find($summit_id);
            if (is_null($summit)) return $this->error404();
            $track_chair = $this->service->removeFromTrackChair($summit, intval($track_chair_id), intval($track_id));
            return $this->updated(SerializerRegistry::getInstance()->getSerializer($track_chair)->serialize(Request::input('expand', '')));
        }
        catch (ValidationException $ex) {
            Log::warning($ex);
            return $this->error412($ex->getMessages());
        }
        catch (EntityNotFoundException $ex) {
            Log::warning($ex);
            return $this->error404(array('message' => $ex->getMessage()));
        }
        catch (\HTTP401UnauthorizedException $ex) {
            Log::warning($ex);
            return $this->error401();
        }
        catch(HTTP403ForbiddenException $ex){
            Log::warning($ex);
            return $this->error403();
        }
        catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

}