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

use App\Http\Utils\BooleanCellFormatter;
use App\Http\Utils\EpochCellFormatter;
use App\Models\Foundation\Summit\Repositories\ISummitTrackRepository;
use App\ModelSerializers\SerializerUtils;
use App\Rules\Boolean;
use App\Services\Model\ISummitTrackService;
use Exception;
use Illuminate\Http\Request as LaravelRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Validator;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use models\oauth2\IResourceServerContext;
use models\summit\ISummitRepository;
use ModelSerializers\SerializerRegistry;
use utils\Filter;
use utils\FilterParser;
use utils\OrderParser;
use utils\PagingInfo;
use utils\PagingResponse;

/**
 * Class OAuth2SummitTracksApiController
 * @package App\Http\Controllers
 */
final class OAuth2SummitTracksApiController extends OAuth2ProtectedController
{
    /**
     * @var ISummitRepository
     */
    private $summit_repository;

    /**
     * @var ISummitTrackService
     */
    private $track_service;

    /**
     * OAuth2SummitsEventTypesApiController constructor.
     * @param ISummitTrackRepository $repository
     * @param ISummitRepository $summit_repository
     * @param ISummitTrackService $track_service
     * @param IResourceServerContext $resource_server_context
     */
    public function __construct
    (
        ISummitTrackRepository $repository,
        ISummitRepository      $summit_repository,
        ISummitTrackService    $track_service,
        IResourceServerContext $resource_server_context
    )
    {
        parent::__construct($resource_server_context);
        $this->repository = $repository;
        $this->summit_repository = $summit_repository;
        $this->track_service = $track_service;
    }

    use ParametrizedGetAll;

    use RequestProcessor;

