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
use App\Jobs\Emails\Registration\Invitations\InviteSummitRegistrationEmail;
use App\Jobs\Emails\Registration\Invitations\ReInviteSummitRegistrationEmail;
use App\Models\Foundation\Summit\Repositories\ISummitRegistrationInvitationRepository;
use App\ModelSerializers\SerializerUtils;
use App\Services\Model\ISummitRegistrationInvitationService;
use Illuminate\Http\Request as LaravelRequest;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Validator;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use models\oauth2\IResourceServerContext;
use models\summit\ISummitRepository;
use models\summit\Summit;
use models\summit\SummitRegistrationInvitation;
use models\utils\IEntity;
use ModelSerializers\SerializerRegistry;
use utils\Filter;
use utils\FilterElement;
use utils\FilterParser;

/**
 * Class OAuth2SummitRegistrationInvitationApiController
 * @package App\Http\Controllers
 */
final class OAuth2SummitRegistrationInvitationApiController extends OAuth2ProtectedController
{
    use RequestProcessor;

    /**
     * @var ISummitRepository
     */
    private $summit_repository;

    /**
     * @var ISummitRegistrationInvitationService
     */
    private $service;

    /**
     * OAuth2SummitRegistrationInvitationApiController constructor.
     * @param ISummitRepository $summit_repository
     * @param ISummitRegistrationInvitationRepository $repository
     * @param ISummitRegistrationInvitationService $service
     * @param IResourceServerContext $resource_server_context
     */
    public function __construct
    (
        ISummitRepository                       $summit_repository,
        ISummitRegistrationInvitationRepository $repository,
        ISummitRegistrationInvitationService    $service,
        IResourceServerContext                  $resource_server_context
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


            $payload = $request->all();

            $rules = [
                'file' => 'required',
                'acceptance_criteria' => sprintf('sometimes|string|in:%s', implode(',', SummitRegistrationInvitation::AllowedAcceptanceCriteria)),
            ];
            // Creates a Validator instance and validates the data.
            $validation = Validator::make($payload, $rules);

            if ($validation->fails()) {
                $ex = new ValidationException;
                $ex->setMessages($validation->messages()->toArray());
                throw $ex;
            }

            $file = $request->file('file');

            $this->service->importInvitationData($summit, $file, $payload);
            return $this->ok();
        });
    }

    /**
     * @param $token
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function getInvitationByToken($token)
    {
        return $this->processRequest(function () use ($token) {
            $current_member = $this->resource_server_context->getCurrentUser();
            if (is_null($current_member)) return $this->error403();

            $invitation = $this->service->getInvitationByToken($current_member, $token);

            return $this->ok(SerializerRegistry::getInstance()->getSerializer($invitation)->serialize
            (
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));
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
        return $summit->getSummitRegistrationInvitationById($child_id);
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
                    'id' => ['=='],
                    'not_id' => ['=='],
                    'email' => ['@@','=@', '=='],
                    'first_name' => ['@@','=@', '=='],
                    'last_name' => ['@@','=@', '=='],
                    'full_name' => ['@@','=@', '=='],
                    'is_accepted' => ['=='],
                    'is_sent' => ['=='],
                    'ticket_types_id' => ['=='],
                    'tags' => ['@@','=@', '=='],
                    'tags_id' => ['=='],
                    'status' => ['=='],
                ];
            },
            function () {
                return [
                    'id' => 'sometimes|integer',
                    'not_id' => 'sometimes|integer',
                    'email' => 'sometimes|required|string',
                    'first_name' => 'sometimes|required|string',
                    'last_name' => 'sometimes|required|string',
                    'full_name' => 'sometimes|required|string',
                    'is_accepted' => 'sometimes|required|string|in:true,false',
                    'is_sent' => 'sometimes|required|string|in:true,false',
                    'ticket_types_id' => 'sometimes|integer',
                    'tags' => 'sometimes|required|string',
                    'tags_id' => 'sometimes|integer',
                    'status' => 'sometimes|required|string|in:'.join(",", SummitRegistrationInvitation::AllowedStatus),
                ];
            },
            function () {
                return [
                    'id',
                    'email',
                    'first_name',
                    'last_name',
                    'full_name',
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
                    'id' => ['=='],
                    'not_id' => ['=='],
                    'email' =>  ['@@','=@', '=='],
                    'first_name' =>  ['@@','=@', '=='],
                    'full_name' => ['@@','=@', '=='],
                    'last_name' => ['@@','=@', '=='],
                    'is_accepted' => ['=='],
                    'is_sent' => ['=='],
                    'ticket_types_id' => ['=='],
                    'tags' => ['@@','=@', '=='],
                    'tags_id' => ['=='],
                    'status' => ['=='],
                ];
            },
            function () {
                return [
                    'id' => 'sometimes|integer',
                    'not_id' => 'sometimes|integer',
                    'email' => 'sometimes|required|string',
                    'first_name' => 'sometimes|required|string',
                    'last_name' => 'sometimes|required|string',
                    'full_name' => 'sometimes|required|string',
                    'is_accepted' => 'sometimes|required|string|in:true,false',
                    'is_sent' => 'sometimes|required|string|in:true,false',
                    'ticket_types_id' => 'sometimes|integer',
                    'tags' => 'sometimes|required|string',
                    'tags_id' => 'sometimes|integer',
                    'status' => 'sometimes|required|string|in:'.join(",", SummitRegistrationInvitation::AllowedStatus),
                ];
            },
            function () {
                return [
                    'id',
                    'email',
                    'first_name',
                    'last_name',
                    'full_name',
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
                    'accepted_date' => new EpochCellFormatter(),
                    'is_accepted' => new BooleanCellFormatter(),
                    'is_sent' => new BooleanCellFormatter(),
                ];
            },
            function () {

                $allowed_columns = [
                    'id',
                    'email',
                    'first_name',
                    'last_name',
                    'member_id',
                    'order_id',
                    'summit_id',
                    'accepted_date',
                    'is_accepted',
                    'is_sent',
                    'allowed_ticket_types',
                    'tags',
                    'status',
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
            'summit-registration-invitations-'
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
        return SummitRegistrationInvitationValidationRulesFactory::buildForAdd($payload);
    }

    use UpdateSummitChildElement;

    /**
     * @inheritDoc
     */
    function getUpdateValidationRules(array $payload): array
    {
        return SummitRegistrationInvitationValidationRulesFactory::buildForUpdate($payload);
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
                        InviteSummitRegistrationEmail::EVENT_SLUG,
                        ReInviteSummitRegistrationEmail::EVENT_SLUG,
                    ]),
                'invitations_ids' => 'sometimes|int_array',
                'excluded_invitations_ids' => 'sometimes|int_array',
                'test_email_recipient' => 'sometimes|email',
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
                    'id' => ['=='],
                    'not_id' => ['=='],
                    'email' => ['@@','=@', '=='],
                    'first_name' =>['@@','=@', '=='],
                    'last_name' => ['@@','=@', '=='],
                    'full_name' => ['@@','=@', '=='],
                    'is_accepted' => ['=='],
                    'is_sent' => ['=='],
                    'ticket_types_id' => ['=='],
                    'tags' => ['@@','=@', '=='],
                    'tags_id' => ['=='],
                    'status' => ['=='],
                ]);
            }

            if (is_null($filter))
                $filter = new Filter();

            $filter->validate([
                'id' => 'sometimes|integer',
                'not_id' => 'sometimes|integer',
                'is_accepted' => 'sometimes|required|string|in:true,false',
                'is_sent' => 'sometimes|required|string|in:true,false',
                'email' => 'sometimes|required|string',
                'first_name' => 'sometimes|required|string',
                'last_name' => 'sometimes|required|string',
                'full_name' =>'sometimes|required|string',
                'ticket_types_id' => 'sometimes|integer',
                'tags' => 'sometimes|required|string',
                'tags_id' => 'sometimes|integer',
                'status' => 'sometimes|required|string|in:'.join(",", SummitRegistrationInvitation::AllowedStatus),
            ]);

            $this->service->triggerSend($summit, $payload, Request::input('filter'));

            return $this->ok();

        });
    }

    /**
     * @param $summit_id
     * @return mixed
     */
    function getMyInvitation($summit_id)
    {

        return $this->processRequest(function () use ($summit_id) {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->getResourceServerContext())->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $current_member = $this->resource_server_context->getCurrentUser();
            if (is_null($current_member)) return $this->error403();

            $invitation = $this->service->getInvitationByEmail($summit, $current_member->getEmail());

            if (is_null($invitation))
                throw new EntityNotFoundException();

            return $this->ok(SerializerRegistry::getInstance()->getSerializer($invitation)->serialize
            (
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations(),
            ));
        });
    }
}
