<?php namespace App\Http\Controllers;
/**
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

use App\ModelSerializers\IMemberSerializerTypes;
use Illuminate\Support\Facades\Request;
use models\main\IMemberRepository;
use models\oauth2\IResourceServerContext;
use models\summit\ISummitRepository;
use services\model\ISubmitterService;
use utils\Filter;
use utils\FilterParser;
use utils\PagingInfo;

/**
 * Class OAuth2SummitSubmittersApiController
 * @package App\Http\Controllers
 */
final class OAuth2SummitSubmittersApiController
    extends OAuth2ProtectedController
{
    use ParametrizedGetAll;

    use GetAndValidateJsonPayload;

    use RequestProcessor;

    /**
     * @var ISubmitterService
     */
    private $service;

    /**
     * @var ISummitRepository
     */
    private $summit_repository;

    /**
     * OAuth2SummitSubmittersApiController constructor.
     * @param IMemberRepository $repository
     * @param ISummitRepository $summit_repository
     * @param ISubmitterService $service
     * @param IResourceServerContext $resource_server_context
     */
    public function __construct
    (
        IMemberRepository      $repository,
        ISummitRepository      $summit_repository,
        ISubmitterService      $service,
        IResourceServerContext $resource_server_context
    )
    {
        parent::__construct($resource_server_context);
        $this->repository = $repository;
        $this->summit_repository = $summit_repository;
        $this->service = $service;
    }

    /**
     * @param $summit_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function getAllBySummit($summit_id)
    {
        $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->getResourceServerContext())->find(intval($summit_id));
        if (is_null($summit)) return $this->error404();

        return $this->_getAll(
            function () {
                return [
                    'id' => ['=='],
                    'not_id' => ['=='],
                    'first_name' => ['=@', '@@', '=='],
                    'last_name' => ['=@', '@@', '=='],
                    'email' => ['=@', '@@', '=='],
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
                    'is_speaker' => ['=='],
                    'has_media_upload_with_type' => ['=='],
                    'has_not_media_upload_with_type' => ['=='],
                ];
            },
            function () {
                return [
                    'id' => 'sometimes|integer',
                    'not_id' => 'sometimes|integer',
                    'first_name' => 'sometimes|string',
                    'last_name' => 'sometimes|string',
                    'email' => 'sometimes|string',
                    'full_name' => 'sometimes|string',
                    'member_id' => 'sometimes|integer',
                    'member_user_external_id' => 'sometimes|integer',
                    'has_accepted_presentations' => 'sometimes|string|in:true,false',
                    'has_alternate_presentations' => 'sometimes|string|in:true,false',
                    'has_rejected_presentations' => 'sometimes|string|in:true,false',
                    'presentations_track_id' => 'sometimes|integer',
                    'presentations_selection_plan_id' => 'sometimes|integer',
                    'presentations_type_id' => 'sometimes|integer',
                    'presentations_title' => 'sometimes|string',
                    'presentations_abstract' => 'sometimes|string',
                    'presentations_submitter_full_name' => 'sometimes|string',
                    'presentations_submitter_email' => 'sometimes|string',
                    'is_speaker' => 'sometimes|string|in:true,false',
                    'has_media_upload_with_type' => 'sometimes|integer',
                    'has_not_media_upload_with_type' => 'sometimes|integer',
                ];
            },
            function () {
                return [
                    'id',
                    'first_name',
                    'last_name',
                    'full_name',
                    'email',
                    'created',
                    'last_edited',
                ];
            },
            function ($filter) use ($summit) {
                return $filter;
            },
            function () {
                return IMemberSerializerTypes::Submitter;
            },
            null,
            null,
            function ($page, $per_page, $filter, $order, $applyExtraFilters) use ($summit) {
                return $this->repository->getSubmittersBySummit
                (
                    $summit,
                    new PagingInfo($page, $per_page),
                    call_user_func($applyExtraFilters, $filter),
                    $order
                );
            },
            ["summit" => $summit]
        );
    }

    /**
     * @param $summit_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function getAllBySummitCSV($summit_id)
    {
        $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->getResourceServerContext())->find(intval($summit_id));
        if (is_null($summit)) return $this->error404();

        return $this->_getAllCSV(
            function () {
                return [
                    'id' => ['=='],
                    'not_id' => ['=='],
                    'first_name' => ['=@', '@@', '=='],
                    'last_name' => ['=@', '@@', '=='],
                    'email' => ['=@', '@@', '=='],
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
                    'is_speaker' => ['=='],
                    'has_media_upload_with_type' => ['=='],
                    'has_not_media_upload_with_type' => ['=='],
                ];
            },
            function () {
                return [
                    'id' => 'sometimes|integer',
                    'not_id' => 'sometimes|integer',
                    'first_name' => 'sometimes|string',
                    'last_name' => 'sometimes|string',
                    'email' => 'sometimes|string',
                    'full_name' => 'sometimes|string',
                    'member_id' => 'sometimes|integer',
                    'member_user_external_id' => 'sometimes|integer',
                    'has_accepted_presentations' => 'sometimes|string|in:true,false',
                    'has_alternate_presentations' => 'sometimes|string|in:true,false',
                    'has_rejected_presentations' => 'sometimes|string|in:true,false',
                    'presentations_track_id' => 'sometimes|integer',
                    'presentations_selection_plan_id' => 'sometimes|integer',
                    'presentations_type_id' => 'sometimes|integer',
                    'presentations_title' => 'sometimes|string',
                    'presentations_abstract' => 'sometimes|string',
                    'presentations_submitter_full_name' => 'sometimes|string',
                    'presentations_submitter_email' => 'sometimes|string',
                    'is_speaker' => 'sometimes|string|in:true,false',
                    'has_media_upload_with_type' => 'sometimes|integer',
                    'has_not_media_upload_with_type' => 'sometimes|integer',
                ];
            },
            function () {
                return [
                    'id',
                    'first_name',
                    'last_name',
                    'full_name',
                    'email',
                    'created',
                    'last_edited',
                ];
            },
            function ($filter) use ($summit) {
                return $filter;
            },
            function () {
                return IMemberSerializerTypes::SubmitterCSV;
            },
            function () {
                return [];
            },
            function () {
                return [];
            },
            'submitters-',
            ["summit" => $summit],
            function ($page, $per_page, $filter, $order, $applyExtraFilters) use ($summit) {
                return $this->repository->getSubmittersBySummit
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
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function send($summit_id)
    {
        return $this->processRequest(function () use ($summit_id) {

            if (!Request::isJson()) return $this->error400();

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->getResourceServerContext())->find(intval($summit_id));
            if (is_null($summit)) return $this->error404();

            $payload = $this->getJsonPayload(SummitSubmittersEmailsValidationRulesFactory::buildForAdd());

            $filter = null;

            if (Request::has('filter')) {
                $filter = FilterParser::parse(Request::input('filter'), [
                    'id' => ['=='],
                    'not_id' => ['=='],
                    'first_name' => ['=@', '@@', '=='],
                    'last_name' => ['=@', '@@', '=='],
                    'email' => ['=@', '@@', '=='],
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
                    'is_speaker' => ['=='],
                    'has_media_upload_with_type' => ['=='],
                    'has_not_media_upload_with_type' => ['=='],
                ]);
            }

            if (is_null($filter))
                $filter = new Filter();

            $filter->validate([
                'id' => 'sometimes|integer',
                'not_id' => 'sometimes|integer',
                'first_name' => 'sometimes|string',
                'last_name' => 'sometimes|string',
                'email' => 'sometimes|string',
                'full_name' => 'sometimes|string',
                'member_id' => 'sometimes|integer',
                'member_user_external_id' => 'sometimes|integer',
                'has_accepted_presentations' => 'sometimes|string|in:true,false',
                'has_alternate_presentations' => 'sometimes|string|in:true,false',
                'has_rejected_presentations' => 'sometimes|string|in:true,false',
                'presentations_track_id' => 'sometimes|integer',
                'presentations_selection_plan_id' => 'sometimes|integer',
                'presentations_type_id' => 'sometimes|integer',
                'presentations_title' => 'sometimes|string',
                'presentations_abstract' => 'sometimes|string',
                'presentations_submitter_full_name' => 'sometimes|string',
                'presentations_submitter_email' => 'sometimes|string',
                'is_speaker' => 'sometimes|string|in:true,false',
                'has_media_upload_with_type' => 'sometimes|integer',
                'has_not_media_upload_with_type' => 'sometimes|integer',
            ]);

            $this->service->triggerSendEmails($summit, $payload, Request::input('filter', null));

            return $this->ok();
        });
    }
}