    use GetAndValidateJsonPayload;

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
                    'name' => ['=@', '==', '@@'],
                    'description' => ['=@', '==', '@@'],
                    'code' => ['=@', '==', '@@'],
                    'group_name' => ['=@', '==', '@@'],
                    'voting_visible' => ['=='],
                    'chair_visible' => ['=='],
                    'has_parent'  => ['=='],
                    'has_subtracks' => ['=='],
                    'has_proposed_schedule_allowed_locations' => ['=='],
                ];
            },
            function () {
                return [
                    'name' => 'sometimes|string',
                    'description' => 'sometimes|string',
                    'code' => 'sometimes|string',
                    'group_name' => 'sometimes|string',
                    'voting_visible' =>  ['sometimes', new Boolean],
                    'chair_visible' =>  ['sometimes', new Boolean],
                    'has_parent'  => ['sometimes', new Boolean],
                    'has_subtracks' =>  ['sometimes', new Boolean],
                    'has_proposed_schedule_allowed_locations'=> 'sometimes|required|string|in:true,false',
                ];
            },
            function () {
                return [
                    'id',
                    'code',
                    'name',
                    'order',
                ];
            },
            function ($filter) {
                return $filter;
            },
            function () {
                return SerializerRegistry::SerializerType_Public;
            },
            null,
            null,
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

    /**
     * @param $summit_id
     * @return mixed
     */
    public function getAllBySummitCSV($summit_id)
    {
        $values = Request::all();
        $rules = [];

        try {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $validation = Validator::make($values, $rules);

            if ($validation->fails()) {
                $ex = new ValidationException();
                throw $ex->setMessages($validation->messages()->toArray());
            }

            // default values
            $page = 1;
            $per_page = PHP_INT_MAX;

            if (Request::has('page')) {
                $page = intval(Request::input('page'));
                $per_page = intval(Request::input('per_page'));
            }

            $filter = null;

            if (Request::has('filter')) {
                $filter = FilterParser::parse(Request::input('filter'), [
                    'title' => ['=@', '==', '@@'],
                    'description' => ['=@', '==', '@@'],
                    'code' => ['=@', '==', '@@'],
                    'group_name' => ['=@', '==', '@@'],
                    'voting_visible' => ['=='],
                    'chair_visible' => ['=='],
                    'has_proposed_schedule_allowed_locations' => ['=='],
                    'has_parent'  => ['=='],
                    'has_subtracks' => ['=='],
                ]);
            }

            if (is_null($filter)) $filter = new Filter();

            $filter->validate([
                'name' => 'sometimes|string',
                'description' => 'sometimes|string',
                'code' => 'sometimes|string',
                'group_name' => 'sometimes|string',
                'voting_visible' =>  ['sometimes', new Boolean],
                'chair_visible' =>  ['sometimes', new Boolean],
                'has_parent'  => ['sometimes', new Boolean],
                'has_subtracks' =>  ['sometimes', new Boolean],
                'has_proposed_schedule_allowed_locations'=> 'sometimes|boolean',
            ]);

            $order = null;

            if (Request::has('order')) {
                $order = OrderParser::parse(Request::input('order'), [

                    'id',
                    'code',
                    'title',
                    'order',
                ]);
            }

            $data = $this->repository->getBySummit($summit, new PagingInfo($page, $per_page), $filter, $order);

            $filename = "tracks-" . date('Ymd');
            $list = $data->toArray();
            return $this->export
            (
                'csv',
                $filename,
                $list['data'],
                [
                    'created' => new EpochCellFormatter,
                    'last_edited' => new EpochCellFormatter,
                    'is_default' => new BooleanCellFormatter,
                    'use_sponsors' => new BooleanCellFormatter,
                    'are_sponsors_mandatory' => new BooleanCellFormatter,
                    'allows_attachment' => new BooleanCellFormatter,
                    'use_speakers' => new BooleanCellFormatter,
                    'are_speakers_mandatory' => new BooleanCellFormatter,
                    'use_moderator' => new BooleanCellFormatter,
                    'is_moderator_mandatory' => new BooleanCellFormatter,
                    'should_be_available_on_cfp' => new BooleanCellFormatter,
                ]
            );
        } catch (ValidationException $ex1) {
            Log::warning($ex1);
            return $this->error412(array($ex1->getMessage()));
        } catch (EntityNotFoundException $ex2) {
            Log::warning($ex2);
            return $this->error404(array('message' => $ex2->getMessage()));
        } catch (\HTTP401UnauthorizedException $ex3) {
            Log::warning($ex3);
            return $this->error401();
        } catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    /**
     * @param $summit_id
     * @param $track_id
     * @return mixed
     */
    public function getTrackBySummit($summit_id, $track_id)
    {
        try {
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();
            $track = $summit->getPresentationCategory($track_id);
            if (is_null($track))
                return $this->error404();
            return $this->ok(SerializerRegistry::getInstance()->getSerializer($track)->serialize
            (
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));
        } catch (ValidationException $ex1) {
            Log::warning($ex1);
            return $this->error412(array($ex1->getMessage()));
        } catch (EntityNotFoundException $ex2) {
            Log::warning($ex2);
            return $this->error404(array('message' => $ex2->getMessage()));
        } catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    /**
     * @param $summit_id
     * @param $track_id
     * @return mixed
     */
    public function getTrackExtraQuestionsBySummit($summit_id, $track_id)
    {
        try {
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();
            $track = $summit->getPresentationCategory($track_id);
            if (is_null($track))
                return $this->error404();
            $extra_questions = $track->getExtraQuestions()->toArray();
            $response = new PagingResponse(
                count($extra_questions),
                count($extra_questions),
                1,
                1,
                $extra_questions
            );

            return $this->ok($response->toArray());

        } catch (ValidationException $ex1) {
            Log::warning($ex1);
            return $this->error412(array($ex1->getMessage()));
        } catch (EntityNotFoundException $ex2) {
            Log::warning($ex2);
            return $this->error404(array('message' => $ex2->getMessage()));
        } catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    /**
     * @param $summit_id
     * @param $track_id
     * @param $question_id
     * @return mixed
     */
    public function addTrackExtraQuestion($summit_id, $track_id, $question_id)
    {
        try {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $this->track_service->addTrackExtraQuestion($track_id, $question_id);

            return $this->updated();

        } catch (ValidationException $ex1) {
            Log::warning($ex1);
            return $this->error412(array($ex1->getMessage()));
        } catch (EntityNotFoundException $ex2) {
            Log::warning($ex2);
            return $this->error404(array('message' => $ex2->getMessage()));
        } catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    /**
     * @param $summit_id
     * @param $track_id
     * @param $question_id
     * @return mixed
     */
    public function removeTrackExtraQuestion($summit_id, $track_id, $question_id)
    {
        try {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $this->track_service->removeTrackExtraQuestion($track_id, $question_id);

            return $this->deleted();

        } catch (ValidationException $ex1) {
            Log::warning($ex1);
            return $this->error412(array($ex1->getMessage()));
        } catch (EntityNotFoundException $ex2) {
            Log::warning($ex2);
            return $this->error404(array('message' => $ex2->getMessage()));
        } catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    public function getTrackAllowedTagsBySummit($summit_id, $track_id)
    {
        try {
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();
            $track = $summit->getPresentationCategory($track_id);
            if (is_null($track))
                return $this->error404();
            $allowed_tags = $track->getAllowedTags()->toArray();

            $response = new PagingResponse(
                count($allowed_tags),
                count($allowed_tags),
                1,
                1,
                $allowed_tags
            );
            $res = $response->toArray();
            $i = 0;
            foreach ($res["data"] as $allowed_tag) {
                $track_tag_group = $summit->getTrackTagGroupForTagId($allowed_tag['id']);
                if (is_null($track_tag_group)) continue;
                $res["data"][$i]['track_tag_group'] = SerializerRegistry::getInstance()->getSerializer($track_tag_group)->serialize(null, [], ['none']);
                $i++;
            }
            return $this->ok($res);

        } catch (ValidationException $ex1) {
            Log::warning($ex1);
            return $this->error412(array($ex1->getMessage()));
        } catch (EntityNotFoundException $ex2) {
            Log::warning($ex2);
            return $this->error404(array('message' => $ex2->getMessage()));
        } catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    /**
     * @param $summit_id
     * @return mixed
     */
    public function addTrackBySummit($summit_id)
    {
        try {
            if (!Request::isJson()) return $this->error400();
            $data = Request::json();

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            // Creates a Validator instance and validates the data.
            $validation = Validator::make($data->all(), SummitTrackValidationRulesFactory::buildForAdd());

            if ($validation->fails()) {
                $messages = $validation->messages()->toArray();

                return $this->error412
                (
                    $messages
                );
            }

            $track = $this->track_service->addTrack($summit, $data->all());

            return $this->created(SerializerRegistry::getInstance()->getSerializer($track)->serialize(
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));
        } catch (ValidationException $ex1) {
            Log::warning($ex1);
            return $this->error412(array($ex1->getMessage()));
        } catch (EntityNotFoundException $ex2) {
            Log::warning($ex2);
            return $this->error404(array('message' => $ex2->getMessage()));
        } catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    /**
     * @param $summit_id
     * @param $to_summit_id
     * @return mixed
     */
    public function copyTracksToSummit($summit_id, $to_summit_id)
    {
        try {

            $from_summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($from_summit)) return $this->error404();

            $to_summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($to_summit_id);
            if (is_null($to_summit)) return $this->error404();

            $tracks = $this->track_service->copyTracks($from_summit, $to_summit);

            $response = new PagingResponse
            (
                count($tracks),
                count($tracks),
                1,
                1,
                $tracks
            );

            return $this->created($response->toArray());
        } catch (ValidationException $ex1) {
            Log::warning($ex1);
            return $this->error412(array($ex1->getMessage()));
        } catch (EntityNotFoundException $ex2) {
            Log::warning($ex2);
            return $this->error404(array('message' => $ex2->getMessage()));
        } catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    /**
     * @param $summit_id
     * @param $track_id
     * @return mixed
     */
    public function updateTrackBySummit($summit_id, $track_id)
    {
        try {
            if (!Request::isJson()) return $this->error400();
            $data = Request::json();

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            // Creates a Validator instance and validates the data.
            $validation = Validator::make($data->all(), SummitTrackValidationRulesFactory::buildForUpdate());

            if ($validation->fails()) {
                $messages = $validation->messages()->toArray();

                return $this->error412
                (
                    $messages
                );
            }

            $track = $this->track_service->updateTrack($summit, $track_id, $data->all());

            return $this->updated(SerializerRegistry::getInstance()->getSerializer($track)->serialize(
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));
        } catch (ValidationException $ex1) {
            Log::warning($ex1);
            return $this->error412(array($ex1->getMessage()));
        } catch (EntityNotFoundException $ex2) {
            Log::warning($ex2);
            return $this->error404(array('message' => $ex2->getMessage()));
        } catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    /**
     * @param $summit_id
     * @param $track_id
     * @return mixed
     */
    public function deleteTrackBySummit($summit_id, $track_id)
    {
        try {
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $this->track_service->deleteTrack($summit, $track_id);

            return $this->deleted();
        } catch (ValidationException $ex1) {
            Log::warning($ex1);
            return $this->error412(array($ex1->getMessage()));
        } catch (EntityNotFoundException $ex2) {
            Log::warning($ex2);
            return $this->error404(array('message' => $ex2->getMessage()));
        } catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    public function addTrackIcon(LaravelRequest $request, $summit_id, $track_id)
    {
        try {
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $file = $request->file('file');
            if (is_null($file)) {
                return $this->error412(array('file param not set!'));
            }

            $image = $this->track_service->addTrackIcon($summit, $track_id, $file);

            return $this->created(SerializerRegistry::getInstance()->getSerializer($image)->serialize(
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));

        } catch (ValidationException $ex1) {
            Log::warning($ex1);
            return $this->error412(array($ex1->getMessage()));
        } catch (EntityNotFoundException $ex2) {
            Log::warning($ex2);
            return $this->error404(array('message' => $ex2->getMessage()));
        } catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    public function deleteTrackIcon($summit_id, $track_id)
    {
        try {
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();
            $this->track_service->removeTrackIcon($summit, $track_id);
            return $this->deleted();
        } catch (ValidationException $ex1) {
            Log::warning($ex1);
            return $this->error412(array($ex1->getMessage()));
        } catch (EntityNotFoundException $ex2) {
            Log::warning($ex2);
            return $this->error404(array('message' => $ex2->getMessage()));
        } catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    public function addSubTrack($summit_id, $track_id, $child_track_id)
    {
        return $this->processRequest(function () use ($summit_id, $track_id, $child_track_id) {
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find(intval($summit_id));
            if (is_null($summit)) return $this->error404();

            $payload = $this->getJsonPayload([
                'order' => 'sometimes|integer|min:1'
            ], true);

            $track = $this->track_service->addSubTrack($summit, intval($track_id), intval($child_track_id), $payload);

            return $this->created(SerializerRegistry::getInstance()->getSerializer($track)->serialize
            (
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));
        });
    }

    public function removeSubTrack($summit_id, $track_id, $child_track_id)
    {
        return $this->processRequest(function () use ($summit_id, $track_id, $child_track_id) {
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find(intval($summit_id));
            if (is_null($summit)) return $this->error404();

            $this->track_service->removeSubTrack($summit, intval($track_id), intval($child_track_id));

            return $this->deleted();
        });
    }
}