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

use App\Models\Foundation\Main\IGroup;
use App\Models\Foundation\Summit\Registration\IBuildDefaultPaymentGatewayProfileStrategy;
use App\ModelSerializers\SerializerUtils;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request as LaravelRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Validator;
use models\oauth2\IResourceServerContext;
use models\summit\ConfirmationExternalOrderRequest;
use models\summit\IEventFeedbackRepository;
use models\summit\ISpeakerRepository;
use models\summit\ISummitEventRepository;
use models\summit\ISummitRepository;
use ModelSerializers\ISerializerTypeSelector;
use ModelSerializers\SerializerRegistry;
use services\model\ISummitService;
use utils\Filter;
use utils\FilterElement;
use utils\FilterParser;
use utils\Order;
use utils\OrderElement;
use utils\PagingInfo;

/**
 * Class OAuth2SummitApiController
 * @package App\Http\Controllers
 */
final class OAuth2SummitApiController extends OAuth2ProtectedController
{

    /**
     * @var IBuildDefaultPaymentGatewayProfileStrategy
     */
    private $build_default_payment_gateway_profile_strategy;

    /**
     * @var ISummitService
     */
    private $summit_service;

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
     * @var ISerializerTypeSelector
     */
    private $serializer_type_selector;

    /**
     * OAuth2SummitApiController constructor.
     * @param ISummitRepository $summit_repository
     * @param ISummitEventRepository $event_repository
     * @param ISpeakerRepository $speaker_repository
     * @param IEventFeedbackRepository $event_feedback_repository
     * @param ISummitService $summit_service
     * @param ISerializerTypeSelector $serializer_type_selector
     * @param IBuildDefaultPaymentGatewayProfileStrategy $build_default_payment_gateway_profile_strategy
     * @param IResourceServerContext $resource_server_context
     */
    public function __construct
    (
        ISummitRepository                          $summit_repository,
        ISummitEventRepository                     $event_repository,
        ISpeakerRepository                         $speaker_repository,
        IEventFeedbackRepository                   $event_feedback_repository,
        ISummitService                             $summit_service,
        ISerializerTypeSelector                    $serializer_type_selector,
        IBuildDefaultPaymentGatewayProfileStrategy $build_default_payment_gateway_profile_strategy,
        IResourceServerContext                     $resource_server_context
    )
    {
        parent::__construct($resource_server_context);

        $this->repository = $summit_repository;
        $this->speaker_repository = $speaker_repository;
        $this->event_repository = $event_repository;
        $this->event_feedback_repository = $event_feedback_repository;
        $this->serializer_type_selector = $serializer_type_selector;
        $this->build_default_payment_gateway_profile_strategy = $build_default_payment_gateway_profile_strategy;
        $this->summit_service = $summit_service;
    }

    use ParametrizedGetAll;

    use RequestProcessor;

