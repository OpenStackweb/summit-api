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

use App\Models\Foundation\Summit\Repositories\ISelectionPlanRepository;
use App\Models\Foundation\Summit\SelectionPlan;
use App\ModelSerializers\SerializerUtils;
use Exception;
use Illuminate\Http\Request as LaravelRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
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
use utils\FilterParser;
use utils\PagingInfo;
use utils\PagingResponse;

/**
 * Class OAuth2SummitSpeakersApiController
 * @package App\Http\Controllers
 */
final class OAuth2SummitSpeakersApiController extends OAuth2ProtectedController
{
    use RequestProcessor;

    use GetAndValidateJsonPayload;

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
                    'member_id' => ['=='],
                    'member_user_external_id' => ['=='],
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
                    'member_id' => 'sometimes|integer',
                    'member_user_external_id' => 'sometimes|integer',
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
                    'member_id' => ['=='],
                    'member_user_external_id' => ['=='],
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
                    'member_id' => 'sometimes|integer',
                    'member_user_external_id' => 'sometimes|integer',
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
                    'first_name' => ['=@', '==','@@'],
                    'last_name' => ['=@', '==','@@'],
                    'email' => ['=@', '==','@@'],
                    'id' => ['=='],
                    'full_name' => ['=@', '==','@@'],
                    'member_id' => ['=='],
                    'member_user_external_id' => ['=='],
                ];
            },
            function () {
                return [
                    'first_name' => 'sometimes|string',
                    'last_name' => 'sometimes|string',
                    'email' => 'sometimes|string',
                    'id' => 'sometimes|integer',
                    'full_name' => 'sometimes|string',
                    'member_id' => 'sometimes|integer',
                    'member_user_external_id' => 'sometimes|integer',
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
        return $this->processRequest(function () use ($summit_id, $speaker_id) {

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
                    SerializerUtils::getExpand(),
                    SerializerUtils::getFields(),
                    SerializerUtils::getRelations(),
                    ['summit_id' => $summit_id, 'published' => true, 'summit' => $summit]
                )
            );

        });
    }

    /**
     * @param $summit_id
     * @return mixed
     */
    public function getMySummitSpeaker($summit_id)
    {
        return $this->processRequest(function () use ($summit_id) {
            $summit = SummitFinderStrategyFactory::build($this->repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $speaker = CheckSpeakerStrategyFactory::build(CheckSpeakerStrategyFactory::Me, $this->resource_server_context)->check('me', $summit);
            if (is_null($speaker)) return $this->error404();

            $serializer_type = SerializerRegistry::SerializerType_Private;

            return $this->ok
            (
                SerializerRegistry::getInstance()->getSerializer($speaker, $serializer_type)->serialize
                (
                    SerializerUtils::getExpand(),
                    SerializerUtils::getFields(),
                    SerializerUtils::getRelations(),
                    [
                        'summit_id' => $summit_id,
                        'published' => Request::input('published', false),
                        'summit' => $summit
                    ]
                )
            );
        });
    }

    /**
     * @return mixed
     */
    public function getMySpeaker()
    {
        return $this->processRequest(function () {

            $current_member = $this->resource_server_context->getCurrentUser();
            if (is_null($current_member)) return $this->error403();

            $speaker = $this->service->getSpeakerByMember($current_member);
            if (is_null($speaker))
                return $this->error404();

            $serializer_type = SerializerRegistry::SerializerType_Private;

            return $this->ok
            (
                SerializerRegistry::getInstance()->getSerializer($speaker, $serializer_type)->serialize
                (
                    SerializerUtils::getExpand(),
                    SerializerUtils::getFields(),
                    SerializerUtils::getRelations(),
                )
            );

        });
    }

    /**
     * @return mixed
     */
    public function createMySpeaker()
    {
        return $this->processRequest(function () {

            $current_member = $this->resource_server_context->getCurrentUser();
            if (is_null($current_member)) return $this->error403();

            $current_member = $this->resource_server_context->getCurrentUser();
            if (is_null($current_member)) return $this->error403();

            // Creates a Validator instance and validates the data.

            $payload = $this->getJsonPayload(
                [
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
                ],
                true
            );

            $fields = [
                'title',
                'bio',
                'notes'
            ];

            // set data from current member ...
            $aux_payload = [
                'member_id' => $current_member->getId(),
                'first_name' => $current_member->getFirstName(),
                'last_name' => $current_member->getLastName(),
                'bio' => $current_member->getBio(),
                'twitter' => $current_member->getTwitterHandle(),
                'irc' => $current_member->getIrcHandle(),
            ];

            $payload = array_merge($payload, $aux_payload);

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

        });
    }

    /**
     * @return mixed
     */
    public function updateMySpeaker()
    {
        return $this->processRequest(function () {

            $current_member = $this->resource_server_context->getCurrentUser();
            if (is_null($current_member)) return $this->error403();

            $speaker = $this->speaker_repository->getByMember($current_member);
            if (is_null($speaker)) return $this->error404();

            return $this->updateSpeaker($speaker->getId());

        });
    }

    /**
     * @param $speaker_id
     * @return mixed
     */
    public function getSpeaker($speaker_id)
    {
        return $this->processRequest(function () use ($speaker_id) {

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
                    SerializerUtils::getExpand(),
                    SerializerUtils::getFields(),
                    SerializerUtils::getRelations()
                )
            );

        });
    }

    /**
     * @param $summit_id
     * @return mixed
     */
    public function addSpeakerBySummit($summit_id)
    {
        return $this->processRequest(function () use ($summit_id) {

            $summit = SummitFinderStrategyFactory::build($this->repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $payload = $this->getJsonPayload(
                [
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
                ], true
            );

            $fields = [
                'title',
                'bio',
            ];

            $speaker = $this->service->addSpeakerBySummit
            (
                $summit,
                HTMLCleaner::cleanData($payload, $fields)
            );

            return $this->created(SerializerRegistry::getInstance()->getSerializer($speaker)->serialize(
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));

        });
    }

    /**
     * @param $summit_id
     * @param $speaker_id
     * @return mixed
     */
    public function updateSpeakerBySummit($summit_id, $speaker_id)
    {
        return $this->processRequest(function () use ($summit_id, $speaker_id) {

            $summit = SummitFinderStrategyFactory::build($this->repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $speaker = $this->speaker_repository->getById($speaker_id);
            if (!$speaker instanceof PresentationSpeaker) return $this->error404();

            $payload = $this->getJsonPayload(
                [
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
                ], true
            );

            $fields = [
                'title',
                'bio',
            ];

            $speaker = $this->service->updateSpeakerBySummit($summit, $speaker, HTMLCleaner::cleanData($payload, $fields));

            return $this->updated(SerializerRegistry::getInstance()->getSerializer($speaker)->serialize(
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));
        });
    }

    /**
     * @param LaravelRequest $request
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function addMySpeakerPhoto(LaravelRequest $request)
    {
        $current_member = $this->resource_server_context->getCurrentUser();
        if (is_null($current_member)) return $this->error403();

        $speaker = $this->speaker_repository->getByMember($current_member);
        if (is_null($speaker)) return $this->error404();

        return $this->addSpeakerPhoto($request, $speaker->getId());
    }

    /**
     * @return mixed
     */
    public function deleteMySpeaker()
    {
        $current_member = $this->resource_server_context->getCurrentUser();
        if (is_null($current_member)) return $this->error403();
        $speaker = $this->speaker_repository->getByMember($current_member);
        if (is_null($speaker)) return $this->error404();
        $this->deleteSpeakerPhoto($speaker->getId());
        return $this->deleted();
    }

    /**
     * @param LaravelRequest $request
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function addMySpeakerBigPhoto(LaravelRequest $request)
    {
        $current_member = $this->resource_server_context->getCurrentUser();
        if (is_null($current_member)) return $this->error403();

        $speaker = $this->speaker_repository->getByMember($current_member);
        if (is_null($speaker)) return $this->error404();

        return $this->addSpeakerBigPhoto($request, $speaker->getId());
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteMySpeakerBigPhoto()
    {
        $current_member = $this->resource_server_context->getCurrentUser();
        if (is_null($current_member)) return $this->error403();

        $speaker = $this->speaker_repository->getByMember($current_member);
        if (is_null($speaker)) return $this->error404();

        return $this->deleteSpeakerBigPhoto($speaker->getId());
    }

    /**
     * @param $speaker_from_id
     * @param $speaker_to_id
     * @return mixed
     */
    public function merge($speaker_from_id, $speaker_to_id)
    {
        return $this->processRequest(function() use($speaker_from_id, $speaker_to_id) {
            if (!Request::isJson()) return $this->error400();
            $data = Request::json();

            $speaker_from = $this->speaker_repository->getById(intval($speaker_from_id));
            if (!$speaker_from instanceof PresentationSpeaker) return $this->error404();

            $speaker_to = $this->speaker_repository->getById(intval($speaker_to_id));
            if (!$speaker_to instanceof PresentationSpeaker) return $this->error404();

            $this->service->merge($speaker_from, $speaker_to, $data->all());

            return $this->updated();
        });
    }

    /**
     * @return mixed
     */
    public function addSpeaker()
    {
       return $this->processRequest(function(){

            $current_member = $this->resource_server_context->getCurrentUser();
            if (is_null($current_member)) return $this->error403();

            $payload = $this->getJsonPayload(
                [
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
                ], true
            );

            $fields = [
                'title',
                'bio',
                'notes'
            ];

            $speaker = $this->service->addSpeaker(HTMLCleaner::cleanData($payload, $fields), $current_member);

            return $this->created(SerializerRegistry::getInstance()
                ->getSerializer($speaker, SerializerRegistry::SerializerType_Private)->serialize(
                    SerializerUtils::getExpand(),
                    SerializerUtils::getFields(),
                    SerializerUtils::getRelations()
                ));
        });
    }

    /**
     * @param $speaker_id
     * @return mixed
     */
    public function updateSpeaker($speaker_id)
    {
        return $this->processRequest(function() use($speaker_id){

            $current_member = $this->resource_server_context->getCurrentUser();
            if (is_null($current_member)) return $this->error403();

            $speaker = $this->speaker_repository->getById(intval($speaker_id));
            if (!$speaker instanceof PresentationSpeaker) return $this->error404();

            if (!$speaker->canBeEditedBy($current_member)) {
                return $this->error403();
            }

            $payload = $this->getJsonPayload([
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
            ], true);

            $fields = [
                'title',
                'bio',
                'notes',
            ];

            $speaker = $this->service->updateSpeaker($speaker, HTMLCleaner::cleanData($payload, $fields));

            return $this->updated(SerializerRegistry::getInstance()
                ->getSerializer($speaker, SerializerRegistry::SerializerType_Private)->serialize(
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));
        });
    }

    /**
     * @param $speaker_id
     * @return mixed
     */
    public function deleteSpeaker($speaker_id)
    {
        return $this->processRequest(function() use($speaker_id){

            $speaker = $this->speaker_repository->getById(intval($speaker_id));
            if (is_null($speaker)) return $this->error404();
            $this->service->deleteSpeaker(intval($speaker_id));
            return $this->deleted();

        });
    }

    /**
     * @param $role
     * @param $selection_plan_id
     * @return mixed
     */
    public function getMySpeakerPresentationsByRoleAndBySelectionPlan($role, $selection_plan_id)
    {
        return $this->processRequest(function () use($role, $selection_plan_id){
            $current_member = $this->resource_server_context->getCurrentUser();
            if (is_null($current_member)) return $this->error403();

            $speaker = $this->speaker_repository->getByMember($current_member);
            if (is_null($speaker))
                return $this->error403();

            $selection_plan = $this->selection_plan_repository->getById(intval($selection_plan_id));
            if (!$selection_plan instanceof SelectionPlan)
                return $this->error404();

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

            return $this->ok($response->toArray(
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));
        });
    }

    /**
     * @param $role
     * @param $summit_id
     * @return mixed
     */
    public function getMySpeakerPresentationsByRoleAndBySummit($role, $summit_id)
    {
        return $this->processRequest(function() use($role, $summit_id){

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

            return $this->ok($response->toArray(
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));
        });
    }

    /**
     * @param $presentation_id
     * @param $speaker_id
     * @return mixed
     */
    public function addSpeakerToMyPresentation($presentation_id, $speaker_id)
    {
        return $this->processRequest(function () use ($presentation_id, $speaker_id) {

            $current_member = $this->resource_server_context->getCurrentUser();
            if (is_null($current_member)) return $this->error403();

            $this->summit_service->addSpeaker2Presentation($current_member->getId(), intval($speaker_id), intval($presentation_id));

            return $this->updated();

        });
    }

    /**
     * @param $presentation_id
     * @param $speaker_id
     * @return mixed
     */
    public function addModeratorToMyPresentation($presentation_id, $speaker_id)
    {
        return $this->processRequest(function () use ($presentation_id, $speaker_id) {

            $current_member = $this->resource_server_context->getCurrentUser();
            if (is_null($current_member)) return $this->error403();

            $this->summit_service->addModerator2Presentation($current_member->getId(), intval($speaker_id), intval($presentation_id));

            return $this->updated();

        });
    }

    /**
     * @param $presentation_id
     * @param $speaker_id
     * @return mixed
     */
    public function removeSpeakerFromMyPresentation($presentation_id, $speaker_id)
    {
        return $this->processRequest(function () use ($presentation_id, $speaker_id) {
            $current_member = $this->resource_server_context->getCurrentUser();
            if (is_null($current_member)) return $this->error403();

            $this->summit_service->removeSpeakerFromPresentation
            (
                $current_member->getId(),
                intval($speaker_id),
                intval($presentation_id)
            );

            return $this->deleted();

        });
    }

    /**
     * @param $presentation_id
     * @param $speaker_id
     * @return mixed
     */
    public function removeModeratorFromMyPresentation($presentation_id, $speaker_id)
    {
        return $this->processRequest(function () use ($presentation_id, $speaker_id) {
            $current_member = $this->resource_server_context->getCurrentUser();
            if (is_null($current_member)) return $this->error403();

            $this->summit_service->removeModeratorFromPresentation
            (
                $current_member->getId(),
                intval($speaker_id),
                intval($presentation_id)
            );

            return $this->deleted();
        });
    }

    /**
     * @param $speaker_id
     * @return mixed
     */
    public function requestSpeakerEditPermission($speaker_id)
    {
        return $this->processRequest(function () use ($speaker_id) {
            $current_member = $this->resource_server_context->getCurrentUser();
            if (is_null($current_member)) return $this->error403();

            $request = $this->service->requestSpeakerEditPermission($current_member->getId(), intval($speaker_id));

            return $this->created(
                SerializerRegistry::getInstance()->getSerializer($request)->serialize(
                    SerializerUtils::getExpand(),
                    SerializerUtils::getFields(),
                    SerializerUtils::getRelations()
                )
            );

        });
    }

    /**
     * @param $speaker_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function getSpeakerEditPermission($speaker_id)
    {

        return $this->processRequest(function () use ($speaker_id) {

            $current_member = $this->resource_server_context->getCurrentUser();
            if (is_null($current_member)) return $this->error403();

            $request = $this->service->getSpeakerEditPermission($current_member->getId(), intval($speaker_id));

            return $this->ok(
                SerializerRegistry::getInstance()->getSerializer($request)->serialize(
                    SerializerUtils::getExpand(),
                    SerializerUtils::getFields(),
                    SerializerUtils::getRelations()
                )
            );

        });
    }

    /**
     * @param $speaker_id
     * @param $hash
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function approveSpeakerEditPermission($speaker_id, $hash)
    {
        try {
            $this->service->approveSpeakerEditPermission($hash, $speaker_id);
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
            $this->service->rejectSpeakerEditPermission($hash, $speaker_id);
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
        return $this->processRequest(function () use ($request, $speaker_id) {

            $current_member = $this->resource_server_context->getCurrentUser();
            if (is_null($current_member)) return $this->error403();

            $speaker = $this->speaker_repository->getById(intval($speaker_id));
            if (is_null($speaker)) return $this->error404();

            if (!$speaker->canBeEditedBy($current_member)) {
                return $this->error403();
            }

            $file = $request->file('file');
            if (is_null($file)) {
                return $this->error412('file param not set!');
            }

            $photo = $this->service->addSpeakerPhoto(intval($speaker_id), $file);

            return $this->created(SerializerRegistry::getInstance()->getSerializer($photo)->serialize(
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));

        });
    }

    /**
     * @param $speaker_id
     * @return mixed
     */
    public function deleteSpeakerPhoto($speaker_id)
    {
        return $this->processRequest(function () use ($speaker_id) {

            $current_member = $this->resource_server_context->getCurrentUser();
            if (is_null($current_member)) return $this->error403();

            $speaker = $this->speaker_repository->getById(intval($speaker_id));
            if (is_null($speaker)) return $this->error404();

            if (!$speaker->canBeEditedBy($current_member)) {
                return $this->error403();
            }

            $this->service->deleteSpeakerPhoto(intval($speaker_id));

            return $this->deleted();

        });
    }

    /**
     * @param LaravelRequest $request
     * @param $speaker_id
     * @return mixed
     */
    public function addSpeakerBigPhoto(LaravelRequest $request, $speaker_id)
    {
        return $this->processRequest(function () use ($request, $speaker_id) {

            $current_member = $this->resource_server_context->getCurrentUser();
            if (is_null($current_member)) return $this->error403();

            $speaker = $this->speaker_repository->getById(intval($speaker_id));
            if (is_null($speaker)) return $this->error404();

            if (!$speaker->canBeEditedBy($current_member)) {
                return $this->error403();
            }

            $file = $request->file('file');
            if (is_null($file)) {
                return $this->error412('file param not set!');
            }

            $photo = $this->service->addSpeakerBigPhoto(intval($speaker_id), $file);

            return $this->created(SerializerRegistry::getInstance()->getSerializer($photo)->serialize(
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));

        });
    }

    /**
     * @param $speaker_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function deleteSpeakerBigPhoto($speaker_id)
    {
        return $this->processRequest(function () use ($speaker_id) {

            $current_member = $this->resource_server_context->getCurrentUser();
            if (is_null($current_member)) return $this->error403();

            $speaker = $this->speaker_repository->getById(intval($speaker_id));
            if (is_null($speaker)) return $this->error404();

            if (!$speaker->canBeEditedBy($current_member)) {
                return $this->error403();
            }

            $this->service->deleteSpeakerBigPhoto(intval($speaker_id));

            return $this->deleted();

        });
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
            if (is_null($summit)) return $this->error404();

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