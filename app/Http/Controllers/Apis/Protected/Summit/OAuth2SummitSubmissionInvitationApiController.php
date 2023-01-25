<?php namespace App\Http\Controllers;
/**
 * Copyright 2020 OpenStack Foundation
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
use App\Jobs\Emails\PresentationSubmissions\Invitations\InviteSubmissionEmail;
use App\Jobs\Emails\PresentationSubmissions\Invitations\ReInviteSubmissionEmail;
use App\Models\Foundation\Summit\Repositories\ISummitSubmissionInvitationRepository;
use App\Services\Model\ISummitSubmissionInvitationService;
use Illuminate\Http\Request as LaravelRequest;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Validator;
use models\exceptions\ValidationException;
use models\oauth2\IResourceServerContext;
use models\summit\ISummitRepository;
use models\summit\Summit;
use models\utils\IEntity;
use ModelSerializers\SerializerRegistry;
use utils\Filter;
use utils\FilterElement;
use utils\FilterParser;

/**
 * Class OAuth2SummitSubmissionInvitationApiController
 * @package App\Http\Controllers
 */
final class OAuth2SummitSubmissionInvitationApiController extends OAuth2ProtectedController
{
    use RequestProcessor;

    /**
     * @var ISummitRepository
     */
    private $summit_repository;

    /**
     * @var ISummitSubmissionInvitationService
     */
    private $service;

    /**
     * OAuth2SummitRegistrationInvitationApiController constructor.
     * @param ISummitRepository $summit_repository
     * @param ISummitSubmissionInvitationRepository $repository
     * @param ISummitSubmissionInvitationService $service
     * @param IResourceServerContext $resource_server_context
     */
    public function __construct
    (
        ISummitRepository                     $summit_repository,
        ISummitSubmissionInvitationRepository $repository,
        ISummitSubmissionInvitationService    $service,
        IResourceServerContext                $resource_server_context
    )
    {
        parent::__construct($resource_server_context);
        $this->repository = $repository;
        $this->summit_repository = $summit_repository;
        $this->service = $service;
    }

    /**
     * @param LaravelRequest $request
     * @param $summit_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function ingestInvitations(LaravelRequest $request, $summit_id)
    {
        return $this->processRequest(function () use ($request, $summit_id) {
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->getResourceServerContext())->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $current_member = $this->resource_server_context->getCurrentUser();
            if (is_null($current_member)) return $this->error403();

            $file = $request->file('file');
            if (is_null($file)) {
                return $this->error412(array('file param not set!'));
            }

            $this->service->importInvitationData($summit, $file);
            return $this->ok();
        });
    }

    // traits
    use ParametrizedGetAll;

    use GetSummitChildElementById;

    /**
     * @return ISummitRepository
     */
    protected function getSummitRepository(): ISummitRepository
    {
        return $this->summit_repository;
    }

    /**
     * @inheritDoc
     */
    protected function getChildFromSummit(Summit $summit, $child_id): ?IEntity
    {
        return $summit->getSubmissionInvitationById($child_id);
    }

    /**
     * @param $summit_id
     * @return mixed
     */
    public function getAllBySummit($summit_id)
    {

        $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->getResourceServerContext())->find($summit_id);
        if (is_null($summit)) return $this->error404();

