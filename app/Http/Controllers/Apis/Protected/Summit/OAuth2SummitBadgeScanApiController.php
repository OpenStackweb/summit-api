<?php namespace App\Http\Controllers;
/**
 * Copyright 2019 OpenStack Foundation
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
use App\Services\Model\IAttendeeService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Validator;
use models\exceptions\EntityNotFoundException;
use models\summit\ISponsorUserInfoGrantRepository;
use models\exceptions\ValidationException;
use models\oauth2\IResourceServerContext;
use App\Services\Model\ISponsorUserInfoGrantService;
use models\summit\ISummitRepository;
use models\summit\Summit;
use models\summit\SummitOrderExtraQuestionTypeConstants;
use models\utils\IEntity;
use ModelSerializers\SerializerRegistry;
use utils\Filter;
use utils\FilterElement;
use Exception;
/**
 * Class OAuth2SummitBadgeScanApiController
 * @package App\Http\Controllers
 */
final class OAuth2SummitBadgeScanApiController
    extends OAuth2ProtectedController
{
    /**
     * @var ISponsorUserInfoGrantService
     */
    private $service;

    /**
     * @var IAttendeeService
     */
    private $attendee_service;

    /**
     * @var ISummitRepository
     */
    private $summit_repository;

    /**
     * OAuth2SummitBadgeScanApiController constructor.
     * @param ISponsorUserInfoGrantRepository $repository
     * @param ISummitRepository $summit_repository
     * @param IResourceServerContext $resource_server_context
     * @param ISponsorUserInfoGrantService $service
     * @param IAttendeeService $attendee_service
     */
    public function __construct
    (
        ISponsorUserInfoGrantRepository $repository,
        ISummitRepository $summit_repository,
        IResourceServerContext $resource_server_context,
        ISponsorUserInfoGrantService $service,
        IAttendeeService $attendee_service
    )
    {
        parent::__construct($resource_server_context);
        $this->repository = $repository;
        $this->summit_repository = $summit_repository;
        $this->service = $service;
        $this->attendee_service = $attendee_service;
    }

    use AddSummitChildElement;

    use RequestProcessor;

    use GetAndValidateJsonPayload;

    /**
     * @param array $payload
     * @return array
     */
    function getAddValidationRules(array $payload): array
    {
        return [
            'qr_code'   => 'required|string',
            'scan_date' => 'required|date_format:U',
            'notes' => 'sometimes|string|max:1024',
        ];
    }

    /**
     * @return array
     */
    function getCheckInValidationRules(): array
    {
        return [
            'qr_code'   => 'required|string',
        ];
    }

    /**
     * @param Summit $summit
     * @param array $payload
     * @return IEntity
     */
    protected function addChild(Summit $summit, array $payload): IEntity
    {
        $current_member = $this->resource_server_context->getCurrentUser();
        if (is_null($current_member)) throw new HTTP403ForbiddenException();

        return $this->service->addBadgeScan($summit, $current_member, $payload);
    }

    use UpdateSummitChildElement;

    function getUpdateValidationRules(array $payload): array{
        return [
            'notes' => 'sometimes|string|max:1024',
        ];
    }

    /**
     * @param Summit $summit
     * @param int $child_id
     * @param array $payload
     * @return IEntity
     * @throws EntityNotFoundException
     * @throws HTTP403ForbiddenException
     * @throws ValidationException
     */
    protected function updateChild(Summit $summit,int $child_id, array $payload):IEntity{
        $current_member = $this->resource_server_context->getCurrentUser();
        if (is_null($current_member)) throw new HTTP403ForbiddenException();

        return $this->service->updateBadgeScan($summit, $current_member, $child_id, $payload);
    }

    /**
     * @param $summit_id
     * @param $sponsor_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function addGrant($summit_id, $sponsor_id){
        try{
            $summit = SummitFinderStrategyFactory::build($this->getSummitRepository(), $this->getResourceServerContext())->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $current_member = $this->resource_server_context->getCurrentUser();
            if (is_null($current_member)) throw new HTTP403ForbiddenException();

            $grant = $this->service->addGrant($summit, intval($sponsor_id), $current_member);
            return $this->created(SerializerRegistry::getInstance()->getSerializer
            (
                $grant,
                $this->addSerializerType()
            )->serialize(Request::input('expand', '')));
        }
        catch (ValidationException $ex) {
            Log::warning($ex);
            return $this->error412(array($ex->getMessage()));
        }
        catch(EntityNotFoundException $ex)
        {
            Log::warning($ex);
            return $this->error404(array('message'=> $ex->getMessage()));
        }
        catch (\HTTP401UnauthorizedException $ex) {
            Log::warning($ex);
            return $this->error401();
        }
        catch (HTTP403ForbiddenException $ex) {
            Log::warning($ex);
            return $this->error403();
        }
        catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    /**
     * @return ISummitRepository
     */
    protected function getSummitRepository(): ISummitRepository
    {
        return $this->summit_repository;
    }

    // traits
    use ParametrizedGetAll;

    /**
     * @param $summit_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function getAllMyBadgeScans($summit_id){
        $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->getResourceServerContext())->find($summit_id);
        if (is_null($summit)) return $this->error404();

        $current_member = $this->resource_server_context->getCurrentUser();
        if (is_null($current_member)) return $this->error403();

        return $this->_getAll(
            function(){
                return [
                    'attendee_first_name'        => ['=@', '=='],
                    'attendee_last_name'         => ['=@', '=='],
                    'attendee_full_name'         => ['=@', '=='],
                    'attendee_email'             => ['=@', '=='],
                    'ticket_number'              => ['=@', '=='],
                    'order_number'               => ['=@', '=='],
                ];
            },
            function(){
                return [
                    'attendee_first_name'      => 'sometimes|string',
                    'attendee_last_name'       => 'sometimes|string',
                    'attendee_full_name'       => 'sometimes|string',
                    'attendee_email'           => 'sometimes|string',
                    'ticket_number'            => 'sometimes|string',
                    'order_number'             => 'sometimes|string',
                ];
            },
            function()
            {
                return [
                    'id',
                    'scan_date'
                ];
            },
            function($filter) use($summit, $current_member){
                if($filter instanceof Filter){
                    $filter->addFilterCondition(FilterElement::makeEqual('summit_id', $summit->getId()));
                    $filter->addFilterCondition(FilterElement::makeEqual('user_id', $current_member->getId()));
                }
                return $filter;
            },
            function(){
                return SerializerRegistry::SerializerType_Public;
            }
        );
    }

    /**
     * @param $summit_id
     * @return mixed
     */
    public function getAllBySummit($summit_id){

        $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->getResourceServerContext())->find($summit_id);
        if (is_null($summit)) return $this->error404();

        $current_member = $this->resource_server_context->getCurrentUser();
        if (is_null($current_member)) return $this->error403();

        $sponsor = null;
        if(!$current_member->isAdmin()){
            $sponsor = $current_member->getSponsorBySummit($summit);
            if(is_null($sponsor)){
                return $this->error403();
            }
        }

        return $this->_getAll(
            function(){
                return [
                    'attendee_first_name'        => ['=@', '=='],
                    'attendee_last_name'         => ['=@', '=='],
                    'attendee_full_name'         => ['=@', '=='],
                    'attendee_email'             => ['=@', '=='],
                    'ticket_number'              => ['=@', '=='],
                    'order_number'               => ['=@', '=='],
                    'sponsor_id'                 => ['=='],
                    'attendee_company'           => ['=@', '=='],
                ];
            },
            function(){
                return [
                    'attendee_first_name'      => 'sometimes|string',
                    'attendee_last_name'       => 'sometimes|string',
                    'attendee_full_name'       => 'sometimes|string',
                    'attendee_email'           => 'sometimes|string',
                    'ticket_number'            => 'sometimes|string',
                    'order_number'             => 'sometimes|string',
                    'sponsor_id'               => 'sometimes|integer',
                    'attendee_company'         => 'sometimes|string',
                ];
            },
            function()
            {
                return [
                    'id',
                    'attendee_full_name',
                    'attendee_email',
                    'attendee_first_name',
                    'attendee_last_name',
                    'attendee_company',
                    'scan_date'
                ];
            },
            function($filter) use($summit, $sponsor){
                if($filter instanceof Filter){
                    $filter->addFilterCondition(FilterElement::makeEqual('summit_id', $summit->getId()));
                    if(!is_null($sponsor)){
                        $filter->addFilterCondition(FilterElement::makeEqual('sponsor_id', $sponsor->getId()));
                    }
                }
                return $filter;
            },
            function(){
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

        $sponsor = null;
        if(!$current_member->isAdmin()){
            $sponsor = $current_member->getSponsorBySummit($summit);
            if(is_null($sponsor)){
                return $this->error403();
            }
        }

        return $this->_getAllCSV(
            function(){
                return [
                    'attendee_first_name'        => ['=@', '=='],
                    'attendee_last_name'         => ['=@', '=='],
                    'attendee_full_name'         => ['=@', '=='],
                    'attendee_email'             => ['=@', '=='],
                    'ticket_number'              => ['=@', '=='],
                    'order_number'               => ['=@', '=='],
                    'sponsor_id'                 => ['=='],
                    'attendee_company'           => ['=@', '=='],
                ];
            },
            function(){
                return [
                    'attendee_first_name'      => 'sometimes|string',
                    'attendee_last_name'       => 'sometimes|string',
                    'attendee_full_name'       => 'sometimes|string',
                    'attendee_email'           => 'sometimes|string',
                    'ticket_number'            => 'sometimes|string',
                    'order_number'             => 'sometimes|string',
                    'sponsor_id'               => 'sometimes|integer',
                    'attendee_company'         => 'sometimes|string',
                ];
            },
            function()
            {
                return [
                    'id',
                    'attendee_full_name',
                    'attendee_email',
                    'attendee_first_name',
                    'attendee_last_name',
                    'attendee_company',
                    'scan_date'
                ];
            },
            function($filter) use($summit, $sponsor){
                if($filter instanceof Filter){
                    $filter->addFilterCondition(FilterElement::makeEqual('summit_id', $summit->getId()));
                    if(!is_null($sponsor)){
                        $filter->addFilterCondition(FilterElement::makeEqual('sponsor_id', $sponsor->getId()));
                    }
                }
                return $filter;
            },
            function(){
                return SerializerRegistry::SerializerType_CSV;
            },
            function(){
                return [
                    'scan_date' => new EpochCellFormatter(),
                ];
            },
            function() use($summit) {

                $allowed_columns = [
                    'scan_date',
                    'qr_code',
                    'sponsor_id',
                    'user_id',
                    'badge_id',
                    'attendee_first_name',
                    'attendee_last_name',
                    'attendee_email',
                    'attendee_company',
                    'notes'
                ];

                foreach ($summit->getOrderExtraQuestionsByUsage(SummitOrderExtraQuestionTypeConstants::TicketQuestionUsage) as $question){
                    $allowed_columns[] = strip_tags($question->getLabel());
                }

                $columns_param = Request::input("columns", "");
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
            'attendees-badge-scans-',
            [
                'features_types'   => $summit->getBadgeFeaturesTypes(),
                'ticket_questions' => $summit->getOrderExtraQuestionsByUsage(SummitOrderExtraQuestionTypeConstants::TicketQuestionUsage)
            ]
        );
    }

    /**
     * @param $summit_id
     * @return mixed
     */
    protected function checkIn($summit_id) {
        return $this->processRequest(function () use ($summit_id) {
            if(!Request::isJson()) return $this->error400();
            $payload = $this->getJsonPayload($this->getCheckInValidationRules());

            $summit = SummitFinderStrategyFactory::build($this->getSummitRepository(), $this->getResourceServerContext())->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $this->attendee_service->doCheckIn($summit, trim($payload["qr_code"]));

            return $this->updated();
        });
    }

    use GetSummitChildElementById;

    /**
     * @inheritDoc
     */
    protected function getChildFromSummit(Summit $summit, $child_id): ?IEntity
    {
        $current_member = $this->resource_server_context->getCurrentUser();
        if (is_null($current_member)) throw new HTTP403ForbiddenException();

        return $this->service->getBadgeScan($summit, $current_member, $child_id);
    }
}
