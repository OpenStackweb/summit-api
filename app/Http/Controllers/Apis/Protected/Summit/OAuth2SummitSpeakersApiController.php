<?php namespace App\Http\Controllers;
/**
 * Copyright 2016 OpenStack Foundation
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
use App\Models\Foundation\Summit\Repositories\ISelectionPlanRepository;
use App\ModelSerializers\SerializerUtils;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Validator;
use libs\utils\HTMLCleaner;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use models\main\IMemberRepository;
use models\oauth2\IResourceServerContext;
use models\summit\IEventFeedbackRepository;
use models\summit\ISpeakerRepository;
use models\summit\ISummitEventRepository;
use models\summit\ISummitRepository;
use models\summit\PresentationSpeaker;
use ModelSerializers\ISerializerTypeSelector;
use ModelSerializers\SerializerRegistry;
use services\model\ISpeakerService;
use services\model\ISummitService;
use utils\Filter;
use utils\FilterElement;
use utils\FilterParser;
use utils\OrderParser;
use utils\PagingInfo;
use Illuminate\Http\Request as LaravelRequest;
use utils\PagingResponse;

/**
 * Class OAuth2SummitSpeakersApiController
 * @package App\Http\Controllers
 */
final class OAuth2SummitSpeakersApiController extends OAuth2ProtectedController
{
    /**
     * @var ISpeakerService
     */
    private $service;

    /**
     * @var ISpeakerRepository
     */
    private $speaker_repository;

    /**
     * @var ISummitEventRepository
     */
    private $event_repository;

    /**
     * @var IEventFeedbackRepository
     */
    private $event_feedback_repository;

    /**
     * @var IMemberRepository
     */
    private $member_repository;

    /**
     * @var ISerializerTypeSelector
     */
    private $serializer_type_selector;

    /**
     * @var ISelectionPlanRepository
     */
    private $selection_plan_repository;

    /**
     * @var ISummitService
     */
    private $summit_service;

    /**
     * OAuth2SummitSpeakersApiController constructor.
     * @param ISummitRepository $summit_repository
     * @param ISummitEventRepository $event_repository
     * @param ISpeakerRepository $speaker_repository
     * @param IEventFeedbackRepository $event_feedback_repository
     * @param IMemberRepository $member_repository
     * @param ISelectionPlanRepository $selection_plan_repository
     * @param ISpeakerService $service
     * @param ISummitService $summit_service
     * @param ISerializerTypeSelector $serializer_type_selector
     * @param IResourceServerContext $resource_server_context
     */
    public function __construct
    (
        ISummitRepository        $summit_repository,
        ISummitEventRepository   $event_repository,
        ISpeakerRepository       $speaker_repository,
        IEventFeedbackRepository $event_feedback_repository,
        IMemberRepository        $member_repository,
        ISelectionPlanRepository $selection_plan_repository,
        ISpeakerService          $service,
        ISummitService           $summit_service,
        ISerializerTypeSelector  $serializer_type_selector,
        IResourceServerContext   $resource_server_context
    )
    {
        parent::__construct($resource_server_context);
        $this->repository = $summit_repository;
        $this->speaker_repository = $speaker_repository;
        $this->event_repository = $event_repository;
        $this->member_repository = $member_repository;
        $this->event_feedback_repository = $event_feedback_repository;
        $this->selection_plan_repository = $selection_plan_repository;
        $this->service = $service;
        $this->summit_service = $summit_service;
        $this->serializer_type_selector = $serializer_type_selector;
    }

    /**
     *  Speakers endpoints
     */

    use ParametrizedGetAll;

    use GetAndValidateJsonPayload;

    use RequestProcessor;

    /**
     * @param $summit_id
     * @return mixed
     */
    public function getSpeakers($summit_id)
    {
        $summit = SummitFinderStrategyFactory::build($this->getRepository(), $this->getResourceServerContext())->find($summit_id);
        if (is_null($summit)) return $this->error404();

        return $this->_getAll(
            function () {
                return [
                    'first_name' => ['=@', '@@', '=='],
                    'last_name' => ['=@', '@@', '=='],
                    'email' => ['=@', '@@', '=='],
                    'id' => ['=='],
                    'full_name' => ['=@', '@@', '=='],
                    'has_accepted_presentations' => ['=='],
                    'has_alternate_presentations' => ['=='],
                    'has_rejected_presentations' => ['=='],
                    'presentations_track_id' => ['=='],
                    'presentations_selection_plan_id' => ['=='],
                    'presentations_type_id' => ['=='],
                    'presentations_title' => ['=@', '@@', '=='],
                    'presentations_abstract' => ['=@', '@@', '=='],
                    'presentations_submitter_full_name' => ['=@', '@@', '=='],
                    'presentations_submitter_email' => ['=@', '@@', '=='],
                ];
            },
            function () {
                return [
                    'first_name' => 'sometimes|string',
                    'last_name' => 'sometimes|string',
                    'email' => 'sometimes|string',
                    'id' => 'sometimes|integer',
                    'full_name' => 'sometimes|string',
                    'has_accepted_presentations' => 'sometimes|required|string|in:true,false',
                    'has_alternate_presentations' => 'sometimes|required|string|in:true,false',
                    'has_rejected_presentations' => 'sometimes|required|string|in:true,false',
                    'presentations_track_id' => 'sometimes|integer',
                    'presentations_selection_plan_id' => 'sometimes|integer',
                    'presentations_type_id' => 'sometimes|integer',
                    'presentations_title' => 'sometimes|string',
                    'presentations_abstract' => 'sometimes|string',
                    'presentations_submitter_full_name' => 'sometimes|string',
                    'presentations_submitter_email' => 'sometimes|string',
                ];
            },
            function () {
                return [
                    'full_name',
                    'first_name',
                    'last_name',
                    'id',
                    'email',
                ];
            },
            function ($filter) use ($summit) {
                return $filter;
            },
            function () {
                $current_member = $this->resource_server_context->getCurrentUser();
                $serializer_type = SerializerRegistry::SerializerType_Public;

                if (!is_null($current_member) && ($current_member->isAdmin() || $current_member->isSummitAdmin())) {
                    $serializer_type = SerializerRegistry::SerializerType_Admin;
                }

                return $serializer_type;
            },
            null,
            null,
            function ($page, $per_page, $filter, $order, $applyExtraFilters) use ($summit) {
                return $this->speaker_repository->getSpeakersBySummit
                (
                    $summit,
                    new PagingInfo($page, $per_page),
                    call_user_func($applyExtraFilters, $filter),
                    $order
                );
            },
            [
                'summit_id' => $summit_id,
                'published' => true,
                'summit' => $summit,
            ]
        );
    }