        return $this->_getAll(
            function () {
                return [
                    'email' => ['@@', '=@', '=='],
                    'first_name' => ['@@', '=@', '=='],
                    'last_name' => ['@@', '=@', '=='],
                    'is_sent' => ['=='],
                    'ticket_types_id' => ['=='],
                    'tags' => ['@@', '=@', '=='],
                    'tags_id' => ['=='],
                ];
            },
            function () {
                return [
                    'email' => 'sometimes|required|string',
                    'first_name' => 'sometimes|required|string',
                    'last_name' => 'sometimes|required|string',
                    'is_sent' => 'sometimes|required|string|in:true,false',
                    'tags' => 'sometimes|required|string',
                    'tags_id' => 'sometimes|integer',
                ];
            },
            function () {
                return [
                    'id',
                    'email',
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
    public function getAllBySummitCSV($summit_id)
    {

        $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->getResourceServerContext())->find($summit_id);
        if (is_null($summit)) return $this->error404();

        return $this->_getAllCSV(
            function () {
                return [
                    'email' => ['@@', '=@', '=='],
                    'first_name' => ['@@', '=@', '=='],
                    'last_name' => ['@@', '=@', '=='],
                    'is_sent' => ['=='],
                    'tags' => ['@@', '=@', '=='],
                    'tags_id' => ['=='],
                ];
            },
            function () {
                return [
                    'email' => 'sometimes|required|string',
                    'first_name' => 'sometimes|required|string',
                    'last_name' => 'sometimes|required|string',
                    'is_sent' => 'sometimes|required|string|in:true,false',
                    'tags' => 'sometimes|required|string',
                    'tags_id' => 'sometimes|integer',
                ];
            },
            function () {
                return [
                    'id',
                    'email',
                ];
            },
            function ($filter) use ($summit) {
                if ($filter instanceof Filter) {
                    $filter->addFilterCondition(FilterElement::makeEqual('summit_id', $summit->getId()));
                }
                return $filter;
            },
            function () {
                return SerializerRegistry::SerializerType_CSV;
            },
            function () {
                return [
                    'sent_date' => new EpochCellFormatter(),
                    'is_sent' => new BooleanCellFormatter(),
                ];
            },
            function () {

                $allowed_columns = [
                    'id',
                    'email',
                    'first_name',
                    'last_name',
                    'speaker_id',
                    'summit_id',
                    'is_sent',
                    'sent_date',
                    'tags',
                ];

                $columns_param = Request::input("columns", "");
                $columns = [];
                if (!empty($columns_param))
                    $columns = explode(',', $columns_param);
                $diff = array_diff($columns, $allowed_columns);
                if (count($diff) > 0) {
                    throw new ValidationException(sprintf("columns %s are not allowed!", implode(",", $diff)));
                }
                if (empty($columns))
                    $columns = $allowed_columns;
                return $columns;
            },
            'summit-submission-invitations-'
        );
    }


    use DeleteSummitChildElement;

    /**
     * @inheritDoc
     */
    protected function deleteChild(Summit $summit, $child_id): void
    {
        $this->service->delete($summit, $child_id);
    }

    use AddSummitChildElement;

    /**
     * @inheritDoc
     */
    protected function addChild(Summit $summit, array $payload): IEntity
    {
        return $this->service->add($summit, $payload);
    }

    /**
     * @inheritDoc
     */
    function getAddValidationRules(array $payload): array
    {
        return SummitSubmissionInvitationValidationRulesFactory::buildForAdd($payload);
    }

    use UpdateSummitChildElement;

    /**
     * @inheritDoc
     */
    function getUpdateValidationRules(array $payload): array
    {
        return SummitSubmissionInvitationValidationRulesFactory::buildForUpdate($payload);
    }

    /**
     * @inheritDoc
     */
    protected function updateChild(Summit $summit, int $child_id, array $payload): IEntity
    {
        return $this->service->update($summit, $child_id, $payload);
    }

    /**
     * @param $summit_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function deleteAll($summit_id)
    {
        return $this->processRequest(function () use ($summit_id) {

            $summit = SummitFinderStrategyFactory::build($this->getSummitRepository(), $this->getResourceServerContext())->find($summit_id);
            if (is_null($summit)) return $this->error404();
            $this->service->deleteAll($summit);
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
            $data = Request::json();

            $summit = SummitFinderStrategyFactory::build($this->getSummitRepository(), $this->getResourceServerContext())->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $payload = $data->all();

            // Creates a Validator instance and validates the data.
            $validation = Validator::make($payload, [
                'email_flow_event' => 'required|string|in:' . join(',', [
                        InviteSubmissionEmail::EVENT_SLUG,
                        ReInviteSubmissionEmail::EVENT_SLUG,
                    ]),
                'selection_plan_id' => 'sometimes|integer',
                'invitations_ids' => 'sometimes|int_array',
            ]);

            if ($validation->fails()) {
                $messages = $validation->messages()->toArray();

                return $this->error412
                (
                    $messages
                );
            }

            $filter = null;

            if (Request::has('filter')) {
                $filter = FilterParser::parse(Request::input('filter'), [
                    'email' => ['@@', '=@', '=='],
                    'first_name' => ['@@', '=@', '=='],
                    'last_name' => ['@@', '=@', '=='],
                    'is_sent' => ['=='],
                    'tags' => ['@@', '=@', '=='],
                    'tags_id' => ['=='],
                ]);
            }

            if (is_null($filter))
                $filter = new Filter();

            $filter->validate([
                'is_sent' => 'sometimes|required|string|in:true,false',
                'email' => 'sometimes|required|string',
                'first_name' => 'sometimes|required|string',
                'last_name' => 'sometimes|required|string',
                'tags' => 'sometimes|required|string',
                'tags_id' => 'sometimes|integer',
            ]);

            $this->service->triggerSend($summit, $payload, Request::input('filter'));

            return $this->ok();

        });
    }

}