    /**
     * @return mixed
     */
    public function getSummits()
    {
        $current_member = $this->resource_server_context->getCurrentUser();

        if (!is_null($current_member) &&
            !$current_member->isAdmin() &&
            !$current_member->hasAllowedSummits()) {
            return $this->error403(['message' => sprintf("Member %s has not permission for any Summit", $current_member->getId())]);
        }

        return $this->_getAll(
            function () {
                return [
                    'name' => ['=@', '==', '@@'],
                    'start_date' => ['==', '<', '>', '<=', '>='],
                    'end_date' => ['==', '<', '>', '<=', '>='],
                    'registration_begin_date' => ['==', '<', '>', '<=', '>='],
                    'registration_end_date' => ['==', '<', '>', '<=', '>='],
                    'ticket_types_count' => ['==', '<', '>', '<=', '>=', '<>'],
                ];
            },
            function () {
                return [
                    'name' => 'sometimes|required|string',
                    'start_date' => 'sometimes|required|date_format:U',
                    'end_date' => 'sometimes|required_with:start_date|date_format:U|after:start_date',
                    'registration_begin_date' => 'sometimes|required|date_format:U',
                    'registration_end_date' => 'sometimes|required_with:start_date|date_format:U|after:registration_begin_date',
                    'ticket_types_count' => 'sometimes|required|integer'
                ];
            },
            function () {
                return [
                    'id',
                    'name',
                    'start_date',
                    'registration_begin_date'
                ];
            },
            function ($filter) use ($current_member) {
                if ($filter instanceof Filter) {
                    $filter->addFilterCondition(FilterElement::makeEqual('available_on_api', '1'));
                    if (!is_null($current_member) && !$current_member->isAdmin() && $current_member->hasAllowedSummits()) {
                        // filter only the ones that we are allowed to see
                        $filter->addFilterCondition
                        (
                            FilterElement::makeEqual
                            (
                                'summit_id',
                                $current_member->getAllAllowedSummitsIds(),
                                "OR"

                            )
                        );

                    }
                }
                return $filter;
            },
            function () {
                return $this->serializer_type_selector->getSerializerType();
            },
            function () {
                return new Order([
                    OrderElement::buildAscFor("start_date"),
                ]);
            },
            function () {
                return PHP_INT_MAX;
            },
            null,
            [
                'build_default_payment_gateway_profile_strategy' => $this->build_default_payment_gateway_profile_strategy
            ]
        );
    }

    /**
     * @return mixed
     */
    public function getAllSummits()
    {

        $current_member = $this->resource_server_context->getCurrentUser();

        if (!is_null($current_member) &&
            !$current_member->isAdmin() &&
            !$current_member->hasAllowedSummits()) {
            return $this->error403(['message' => sprintf("Member %s has not permission for any Summit", $current_member->getId())]);
        }

        return $this->_getAll(
            function () {
                return [
                    'name' => ['=@', '==', '@@'],
                    'start_date' => ['==', '<', '>', '<=', '>='],
                    'end_date' => ['==', '<', '>', '<=', '>='],
                    'registration_begin_date' => ['==', '<', '>', '<=', '>='],
                    'registration_end_date' => ['==', '<', '>', '<=', '>='],
                    'ticket_types_count' => ['==', '<', '>', '<=', '>=', '<>'],
                    'submission_begin_date' => ['==', '<', '>', '<=', '>='],
                    'submission_end_date' => ['==', '<', '>', '<=', '>='],
                    'voting_begin_date' => ['==', '<', '>', '<=', '>='],
                    'voting_end_date' => ['==', '<', '>', '<=', '>='],
                    'selection_begin_date' => ['==', '<', '>', '<=', '>='],
                    'selection_end_date' => ['==', '<', '>', '<=', '>='],
                    'selection_plan_enabled' => ['=='],
                ];
            },
            function () {
                return [
                    'name' => 'sometimes|required|string',
                    'start_date' => 'sometimes|required|date_format:U',
                    'end_date' => 'sometimes|required_with:start_date|date_format:U|after:start_date',
                    'registration_begin_date' => 'sometimes|required|date_format:U',
                    'registration_end_date' => 'sometimes|required_with:start_date|date_format:U|after:registration_begin_date',
                    'ticket_types_count' => 'sometimes|required|integer',
                    'submission_begin_date' => 'sometimes|required|date_format:U',
                    'submission_end_date' => 'sometimes|required_with:submission_begin_date|date_format:U',
                    'voting_begin_date' => 'sometimes|required|date_format:U',
                    'voting_end_date' => 'sometimes|required_with:voting_begin_date|date_format:U',
                    'selection_begin_date' => 'sometimes|required|date_format:U',
                    'selection_end_date' => 'sometimes|required_with:selection_begin_date|date_format:U',
                    'selection_plan_enabled' => 'sometimes|required|boolean',
                ];
            },
            function () {
                return [
                    'id',
                    'name',
                    'start_date',
                    'registration_begin_date'
                ];
            },
            function ($filter) use ($current_member) {
                if ($filter instanceof Filter) {
                    if (!is_null($current_member) && !$current_member->isAdmin() && $current_member->hasAllowedSummits()) {
                        // filter only the ones that we are allowed to see
                        $filter->addFilterCondition
                        (
                            FilterElement::makeEqual
                            (
                                'summit_id',
                                $current_member->getAllAllowedSummitsIds(),
                                "OR"

                            )
                        );

                    }
                }
                return $filter;
            },
            function () {
                return $this->serializer_type_selector->getSerializerType();
            }
            ,
            function () {
                return new Order([
                    OrderElement::buildAscFor("start_date"),
                ]);
            },
            function () {
                return PHP_INT_MAX;
            },
            null,
            [
                'build_default_payment_gateway_profile_strategy' => $this->build_default_payment_gateway_profile_strategy
            ]
        );
    }