    /**
     * @param $summit_id
     * @return mixed
     */
    public function getSpeakersCSV($summit_id)
    {
        $summit = SummitFinderStrategyFactory::build($this->getRepository(), $this->getResourceServerContext())->find($summit_id);
        if (is_null($summit)) return $this->error404();

        return $this->_getAllCSV(
            function () {
                return [
                    'first_name' => ['=@', '@@', '=='],
                    'last_name' => ['=@', '@@', '=='],
                    'email' => ['=@', '@@', '=='],
                    'id' => ['=='],
                    'full_name' => ['=@', '@@', '=='],
                    'has_accepted_presentations' => ['=='],
                    'has_alternate_presentations' => ['=='],
                    'has_rejected_presentations' => ['=='],
                    'presentations_track_id' => ['=='],
                    'presentations_selection_plan_id' => ['=='],
                    'presentations_type_id' => ['=='],
                    'presentations_title' => ['=@', '@@', '=='],
                    'presentations_abstract' => ['=@', '@@', '=='],
                    'presentations_submitter_full_name' => ['=@', '@@', '=='],
                    'presentations_submitter_email' => ['=@', '@@', '=='],
                ];
            },
            function () {
                return [
                    'first_name' => 'sometimes|string',
                    'last_name' => 'sometimes|string',
                    'email' => 'sometimes|string',
                    'id' => 'sometimes|integer',
                    'full_name' => 'sometimes|string',
                    'has_accepted_presentations' => 'sometimes|required|string|in:true,false',
                    'has_alternate_presentations' => 'sometimes|required|string|in:true,false',
                    'has_rejected_presentations' => 'sometimes|required|string|in:true,false',
                    'presentations_track_id' => 'sometimes|integer',
                    'presentations_selection_plan_id' => 'sometimes|integer',
                    'presentations_type_id' => 'sometimes|integer',
                    'presentations_title' => 'sometimes|string',
                    'presentations_abstract' => 'sometimes|string',
                    'presentations_submitter_full_name' => 'sometimes|string',
                    'presentations_submitter_email' => 'sometimes|string',
                ];
            },
            function () {
                return [
                    'full_name',
                    'first_name',
                    'last_name',
                    'id',
                    'email',
                ];
            },
            function ($filter) use ($summit) {
                return $filter;
            },
            function () {
                return SerializerRegistry::SerializerType_CSV;
            },
            function () {
                return [];
            },
            function () {
                return [
                    'id',
                    'first_name',
                    'last_name',
                    'email',
                    'accepted_presentations',
                    'accepted_presentations_count',
                    'alternate_presentations',
                    'alternate_presentations_count',
                    'rejected_presentations',
                    'rejected_presentations_count'
                ];
            },
            'speakers-',
            [
                'summit_id' => $summit_id,
                'published' => true,
                'summit' => $summit
            ],
            function ($page, $per_page, $filter, $order, $applyExtraFilters) use ($summit) {
                return $this->speaker_repository->getSpeakersBySummit
                (
                    $summit,
                    new PagingInfo($page, $per_page),
                    call_user_func($applyExtraFilters, $filter),
                    $order
                );
            },
        );
    }

    /**
     * @param $summit_id
     * @return mixed
     */
    public function getSpeakersOnSchedule($summit_id)
    {
        $summit = SummitFinderStrategyFactory::build($this->getRepository(), $this->getResourceServerContext())->find($summit_id);
        if (is_null($summit)) return $this->error404();

        return $this->_getAll(
            function () {
                return [
                    'first_name' => ['=@', '=='],
                    'last_name' => ['=@', '=='],
                    'email' => ['=@', '=='],
                    'id' => ['=='],
                    'full_name' => ['=@', '=='],
                    'event_start_date' => ['>', '<', '<=', '>=', '=='],
                    'event_end_date' => ['>', '<', '<=', '>=', '=='],
                    'featured' => ['=='],
                ];
            },
            function () {
                return [
                    'first_name' => 'sometimes|string',
                    'last_name' => 'sometimes|string',
                    'email' => 'sometimes|string',
                    'id' => 'sometimes|integer',
                    'full_name' => 'sometimes|string',
                    'event_start_date' => 'sometimes|date_format:U',
                    'event_end_date' => 'sometimes|date_format:U',
                    'featured' => 'sometimes|required|string|in:true,false',
                ];
            },
            function () {
                return [
                    'first_name',
                    'last_name',
                    'id',
                    'email',
                ];
            },
            function ($filter) use ($summit) {
                return $filter;
            },
            function () {
                $current_member = $this->resource_server_context->getCurrentUser();
                $serializer_type = SerializerRegistry::SerializerType_Public;

                if (!is_null($current_member) && ($current_member->isAdmin() || $current_member->isSummitAdmin())) {
                    $serializer_type = SerializerRegistry::SerializerType_Admin;
                }
                return $serializer_type;
            },
            null,
            null,
            function ($page, $per_page, $filter, $order, $applyExtraFilters) use ($summit) {
                return $this->speaker_repository->getSpeakersBySummitAndOnSchedule
                (
                    $summit,
                    new PagingInfo($page, $per_page),
                    call_user_func($applyExtraFilters, $filter),
                    $order
                );
            },
            [
                'summit_id' => $summit_id,
                'published' => true,
                'summit' => $summit
            ]
        );
    }