    /**
     * @param $summit_id
     * @return mixed
     */
    public function getSummit($summit_id)
    {
        return $this->processRequest(function () use ($summit_id) {

            $summit = SummitFinderStrategyFactory::build($this->repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();
            $current_member = $this->resource_server_context->getCurrentUser();

            if
            (
                !is_null($current_member) &&
                !$current_member->isAdmin() &&
                !$current_member->hasPermissionFor($summit)
            )
                return $this->error403(['message' => sprintf("Member %s has not permission for this Summit", $current_member->getId())]);

            $serializer_type = $this->serializer_type_selector->getSerializerType();
            return $this->ok
            (
                SerializerRegistry::getInstance()
                    ->getSerializer($summit, $serializer_type)
                    ->serialize
                    (
                        SerializerUtils::getExpand(),
                        SerializerUtils::getFields(),
                        SerializerUtils::getRelations(),
                        [
                            'build_default_payment_gateway_profile_strategy' => $this->build_default_payment_gateway_profile_strategy
                        ]
                    )
            );
        });
    }

    /**
     * @return JsonResponse|mixed
     */
    public function getAllCurrentSummit()
    {
        return $this->processRequest(function () {
            $summit = $this->repository->getCurrent();
            if (is_null($summit)) return $this->error404();
            $current_member = $this->resource_server_context->getCurrentUser();
            if (!is_null($current_member) && !$current_member->isAdmin() && !$current_member->hasPermissionFor($summit))
                return $this->error403(['message' => sprintf("Member %s has not permission for this Summit", $current_member->getId())]);
            $serializer_type = $this->serializer_type_selector->getSerializerType();
            return $this->ok
            (
                SerializerRegistry::getInstance()
                    ->getSerializer($summit, $serializer_type)
                    ->serialize(
                        SerializerUtils::getExpand(),
                        SerializerUtils::getFields(),
                        SerializerUtils::getRelations(),
                        [
                            'build_default_payment_gateway_profile_strategy' => $this->build_default_payment_gateway_profile_strategy
                        ])
            );
        });
    }

    /**
     * @param $id
     * @return JsonResponse|mixed
     */
    public function getAllSummitByIdOrSlug($id)
    {
        return $this->processRequest(function () use ($id) {
            $summit = $this->repository->getById(intval($id));
            if (is_null($summit))
                $summit = $this->repository->getBySlug(trim($id));

            if (is_null($summit)) return $this->error404();

            $current_member = $this->resource_server_context->getCurrentUser();
            if (!is_null($current_member) && !$current_member->isAdmin() && !$current_member->hasPermissionFor($summit))
                return $this->error403(['message' => sprintf("Member %s has not permission for this Summit", $current_member->getId())]);

            $serializer_type = $this->serializer_type_selector->getSerializerType();

            return $this->ok
            (
                SerializerRegistry::getInstance()
                    ->getSerializer($summit, $serializer_type)
                    ->serialize
                    (
                        SerializerUtils::getExpand(),
                        SerializerUtils::getFields(),
                        SerializerUtils::getRelations(),
                        [
                            'build_default_payment_gateway_profile_strategy' => $this->build_default_payment_gateway_profile_strategy
                        ])
            );
        });
    }

    /**
     * @param $id
     * @return mixed
     */
    public function getAllSummitByIdOrSlugRegistrationStats($id)
    {
        return $this->processRequest(function () use ($id) {
            $summit = $this->repository->getById(intval($id));
            if (is_null($summit))
                $summit = $this->repository->getBySlug(trim($id));

            if (is_null($summit)) return $this->error404();

            $current_member = $this->resource_server_context->getCurrentUser();
            if (!is_null($current_member) && !$current_member->isAdmin() && !$current_member->hasPermissionFor($summit))
                return $this->error403(['message' => sprintf("Member %s has not permission for this Summit", $current_member->getId())]);

            $filter = null;

            if (Request::has('filter')) {
                $filter = FilterParser::parse(Request::get('filter'), [
                    'start_date' => [ '>='],
                    'end_date' => ['<='],
                ]);
            }

            if (is_null($filter)) $filter = new Filter();

            $filter->validate([
                'start_date' => 'sometimes|required|date_format:U',
                'end_date' => 'sometimes|required_with:start_date|date_format:U|after:start_date',
            ]);

            return $this->ok
            (
                SerializerRegistry::getInstance()
                    ->getSerializer($summit, SerializerRegistry::SerializerType_Admin_Registration_Stats)
                    ->serialize
                    (
                        SerializerUtils::getExpand(),
                        SerializerUtils::getFields(),
                        SerializerUtils::getRelations(),
                        [ 'filter' => $filter ]
                    )
            );
        });
    }

    use GetAndValidateJsonPayload;

    /**
     * @return mixed
     */
    public function addSummit()
    {
        return $this->processRequest(function () {

            $payload = $this->getJsonPayload(SummitValidationRulesFactory::buildForAdd(), true, [
                'slug.required' => 'A Slug is required.',
                'schedule_start_date.before_or_equal' => 'Show on schedule page needs to be after the start of the Show And Before of the Show End.',
            ]);

            $summit = $this->summit_service->addSummit($payload);
            $serializer_type = $this->serializer_type_selector->getSerializerType();
            return $this->created(SerializerRegistry::getInstance()->getSerializer($summit, $serializer_type)->serialize
            (
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));
        });
    }

    /**
     * @param $summit_id
     * @return mixed
     */
    public function updateSummit($summit_id)
    {
        return $this->processRequest(function () use ($summit_id) {

            $payload = $this->getJsonPayload(SummitValidationRulesFactory::buildForUpdate(), true);

            $summit = SummitFinderStrategyFactory::build($this->repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $current_member = $this->resource_server_context->getCurrentUser();
            if (!is_null($current_member) && !$current_member->isAdmin() && !$current_member->hasPermissionForOnGroup($summit, IGroup::SummitAdministrators))
                return $this->error403(['message' => sprintf("Member %s has not permission for this Summit", $current_member->getId())]);

            $summit = $this->summit_service->updateSummit($summit_id, $payload);
            $serializer_type = $this->serializer_type_selector->getSerializerType();
            return $this->updated(SerializerRegistry::getInstance()
                ->getSerializer($summit, $serializer_type)
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
     * @return mixed
     */
    public function deleteSummit($summit_id)
    {
        return $this->processRequest(function () use ($summit_id) {

            $this->summit_service->deleteSummit($summit_id);

            return $this->deleted();
        });
    }


    /**
     * @param $summit_id
     * @param $external_order_id
     * @return mixed
     */
    public function getExternalOrder($summit_id, $external_order_id)
    {
        return $this->processRequest(function () use ($summit_id, $external_order_id) {
            $summit = SummitFinderStrategyFactory::build($this->repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();
            $order = $this->summit_service->getExternalOrder($summit, $external_order_id);
            return $this->ok($order);
        });
    }

    /**
     * @param $summit_id
     * @param $external_order_id
     * @param $external_attendee_id
     * @return mixed
     */
    public function confirmExternalOrderAttendee($summit_id, $external_order_id, $external_attendee_id)
    {
        return $this->processRequest(function () use ($summit_id, $external_order_id, $external_attendee_id) {

            $summit = SummitFinderStrategyFactory::build($this->repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();
            $current_member = $this->resource_server_context->getCurrentUser();
            if (is_null($current_member)) throw new \HTTP401UnauthorizedException;

            $attendee = $this->summit_service->confirmExternalOrderAttendee
            (
                new ConfirmationExternalOrderRequest
                (
                    $summit,
                    $current_member->getId(),
                    trim($external_order_id),
                    trim($external_attendee_id)
                )
            );

            return $this->ok(SerializerRegistry::getInstance()->getSerializer($attendee)->serialize
            (
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));
        });
    }

    /**
     * @return ISummitRepository
     */
    protected function getSummitRepository(): ISummitRepository
    {
        return $this->repository;
    }

    /**
     * @param LaravelRequest $request
     * @param $summit_id
     * @return JsonResponse|mixed
     */
    public function addSummitLogo(LaravelRequest $request, $summit_id)
    {
        return $this->processRequest(function () use ($request, $summit_id) {

            $summit = SummitFinderStrategyFactory::build($this->repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $file = $request->file('file');
            if (is_null($file)) {
                return $this->error412(array('file param not set!'));
            }

            $current_member = $this->resource_server_context->getCurrentUser();
            if (!is_null($current_member) && !$current_member->isAdmin() && !$current_member->hasPermissionForOnGroup($summit, IGroup::SummitAdministrators))
                return $this->error403(['message' => sprintf("Member %s has not permission for this Summit", $current_member->getId())]);

            $photo = $this->summit_service->addSummitLogo($summit_id, $file);

            return $this->created(SerializerRegistry::getInstance()->getSerializer($photo)->serialize
            (
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));

        });
    }

    /**
     * @param $summit_id
     * @return JsonResponse|mixed
     */
    public function deleteSummitLogo($summit_id)
    {
       return $this->processRequest(function() use($summit_id){

            $summit = SummitFinderStrategyFactory::build($this->repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $current_member = $this->resource_server_context->getCurrentUser();
            if (!is_null($current_member) && !$current_member->isAdmin() && !$current_member->hasPermissionForOnGroup($summit, IGroup::SummitAdministrators))
                return $this->error403(['message' => sprintf("Member %s has not permission for this Summit", $current_member->getId())]);

            $this->summit_service->deleteSummitLogo($summit_id);

            return $this->deleted();

        });
    }

    /**
     * @param $summit_id
     * @param $speaker_id
     * @return JsonResponse|mixed
     */
    public function addFeatureSpeaker($summit_id, $speaker_id)
    {
       return $this->processRequest(function () use($summit_id, $speaker_id){

            $this->summit_service->addFeaturedSpeaker(intval($summit_id), intval($speaker_id));

            return $this->updated();

        });
    }

    /**
     * @param $summit_id
     * @param $speaker_id
     * @return JsonResponse|mixed
     */
    public function updateFeatureSpeaker($summit_id, $speaker_id)
    {
       return $this->processRequest(function() use($summit_id, $speaker_id){

            $payload = $this->getJsonPayload([
                'order' => 'required|integer|min:1',
            ], true);

            $this->summit_service->updateFeaturedSpeaker(intval($summit_id), intval($speaker_id), $payload);

            return $this->updated();

        });
    }

    /**
     * @param $summit_id
     * @param $speaker_id
     * @return JsonResponse|mixed
     */
    public function removeFeatureSpeaker($summit_id, $speaker_id)
    {
        return $this->processRequest(function() use($summit_id, $speaker_id){

            $this->summit_service->removeFeaturedSpeaker(intval($summit_id), intval($speaker_id));

            return $this->deleted();

        });
    }

    /**
     * @param $summit_id
     * @return JsonResponse|mixed
     */
    public function getAllFeatureSpeaker($summit_id)
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
                    'order',
                ];
            },
            function ($filter) use ($summit) {
                return $filter;
            },
            function () {
                return $this->serializer_type_selector->getSerializerType();
            },
            null,
            null,
            function ($page, $per_page, $filter, $order, $applyExtraFilters) use ($summit) {
                return $this->speaker_repository->getFeaturedSpeakers
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

}