    /**
     * get all speakers without summit
     * @return mixed
     */
    public function getAll()
    {
        return $this->_getAll(
            function () {
                return [
                    'first_name' => ['=@', '=='],
                    'last_name' => ['=@', '=='],
                    'email' => ['=@', '=='],
                    'id' => ['=='],
                    'full_name' => ['=@', '=='],
                ];
            },
            function () {
                return [
                    'first_name' => 'sometimes|string',
                    'last_name' => 'sometimes|string',
                    'email' => 'sometimes|string',
                    'id' => 'sometimes|integer',
                    'full_name' => 'sometimes|string',
                ];
            },
            function () {
                return [
                    'first_name',
                    'last_name',
                    'id',
                    'email',
                ];
            },
            function ($filter) {
                return $filter;
            },
            function () {
                $current_member = $this->resource_server_context->getCurrentUser();
                $serializer_type = SerializerRegistry::SerializerType_Public;

                if (!is_null($current_member) && ($current_member->isAdmin() || $current_member->isSummitAdmin())) {
                    $serializer_type = SerializerRegistry::SerializerType_Admin;
                }
                return $serializer_type;
            },
            null,
            null,
            function ($page, $per_page, $filter, $order, $applyExtraFilters) {
                return $this->speaker_repository->getAllByPage
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
     * @param $speaker_id
     * @return mixed
     */
    public function getSummitSpeaker($summit_id, $speaker_id)
    {
        try {
            $summit = SummitFinderStrategyFactory::build($this->repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $speaker = CheckSpeakerStrategyFactory::build(CheckSpeakerStrategyFactory::Me, $this->resource_server_context)->check($speaker_id, $summit);
            if (is_null($speaker)) return $this->error404();

            $current_member = $this->resource_server_context->getCurrentUser();
            $serializer_type = SerializerRegistry::SerializerType_Public;
            // if speaker profile belongs to current member
            if (!is_null($current_member)) {
                if ($speaker->getMemberId() == $current_member->getId())
                    $serializer_type = SerializerRegistry::SerializerType_Private;

                if ($current_member->isAdmin() || $current_member->isSummitAdmin()) {
                    $serializer_type = SerializerRegistry::SerializerType_Admin;
                }
            }

            return $this->ok
            (
                SerializerRegistry::getInstance()->getSerializer($speaker, $serializer_type)->serialize
                (
                    Request::input('expand', ''),
                    [],
                    [],
                    ['summit_id' => $summit_id, 'published' => true, 'summit' => $summit]
                )
            );

        } catch (ValidationException $ex1) {
            Log::warning($ex1);
            return $this->error412($ex1->getMessages());
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
    public function getMySummitSpeaker($summit_id)
    {
        try {
            $summit = SummitFinderStrategyFactory::build($this->repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $speaker = CheckSpeakerStrategyFactory::build(CheckSpeakerStrategyFactory::Me, $this->resource_server_context)->check('me', $summit);
            if (is_null($speaker)) return $this->error404();

            $serializer_type = SerializerRegistry::SerializerType_Private;

            return $this->ok
            (
                SerializerRegistry::getInstance()->getSerializer($speaker, $serializer_type)->serialize
                (
                    Request::input('expand', ''),
                    [],
                    [],
                    [
                        'summit_id' => $summit_id,
                        'published' => Request::input('published', false),
                        'summit' => $summit
                    ]
                )
            );

        } catch (ValidationException $ex1) {
            Log::warning($ex1);
            return $this->error412($ex1->getMessages());
        } catch (EntityNotFoundException $ex2) {
            Log::warning($ex2);
            return $this->error404(array('message' => $ex2->getMessage()));
        } catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    /**
     * @return mixed
     */
    public function getMySpeaker()
    {
        try {
            $current_member = $this->resource_server_context->getCurrentUser();
            if (is_null($current_member)) return $this->error403();

            $speaker = $this->speaker_repository->getByMember($current_member);
            if (is_null($speaker)) return $this->error404();

            $serializer_type = SerializerRegistry::SerializerType_Private;

            return $this->ok
            (
                SerializerRegistry::getInstance()->getSerializer($speaker, $serializer_type)->serialize
                (
                    Request::input('expand', ''),
                    [],
                    [],
                    []
                )
            );

        } catch (ValidationException $ex1) {
            Log::warning($ex1);
            return $this->error412($ex1->getMessages());
        } catch (EntityNotFoundException $ex2) {
            Log::warning($ex2);
            return $this->error404(array('message' => $ex2->getMessage()));
        } catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    /**
     * @return mixed
     */
    public function createMySpeaker()
    {
        try {
            if (!Request::isJson()) return $this->error400();
            $data = Request::json();

            $current_member = $this->resource_server_context->getCurrentUser();
            if (is_null($current_member)) return $this->error403();

            $current_member = $this->resource_server_context->getCurrentUser();
            if (is_null($current_member)) return $this->error403();

            $rules = [
                'title' => 'sometimes|string|max:100',
                'first_name' => 'sometimes|string|max:100',
                'last_name' => 'sometimes|string|max:100',
                'bio' => 'sometimes|string',
                'notes' => 'sometimes|string',
                'irc' => 'sometimes|string|max:50',
                'twitter' => 'sometimes|string|max:50',
                'email' => 'sometimes|email:rfc|max:50',
                'funded_travel' => 'sometimes|boolean',
                'willing_to_travel' => 'sometimes|boolean',
                'willing_to_present_video' => 'sometimes|boolean',
                'org_has_cloud' => 'sometimes|boolean',
                'available_for_bureau' => 'sometimes|boolean',
                'country' => 'sometimes|country_iso_alpha2_code',
                // collections
                'languages' => 'sometimes|int_array',
                'areas_of_expertise' => 'sometimes|string_array',
                'other_presentation_links' => 'sometimes|link_array',
                'travel_preferences' => 'sometimes|string_array',
                'organizational_roles' => 'sometimes|int_array',
                'other_organizational_rol' => 'sometimes|string|max:255',
                'active_involvements' => 'sometimes|int_array',
                'company' => 'sometimes|string|max:255',
                'phone_number' => 'sometimes|string|max:255',
            ];

            // Creates a Validator instance and validates the data.
            $validation = Validator::make($data->all(), $rules);

            if ($validation->fails()) {
                $messages = $validation->messages()->toArray();

                return $this->error412
                (
                    $messages
                );
            }

            $fields = [
                'title',
                'bio',
                'notes'
            ];

            // set data from current member ...
            $payload = [
                'member_id' => $current_member->getId(),
                'first_name' => $current_member->getFirstName(),
                'last_name' => $current_member->getLastName(),
                'bio' => $current_member->getBio(),
                'twitter' => $current_member->getTwitterHandle(),
                'irc' => $current_member->getIrcHandle(),
            ];

            $payload = array_merge($payload, $data->all());

            $speaker = $this->service->addSpeaker(HTMLCleaner::cleanData($payload, $fields), $current_member);

            return $this->created
            (
                SerializerRegistry::getInstance()
                    ->getSerializer($speaker, SerializerRegistry::SerializerType_Private)
                    ->serialize
                    (
                        SerializerUtils::getExpand(),
                        SerializerUtils::getFields(),
                        SerializerUtils::getRelations()
                    )
            );
        } catch (ValidationException $ex1) {
            Log::warning($ex1);
            return $this->error412($ex1->getMessages());
        } catch (EntityNotFoundException $ex2) {
            Log::warning($ex2);
            return $this->error404(array('message' => $ex2->getMessage()));
        } catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    /**
     * @return mixed
     */
    public function updateMySpeaker()
    {
        try {
            $current_member = $this->resource_server_context->getCurrentUser();
            if (is_null($current_member)) return $this->error403();

            $speaker = $this->speaker_repository->getByMember($current_member);
            if (is_null($speaker)) return $this->error404();

            return $this->updateSpeaker($speaker->getId());

        } catch (ValidationException $ex1) {
            Log::warning($ex1);
            return $this->error412($ex1->getMessages());
        } catch (EntityNotFoundException $ex2) {
            Log::warning($ex2);
            return $this->error404(array('message' => $ex2->getMessage()));
        } catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    /**
     * @param $speaker_id
     * @return mixed
     */
    public function getSpeaker($speaker_id)
    {
        try {

            $speaker = $this->speaker_repository->getById($speaker_id);
            if (is_null($speaker) || !$speaker instanceof PresentationSpeaker) return $this->error404();

            $current_member = $this->resource_server_context->getCurrentUser();
            $serializer_type = SerializerRegistry::SerializerType_Public;
            // if speaker profile belongs to current member
            if (!is_null($current_member)) {
                if ($speaker->getMemberId() == $current_member->getId() || $speaker->canBeEditedBy($current_member))
                    $serializer_type = SerializerRegistry::SerializerType_Private;
                if ($current_member->isAdmin() || $current_member->isSummitAdmin()) {
                    $serializer_type = SerializerRegistry::SerializerType_Admin;
                }

            }

            return $this->ok
            (
                SerializerRegistry::getInstance()->getSerializer($speaker, $serializer_type)->serialize
                (
                    Request::input('expand', ''),
                    [],
                    [],
                    []
                )
            );

        } catch (ValidationException $ex1) {
            Log::warning($ex1);
            return $this->error412($ex1->getMessages());
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
    public function addSpeakerBySummit($summit_id)
    {
        try {
            if (!Request::isJson()) return $this->error400();
            $data = Request::json();

            $summit = SummitFinderStrategyFactory::build($this->repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $rules = [
                'title' => 'required|string|max:100',
                'first_name' => 'required|string|max:100',
                'last_name' => 'required|string|max:100',
                'bio' => 'sometimes|string',
                'irc' => 'sometimes|string|max:50',
                'twitter' => 'sometimes|string|max:50',
                'member_id' => 'sometimes|integer',
                'email' => 'sometimes|email:rfc|max:50',
                'on_site_phone' => 'sometimes|string|max:50',
                'registered' => 'sometimes|boolean',
                'is_confirmed' => 'sometimes|boolean',
                'checked_in' => 'sometimes|boolean',
                'registration_code' => 'sometimes|string',
                'available_for_bureau' => 'sometimes|boolean',
                'funded_travel' => 'sometimes|boolean',
                'willing_to_travel' => 'sometimes|boolean',
                'willing_to_present_video' => 'sometimes|boolean',
                'org_has_cloud' => 'sometimes|boolean',
                'country' => 'sometimes|string|country_iso_alpha2_code',
                // collections
                'languages' => 'sometimes|int_array',
                'areas_of_expertise' => 'sometimes|string_array',
                'other_presentation_links' => 'sometimes|link_array',
                'travel_preferences' => 'sometimes|string_array',
                'organizational_roles' => 'sometimes|int_array',
                'other_organizational_rol' => 'sometimes|string|max:255',
                'active_involvements' => 'sometimes|int_array',
            ];

            // Creates a Validator instance and validates the data.
            $validation = Validator::make($data->all(), $rules);

            if ($validation->fails()) {
                $messages = $validation->messages()->toArray();

                return $this->error412
                (
                    $messages
                );
            }

            $fields = [
                'title',
                'bio',
            ];

            $speaker = $this->service->addSpeakerBySummit($summit, HTMLCleaner::cleanData($data->all(), $fields));

            return $this->created(SerializerRegistry::getInstance()->getSerializer($speaker)->serialize());
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
     * @param $speaker_id
     * @return mixed
     */
    public function updateSpeakerBySummit($summit_id, $speaker_id)
    {
        try {
            if (!Request::isJson()) return $this->error400();
            $data = Request::json();

            $summit = SummitFinderStrategyFactory::build($this->repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $speaker = $this->speaker_repository->getById($speaker_id);
            if (is_null($speaker)) return $this->error404();

            $rules = [
                'title' => 'sometimes|string|max:100',
                'first_name' => 'sometimes|string|max:100',
                'last_name' => 'sometimes|string|max:100',
                'bio' => 'sometimes|string',
                'irc' => 'sometimes|string|max:50',
                'twitter' => 'sometimes|string|max:50',
                'member_id' => 'sometimes|integer',
                'email' => 'sometimes|email:rfc|max:50',
                'on_site_phone' => 'sometimes|string|max:50',
                'registered' => 'sometimes|boolean',
                'is_confirmed' => 'sometimes|boolean',
                'checked_in' => 'sometimes|boolean',
                'registration_code' => 'sometimes|string',
                'available_for_bureau' => 'sometimes|boolean',
                'funded_travel' => 'sometimes|boolean',
                'willing_to_travel' => 'sometimes|boolean',
                'willing_to_present_video' => 'sometimes|boolean',
                'org_has_cloud' => 'sometimes|boolean',
                'country' => 'sometimes|country_iso_alpha2_code',
                // collections
                'languages' => 'sometimes|int_array',
                'areas_of_expertise' => 'sometimes|string_array',
                'other_presentation_links' => 'sometimes|link_array',
                'travel_preferences' => 'sometimes|string_array',
                'organizational_roles' => 'sometimes|int_array',
                'other_organizational_rol' => 'sometimes|string|max:255',
                'active_involvements' => 'sometimes|int_array',
            ];

            // Creates a Validator instance and validates the data.
            $validation = Validator::make($data->all(), $rules);

            if ($validation->fails()) {
                $messages = $validation->messages()->toArray();

                return $this->error412
                (
                    $messages
                );
            }

            $fields = [
                'title',
                'bio',
            ];

            $speaker = $this->service->updateSpeakerBySummit($summit, $speaker, HTMLCleaner::cleanData($data->all(), $fields));

            return $this->updated(SerializerRegistry::getInstance()->getSerializer($speaker)->serialize());
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
     * @param LaravelRequest $request
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function addMySpeakerPhoto(LaravelRequest $request)
    {
        try {
            $current_member = $this->resource_server_context->getCurrentUser();
            if (is_null($current_member)) return $this->error403();

            $speaker = $this->speaker_repository->getByMember($current_member);
            if (is_null($speaker)) return $this->error404();

            return $this->addSpeakerPhoto($request, $speaker->getId());

        } catch (ValidationException $ex1) {
            Log::warning($ex1);
            return $this->error412($ex1->getMessages());
        } catch (EntityNotFoundException $ex2) {
            Log::warning($ex2);
            return $this->error404(array('message' => $ex2->getMessage()));
        } catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    /**
     * @return mixed
     */
    public function deleteMySpeaker()
    {
        try {

            $current_member = $this->resource_server_context->getCurrentUser();
            if (is_null($current_member)) return $this->error403();
            $speaker = $this->speaker_repository->getByMember($current_member);
            if (is_null($speaker)) return $this->error404();
            $this->deleteSpeakerPhoto($speaker->getId());
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

    /**
     * @param LaravelRequest $request
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function addMySpeakerBigPhoto(LaravelRequest $request)
    {
        try {

            $current_member = $this->resource_server_context->getCurrentUser();
            if (is_null($current_member)) return $this->error403();

            $speaker = $this->speaker_repository->getByMember($current_member);
            if (is_null($speaker)) return $this->error404();

            return $this->addSpeakerBigPhoto($request, $speaker->getId());

        } catch (EntityNotFoundException $ex1) {
            Log::warning($ex1);
            return $this->error404();
        } catch (ValidationException $ex2) {
            Log::warning($ex2);
            return $this->error412(array($ex2->getMessage()));
        } catch (\HTTP401UnauthorizedException $ex3) {
            Log::warning($ex3);
            return $this->error401();
        } catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    public function deleteMySpeakerBigPhoto()
    {
        try {

            $current_member = $this->resource_server_context->getCurrentUser();
            if (is_null($current_member)) return $this->error403();

            $speaker = $this->speaker_repository->getByMember($current_member);
            if (is_null($speaker)) return $this->error404();

            return $this->deleteSpeakerBigPhoto($speaker->getId());
        } catch (EntityNotFoundException $ex1) {
            Log::warning($ex1);
            return $this->error404();
        } catch (ValidationException $ex2) {
            Log::warning($ex2);
            return $this->error412(array($ex2->getMessage()));
        } catch (\HTTP401UnauthorizedException $ex3) {
            Log::warning($ex3);
            return $this->error401();
        } catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    /**
     * @param $speaker_from_id
     * @param $speaker_to_id
     * @return mixed
     */
    public function merge($speaker_from_id, $speaker_to_id)
    {
        try {
            if (!Request::isJson()) return $this->error400();
            $data = Request::json();

            $speaker_from = $this->speaker_repository->getById($speaker_from_id);
            if (is_null($speaker_from)) return $this->error404();

            $speaker_to = $this->speaker_repository->getById($speaker_to_id);
            if (is_null($speaker_to)) return $this->error404();

            $this->service->merge($speaker_from, $speaker_to, $data->all());

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
     * @return mixed
     */
    public function addSpeaker()
    {
        try {
            if (!Request::isJson()) return $this->error400();
            $data = Request::json();

            $current_member = $this->resource_server_context->getCurrentUser();
            if (is_null($current_member)) return $this->error403();

            $rules = [
                'title' => 'required|string|max:100',
                'first_name' => 'required|string|max:100',
                'last_name' => 'required|string|max:100',
                'bio' => 'sometimes|string',
                'notes' => 'sometimes|string',
                'irc' => 'sometimes|string|max:50',
                'twitter' => 'sometimes|string|max:50',
                'member_id' => 'sometimes|integer',
                'email' => 'sometimes|email:rfc|max:50',
                'funded_travel' => 'sometimes|boolean',
                'willing_to_travel' => 'sometimes|boolean',
                'willing_to_present_video' => 'sometimes|boolean',
                'org_has_cloud' => 'sometimes|boolean',
                'available_for_bureau' => 'sometimes|boolean',
                'country' => 'sometimes|country_iso_alpha2_code',
                // collections
                'languages' => 'sometimes|int_array',
                'areas_of_expertise' => 'sometimes|string_array',
                'other_presentation_links' => 'sometimes|link_array',
                'travel_preferences' => 'sometimes|string_array',
                'organizational_roles' => 'sometimes|int_array',
                'other_organizational_rol' => 'sometimes|string|max:255',
                'active_involvements' => 'sometimes|int_array',
                'company' => 'sometimes|string|max:255',
                'phone_number' => 'sometimes|string|max:255',
            ];

            // Creates a Validator instance and validates the data.
            $validation = Validator::make($data->all(), $rules);

            if ($validation->fails()) {
                $messages = $validation->messages()->toArray();

                return $this->error412
                (
                    $messages
                );
            }

            $fields = [
                'title',
                'bio',
                'notes'
            ];

            $speaker = $this->service->addSpeaker(HTMLCleaner::cleanData($data->all(), $fields), $current_member);

            return $this->created(SerializerRegistry::getInstance()->getSerializer($speaker, SerializerRegistry::SerializerType_Private)->serialize());
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
     * @param $speaker_id
     * @return mixed
     */
    public function updateSpeaker($speaker_id)
    {
        try {
            if (!Request::isJson()) return $this->error400();
            $data = Request::json();

            $current_member = $this->resource_server_context->getCurrentUser();
            if (is_null($current_member)) return $this->error403();

            $speaker = $this->speaker_repository->getById($speaker_id);
            if (is_null($speaker)) return $this->error404();

            if (!$speaker->canBeEditedBy($current_member)) {
                return $this->error403();
            }

            $rules = [
                'title' => 'required|string|max:100',
                'first_name' => 'required|string|max:100',
                'last_name' => 'required|string|max:100',
                'bio' => 'sometimes|string',
                'notes' => 'sometimes|string',
                'irc' => 'sometimes|string|max:50',
                'twitter' => 'sometimes|string|max:50',
                'member_id' => 'sometimes|integer',
                'email' => 'sometimes|email:rfc|max:50',
                'available_for_bureau' => 'sometimes|boolean',
                'funded_travel' => 'sometimes|boolean',
                'willing_to_travel' => 'sometimes|boolean',
                'willing_to_present_video' => 'sometimes|boolean',
                'org_has_cloud' => 'sometimes|boolean',
                'country' => 'sometimes|country_iso_alpha2_code',
                // collections
                'languages' => 'sometimes|int_array',
                'areas_of_expertise' => 'sometimes|string_array',
                'other_presentation_links' => 'sometimes|link_array',
                'travel_preferences' => 'sometimes|string_array',
                'organizational_roles' => 'sometimes|int_array',
                'other_organizational_rol' => 'sometimes|string|max:255',
                'active_involvements' => 'sometimes|int_array',
                'company' => 'sometimes|string|max:255',
                'phone_number' => 'sometimes|string|max:255',
            ];

            // Creates a Validator instance and validates the data.
            $validation = Validator::make($data->all(), $rules);

            if ($validation->fails()) {
                $messages = $validation->messages()->toArray();

                return $this->error412
                (
                    $messages
                );
            }

            $fields = [
                'title',
                'bio',
                'notes',
            ];

            $speaker = $this->service->updateSpeaker($speaker, HTMLCleaner::cleanData($data->all(), $fields));

            return $this->updated(SerializerRegistry::getInstance()->getSerializer($speaker, SerializerRegistry::SerializerType_Private)->serialize());
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
     * @param $speaker_id
     * @return mixed
     */
    public function deleteSpeaker($speaker_id)
    {
        try {

            $speaker = $this->speaker_repository->getById($speaker_id);
            if (is_null($speaker)) return $this->error404();
            $this->service->deleteSpeaker($speaker_id);
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

    /**
     * @param $role
     * @param $selection_plan_id
     * @return mixed
     */
    public function getMySpeakerPresentationsByRoleAndBySelectionPlan($role, $selection_plan_id)
    {
        try {
            $current_member = $this->resource_server_context->getCurrentUser();
            if (is_null($current_member)) return $this->error403();

            $speaker = $this->speaker_repository->getByMember($current_member);
            if (is_null($speaker))
                return $this->error403();

            $selection_plan = $this->selection_plan_repository->getById($selection_plan_id);
            if (is_null($selection_plan))
                return $this->error404(['message' => 'missing selection plan']);

            switch ($role) {
                case 'creator':
                    $role = PresentationSpeaker::ROLE_CREATOR;
                    break;
                case 'speaker':
                    $role = PresentationSpeaker::ROLE_SPEAKER;
                    break;
                case 'moderator':
                    $role = PresentationSpeaker::ROLE_MODERATOR;
                    break;
            }
            $presentations = $speaker->getPresentationsBySelectionPlanAndRole($selection_plan, $role);

            $response = new PagingResponse
            (
                count($presentations),
                count($presentations),
                1,
                1,
                $presentations
            );

            return $this->ok($response->toArray($expand = Request::input('expand', '')));
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
     * @param $role
     * @param $summit_id
     * @return mixed
     */
    public function getMySpeakerPresentationsByRoleAndBySummit($role, $summit_id)
    {
        try {
            $current_member = $this->resource_server_context->getCurrentUser();
            if (is_null($current_member)) return $this->error403();

            $speaker = $this->speaker_repository->getByMember($current_member);
            if (is_null($speaker))
                return $this->error403();

            $summit = SummitFinderStrategyFactory::build($this->repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404(['message' => 'missing selection summit']);


            switch ($role) {
                case 'creator':
                    $role = PresentationSpeaker::ROLE_CREATOR;
                    break;
                case 'speaker':
                    $role = PresentationSpeaker::ROLE_SPEAKER;
                    break;
                case 'moderator':
                    $role = PresentationSpeaker::ROLE_MODERATOR;
                    break;
            }
            $presentations = $speaker->getPresentationsBySummitAndRole($summit, $role);

            $response = new PagingResponse
            (
                count($presentations),
                count($presentations),
                1,
                1,
                $presentations
            );

            return $this->ok($response->toArray($expand = Request::input('expand', '')));
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
     * @param $presentation_id
     * @param $speaker_id
     * @return mixed
     */
    public function addSpeakerToMyPresentation($presentation_id, $speaker_id)
    {
        try {
            $current_member = $this->resource_server_context->getCurrentUser();
            if (is_null($current_member)) return $this->error403();

            $this->summit_service->addSpeaker2Presentation($current_member->getId(), $speaker_id, $presentation_id);

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
     * @param $presentation_id
     * @param $speaker_id
     * @return mixed
     */
    public function addModeratorToMyPresentation($presentation_id, $speaker_id)
    {
        try {
            $current_member = $this->resource_server_context->getCurrentUser();
            if (is_null($current_member)) return $this->error403();

            $this->summit_service->addModerator2Presentation($current_member->getId(), $speaker_id, $presentation_id);

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
     * @param $presentation_id
     * @param $speaker_id
     * @return mixed
     */
    public function removeSpeakerFromMyPresentation($presentation_id, $speaker_id)
    {
        try {
            $current_member = $this->resource_server_context->getCurrentUser();
            if (is_null($current_member)) return $this->error403();

            $this->summit_service->removeSpeakerFromPresentation($current_member->getId(), $speaker_id, $presentation_id);

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

    /**
     * @param $presentation_id
     * @param $speaker_id
     * @return mixed
     */
    public function removeModeratorFromMyPresentation($presentation_id, $speaker_id)
    {
        try {
            $current_member = $this->resource_server_context->getCurrentUser();
            if (is_null($current_member)) return $this->error403();

            $this->summit_service->removeModeratorFromPresentation($current_member->getId(), $speaker_id, $presentation_id);

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

    /**
     * @param $speaker_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function requestSpeakerEditPermission($speaker_id)
    {
        try {
            $current_member = $this->resource_server_context->getCurrentUser();
            if (is_null($current_member)) return $this->error403();

            $request = $this->service->requestSpeakerEditPermission($current_member->getId(), $speaker_id);

            return $this->created(
                SerializerRegistry::getInstance()->getSerializer($request)
            );

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
     * @param $speaker_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function getSpeakerEditPermission($speaker_id)
    {
        try {
            $current_member = $this->resource_server_context->getCurrentUser();
            if (is_null($current_member)) return $this->error403();

            $request = $this->service->getSpeakerEditPermission($current_member->getId(), $speaker_id);

            return $this->ok(
                SerializerRegistry::getInstance()->getSerializer($request)->serialize()
            );

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
     * @param $speaker_id
     * @param $hash
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function approveSpeakerEditPermission($speaker_id, $hash)
    {
        try {
            $request = $this->service->approveSpeakerEditPermission($hash, $speaker_id);
            return response()->view('speakers.edit_permissions.approved', [], 200);
        } catch (ValidationException $ex1) {
            Log::warning($ex1);
            return response()->view('speakers.edit_permissions.approved_validation_error', [], 412);
        } catch (EntityNotFoundException $ex2) {
            Log::warning($ex2);
            return response()->view('speakers.edit_permissions.approved_error', [], 404);
        } catch (Exception $ex) {
            Log::error($ex);
            return response()->view('speakers.edit_permissions.approved_error', [], 500);
        }
    }

    /**
     * @param $speaker_id
     * @param $hash
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function declineSpeakerEditPermission($speaker_id, $hash)
    {
        try {

            $request = $this->service->rejectSpeakerEditPermission($hash, $speaker_id);
            return response()->view('speakers.edit_permissions.rejected', [], 200);
        } catch (ValidationException $ex1) {
            Log::warning($ex1);
            return response()->view('speakers.edit_permissions.rejected_validation_error', [], 412);
        } catch (EntityNotFoundException $ex2) {
            Log::warning($ex2);
            return response()->view('speakers.edit_permissions.rejected_error', [], 404);
        } catch (Exception $ex) {
            Log::error($ex);
            return response()->view('speakers.edit_permissions.rejected_error', [], 500);
        }
    }

    /**
     * @param LaravelRequest $request
     * @param $speaker_id
     * @return mixed
     */
    public function addSpeakerPhoto(LaravelRequest $request, $speaker_id)
    {
        try {

            $current_member = $this->resource_server_context->getCurrentUser();
            if (is_null($current_member)) return $this->error403();

            $speaker = $this->speaker_repository->getById($speaker_id);
            if (is_null($speaker)) return $this->error404();

            if (!$speaker->canBeEditedBy($current_member)) {
                return $this->error403();
            }

            $file = $request->file('file');
            if (is_null($file)) {
                return $this->error412(array('file param not set!'));
            }

            $photo = $this->service->addSpeakerPhoto($speaker_id, $file);

            return $this->created(SerializerRegistry::getInstance()->getSerializer($photo)->serialize());

        } catch (EntityNotFoundException $ex1) {
            Log::warning($ex1);
            return $this->error404();
        } catch (ValidationException $ex2) {
            Log::warning($ex2);
            return $this->error412(array($ex2->getMessage()));
        } catch (\HTTP401UnauthorizedException $ex3) {
            Log::warning($ex3);
            return $this->error401();
        } catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    public function deleteSpeakerPhoto($speaker_id)
    {
        try {

            $current_member = $this->resource_server_context->getCurrentUser();
            if (is_null($current_member)) return $this->error403();

            $speaker = $this->speaker_repository->getById($speaker_id);
            if (is_null($speaker)) return $this->error404();

            if (!$speaker->canBeEditedBy($current_member)) {
                return $this->error403();
            }

            $this->service->deleteSpeakerPhoto($speaker_id);

            return $this->deleted();

        } catch (EntityNotFoundException $ex1) {
            Log::warning($ex1);
            return $this->error404();
        } catch (ValidationException $ex2) {
            Log::warning($ex2);
            return $this->error412(array($ex2->getMessage()));
        } catch (\HTTP401UnauthorizedException $ex3) {
            Log::warning($ex3);
            return $this->error401();
        } catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    public function addSpeakerBigPhoto(LaravelRequest $request, $speaker_id)
    {
        try {

            $current_member = $this->resource_server_context->getCurrentUser();
            if (is_null($current_member)) return $this->error403();

            $speaker = $this->speaker_repository->getById($speaker_id);
            if (is_null($speaker)) return $this->error404();

            if (!$speaker->canBeEditedBy($current_member)) {
                return $this->error403();
            }

            $file = $request->file('file');
            if (is_null($file)) {
                return $this->error412(array('file param not set!'));
            }

            $photo = $this->service->addSpeakerBigPhoto($speaker_id, $file);

            return $this->created(SerializerRegistry::getInstance()->getSerializer($photo)->serialize());

        } catch (EntityNotFoundException $ex1) {
            Log::warning($ex1);
            return $this->error404();
        } catch (ValidationException $ex2) {
            Log::warning($ex2);
            return $this->error412(array($ex2->getMessage()));
        } catch (\HTTP401UnauthorizedException $ex3) {
            Log::warning($ex3);
            return $this->error401();
        } catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    public function deleteSpeakerBigPhoto($speaker_id)
    {
        try {

            $current_member = $this->resource_server_context->getCurrentUser();
            if (is_null($current_member)) return $this->error403();

            $speaker = $this->speaker_repository->getById($speaker_id);
            if (is_null($speaker)) return $this->error404();

            if (!$speaker->canBeEditedBy($current_member)) {
                return $this->error403();
            }

            $this->service->deleteSpeakerBigPhoto($speaker_id);

            return $this->deleted();

        } catch (EntityNotFoundException $ex1) {
            Log::warning($ex1);
            return $this->error404();
        } catch (ValidationException $ex2) {
            Log::warning($ex2);
            return $this->error412(array($ex2->getMessage()));
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
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function send($summit_id)
    {
        return $this->processRequest(function () use ($summit_id) {
            if (!Request::isJson()) return $this->error400();

            $summit = SummitFinderStrategyFactory::build($this->repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404(['message' => 'missing selection summit']);

            $payload = $this->getJsonPayload(SummitSpeakerEmailsValidationRulesFactory::buildForAdd());

            $filter = null;

            if (Request::has('filter')) {
                $filter = FilterParser::parse(Request::input('filter'), [
                    'first_name' => ['=@', '@@', '=='],
                    'last_name' => ['=@', '@@', '=='],
                    'email' => ['=@', '@@', '=='],
                    'id' => ['=='],
                    'full_name' => ['=@', '@@', '=='],
                    'has_accepted_presentations' => ['=='],
                    'has_alternate_presentations' => ['=='],
                    'has_rejected_presentations' => ['=='],
                    'presentations_track_id' => ['=='],
                    'presentations_selection_plan_id' => ['=='],
                    'presentations_type_id' => ['=='],
                    'presentations_title' => ['=@', '@@', '=='],
                    'presentations_abstract' => ['=@', '@@', '=='],
                    'presentations_submitter_full_name' => ['=@', '@@', '=='],
                    'presentations_submitter_email' => ['=@', '@@', '=='],
                ]);
            }

            if (is_null($filter))
                $filter = new Filter();

            $filter->validate([
                'first_name' => 'sometimes|string',
                'last_name' => 'sometimes|string',
                'email' => 'sometimes|string',
                'id' => 'sometimes|integer',
                'full_name' => 'sometimes|string',
                'has_accepted_presentations' => 'sometimes|required|string|in:true,false',
                'has_alternate_presentations' => 'sometimes|required|string|in:true,false',
                'has_rejected_presentations' => 'sometimes|required|string|in:true,false',
                'presentations_track_id' => 'sometimes|integer',
                'presentations_selection_plan_id' => 'sometimes|integer',
                'presentations_type_id' => 'sometimes|integer',
                'presentations_title' => 'sometimes|string',
                'presentations_abstract' => 'sometimes|string',
                'presentations_submitter_full_name' => 'sometimes|string',
                'presentations_submitter_email' => 'sometimes|string',
            ]);

            $this->service->triggerSendEmails($summit, $payload, Request::input('filter'));

            return $this->ok();
        });
    }
}