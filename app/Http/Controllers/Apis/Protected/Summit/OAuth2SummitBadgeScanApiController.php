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
use App\ModelSerializers\SerializerUtils;
use App\Models\Foundation\Main\IGroup;
use App\Security\SummitScopes;
use App\Services\Model\IAttendeeService;
use Illuminate\Support\Facades\Request;
use Libs\ModelSerializers\AbstractSerializer;
use models\exceptions\EntityNotFoundException;
use models\summit\ISponsorUserInfoGrantRepository;
use models\exceptions\ValidationException;
use models\oauth2\IResourceServerContext;
use App\Services\Model\ISponsorUserInfoGrantService;
use models\summit\ISummitRepository;
use models\summit\SponsorBadgeScan;
use models\summit\Summit;
use models\summit\SummitOrderExtraQuestionTypeConstants;
use models\utils\IEntity;
use ModelSerializers\SerializerRegistry;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Response;
use utils\Filter;
use utils\FilterElement;
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
            'scan_date' => 'required|date_format:U|epoch_seconds',
            'notes' => 'sometimes|string|max:1024',
            'extra_questions' => 'sometimes|extra_question_dto_array',
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
        if (is_null($current_member))
            throw new HTTP403ForbiddenException();

        return $this->service->addBadgeScan($summit, $current_member, $payload);
    }

    use UpdateSummitChildElement;

    function getUpdateValidationRules(array $payload): array{
        return [
            'notes' => 'sometimes|string|max:1024',
            'extra_questions' => 'sometimes|extra_question_dto_array',
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
        if (is_null($current_member))
            throw new HTTP403ForbiddenException();

        return $this->service->updateBadgeScan($summit, $current_member, $child_id, $payload);
    }

    #[OA\Post(
        path: "/api/v1/summits/{id}/sponsors/{sponsor_id}/user-info-grants/me",
        summary: "Add user info grant for current user",
        operationId: "addUserInfoWithSponsor",
        tags: ["Badge Scans", "Sponsor User Info Grants"],
        security: [['summit_badge_scan_oauth2' => [
            SummitScopes::WriteMyBadgeScan,
        ]]],
        parameters: [
            new OA\Parameter(name: "id", description: "Summit ID", in: "path", required: true, schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "sponsor_id", description: "Sponsor ID", in: "path", required: true, schema: new OA\Schema(type: "integer")),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_CREATED,
                description: "Created",
                content: new OA\JsonContent(ref: "#/components/schemas/SponsorUserInfoGrant")
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: "Bad Request"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "not found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    public function addGrant($summit_id, $sponsor_id){
        return $this->processRequest(function() use($summit_id, $sponsor_id){
            $summit = SummitFinderStrategyFactory::build($this->getSummitRepository(), $this->getResourceServerContext())->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $current_member = $this->resource_server_context->getCurrentUser();
            if (is_null($current_member)) throw new HTTP403ForbiddenException();

            $grant = $this->service->addGrant($summit, intval($sponsor_id), $current_member);
            return $this->created(SerializerRegistry::getInstance()->getSerializer
            (
                $grant,
                $this->addSerializerType()
            )->serialize(
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
        return $this->summit_repository;
    }

    // traits
    use ParametrizedGetAll;

    #[OA\Get(
        path: "/api/v1/summits/{id}/badge-scans/me",
        summary: "Get all my badge scans for a summit",
        operationId: "getMyBadgeScans",
        tags: ['Badge Scans'],
        security: [['summit_badge_scan_oauth2' => [
            SummitScopes::ReadMyBadgeScan,
        ]]],
        parameters: [
            new OA\Parameter(name: "id", description: "Summit ID", in: "path", required: true, schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "page", description: "Page number", in: "query", required: false, schema: new OA\Schema(type: "integer", default: 1)),
            new OA\Parameter(name: "per_page", description: "Items per page", in: "query", required: false, schema: new OA\Schema(type: "integer", default: 10)),
            new OA\Parameter(name: "filter", description: "Filter query", in: "query", required: false, schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "order", description: "Order by", in: "query", required: false, schema: new OA\Schema(type: "string")),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "OK",
                content: new OA\JsonContent(ref: "#/components/schemas/PaginatedBadgeScansResponse")
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: "Bad Request"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "not found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    public function getAllMyBadgeScans($summit_id){
        $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->getResourceServerContext())->find($summit_id);
        if (is_null($summit))
            return $this->error404();

        $current_member = $this->resource_server_context->getCurrentUser();
        if (is_null($current_member))
            return $this->error403();

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
                    $filter->addFilterCondition(FilterElement::makeEqual('class_name', SponsorBadgeScan::ClassName));
                    $filter->addFilterCondition(FilterElement::makeEqual('user_id', $current_member->getId()));
                }
                return $filter;
            },
            function(){
                return SerializerRegistry::SerializerType_Public;
            }
        );
    }

    #[OA\Get(
        path: "/api/v1/summits/{id}/badge-scans",
        summary: "Get all badge scans for a summit",
        operationId: "getAllBadgeScans",
        tags: ['Badge Scans'],
        x: [
            'required-groups' => [
                IGroup::SummitAdministrators,
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::Sponsors,
                IGroup::SponsorExternalUsers,
            ]
        ],
        security: [['summit_badge_scan_oauth2' => [
            SummitScopes::ReadAllSummitData,
            SummitScopes::ReadBadgeScan,
        ]]],
        parameters: [
            new OA\Parameter(name: "id", description: "Summit ID", in: "path", required: true, schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "page", description: "Page number", in: "query", required: false, schema: new OA\Schema(type: "integer", default: 1)),
            new OA\Parameter(name: "per_page", description: "Items per page", in: "query", required: false, schema: new OA\Schema(type: "integer", default: 10)),
            new OA\Parameter(name: "filter", description: "Filter query", in: "query", required: false, schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "order", description: "Order by", in: "query", required: false, schema: new OA\Schema(type: "string")),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "OK",
                content: new OA\JsonContent(ref: "#/components/schemas/PaginatedBadgeScansResponse")
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: "Bad Request"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "not found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    public function getAllBySummit($summit_id){

        $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->getResourceServerContext())->find($summit_id);
        if (is_null($summit))
            return $this->error404();

        // check if we have an user ( not allowed for service accounts )
        $current_member = $this->resource_server_context->getCurrentUser();
        if (is_null($current_member))
            return $this->error403();

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
                    'scan_date',
                    'scanned_by'
                ];
            },
            function($filter) use($summit, $current_member){
                if($filter instanceof Filter){
                    $filter->addFilterCondition(FilterElement::makeEqual('summit_id', $summit->getId()));
                    $filter->addFilterCondition(FilterElement::makeEqual('class_name', SponsorBadgeScan::ClassName));
                    if (!is_null($current_member)){
                        if ($current_member->isAuthzFor($summit)) return $filter;
                        // add filter for sponsor user
                        if ($current_member->isSponsorUser()) {
                            $sponsor_ids = $current_member->getSponsorMembershipIds($summit);
                            // is allowed sponsors are empty, add dummy value
                            if (!count($sponsor_ids)) $sponsor_ids[] = 0;
                            $filter->addFilterCondition
                            (
                                FilterElement::makeEqual
                                (
                                    'sponsor_id',
                                    $sponsor_ids,
                                    "OR"
                                )
                            );
                        }
                    }
                }
                return $filter;
            },
            function(){
                return SerializerRegistry::SerializerType_Public;
            }
        );
    }

    #[OA\Get(
        path: "/api/v1/summits/{id}/badge-scans/csv",
        summary: "Get all badge scans for a summit in CSV format",
        operationId: "getAllBadgeScansCSV",
        tags: ['Badge Scans'],
        x: [
            'required-groups' => [
                IGroup::SummitAdministrators,
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::Sponsors,
                IGroup::SponsorExternalUsers,
            ]
        ],
        security: [['summit_badge_scan_oauth2' => [
            SummitScopes::ReadAllSummitData,
            SummitScopes::ReadBadgeScan,
        ]]],
        parameters: [
            new OA\Parameter(name: "id", description: "Summit ID", in: "path", required: true, schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "filter", description: "Filter query", in: "query", required: false, schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "order", description: "Order by", in: "query", required: false, schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "columns", description: "Columns to export (comma separated)", in: "query", required: false, schema: new OA\Schema(type: "string")),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "OK",
                content: new OA\MediaType(
                    mediaType: "text/csv",
                    schema: new OA\Schema(type: "string", format: "binary")
                )
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: "Bad Request"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "not found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    public function getAllBySummitCSV($summit_id){

        $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->getResourceServerContext())->find($summit_id);
        if (is_null($summit)) return $this->error404();

        $current_member = $this->resource_server_context->getCurrentUser();
        if (is_null($current_member))
            return $this->error403();

        // check summit authz access
        if(!$current_member->isSummitAllowed($summit)){
            if(!$current_member->hasSponsorMembershipsFor($summit)){
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
                    'scan_date',
                    'scanned_by',
                ];
            },
            function($filter) use($summit, $current_member){
                if($filter instanceof Filter){
                    $filter->addFilterCondition(FilterElement::makeEqual('summit_id', $summit->getId()));
                    $filter->addFilterCondition(FilterElement::makeEqual('class_name', SponsorBadgeScan::ClassName));
                    if (!is_null($current_member)){
                        if ($current_member->isAuthzFor($summit)) return $filter;
                        // add filter for sponsor user
                        if ($current_member->isSponsorUser()) {
                            $sponsor_ids = $current_member->getSponsorMembershipIds($summit);
                            // is allowed sponsors are empty, add dummy value
                            if (!count($sponsor_ids)) $sponsor_ids[] = 0;
                            $filter->addFilterCondition
                            (
                                FilterElement::makeEqual
                                (
                                    'sponsor_id',
                                    $sponsor_ids,
                                    "OR"
                                )
                            );
                        }
                    }
                }
                return $filter;
            },
            function(){
                return SerializerRegistry::SerializerType_CSV;
            },
            function() use($summit) {
                return [
                    'scan_date' => new EpochCellFormatter(EpochCellFormatter::DefaultFormat, $summit->getTimeZone())
                ];
            },
            function() use($summit) {

                $allowed_columns = [
                    'scan_date',
                    'scanned_by',
                    'qr_code',
                    'sponsor_id',
                    'user_id',
                    'badge_id',
                    'attendee_first_name',
                    'attendee_last_name',
                    'attendee_email',
                    'attendee_company',
                    'notes',
                ];

                foreach ($summit->getOrderExtraQuestionsByUsage(SummitOrderExtraQuestionTypeConstants::TicketQuestionUsage) as $question){
                    $allowed_columns[] = AbstractSerializer::getCSVLabel($question->getLabel());
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
                'ticket_questions' => $summit->getOrderExtraQuestionsByUsage(SummitOrderExtraQuestionTypeConstants::TicketQuestionUsage),
            ]
        );
    }

    #[OA\Post(
        path: "/api/v1/summits/{id}/badge-scans",
        summary: "Add a badge scan",
        operationId: "addBadgeScan",
        tags: ['Badge Scans'],
        x: [
            'required-groups' => [
                IGroup::SummitAdministrators,
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::Sponsors,
                IGroup::SponsorExternalUsers,
            ]
        ],
        security: [['summit_badge_scan_oauth2' => [
            SummitScopes::WriteBadgeScan,
        ]]],
        parameters: [
            new OA\Parameter(name: "id", description: "Summit ID", in: "path", required: true, schema: new OA\Schema(type: "string")),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: "#/components/schemas/BadgeScanAddRequest")
        ),
        responses: [
            new OA\Response(
                response: Response::HTTP_CREATED,
                description: "Created",
                content: new OA\JsonContent(ref: "#/components/schemas/SponsorBadgeScan")
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: "Bad Request"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "not found"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    public function add($summit_id){
        return $this->processRequest(function() use($summit_id){
            $summit = SummitFinderStrategyFactory::build($this->getSummitRepository(), $this->getResourceServerContext())->find($summit_id);
            if (is_null($summit)) return $this->error404();
            return $this->_add($summit, $this->getJsonPayload($this->getAddValidationRules([])));
        });
    }

    #[OA\Put(
        path: "/api/v1/summits/{id}/badge-scans/checkin",
        summary: "Check in an attendee using QR code",
        operationId: "checkInBadgeScan",
        tags: ['Badge Scans'],
        security: [['summit_badge_scan_oauth2' => [
            SummitScopes::WriteBadgeScan,
            SummitScopes::WriteSummitData,
        ]]],
        parameters: [
            new OA\Parameter(name: "id", description: "Summit ID", in: "path", required: true, schema: new OA\Schema(type: "string")),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: "#/components/schemas/BadgeScanCheckInRequest")
        ),
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: "OK"),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: "Bad Request"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "not found"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
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

    #[OA\Get(
        path: "/api/v1/summits/{id}/badge-scans/{scan_id}",
        summary: "Get a badge scan by id",
        operationId: "getBadgeScan",
        tags: ['Badge Scans'],
        x: [
            'required-groups' => [
                IGroup::SummitAdministrators,
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::Sponsors,
                IGroup::SponsorExternalUsers,
            ]
        ],
        security: [['summit_badge_scan_oauth2' => [
            SummitScopes::ReadAllSummitData,
            SummitScopes::ReadBadgeScan,
            SummitScopes::ReadMyBadgeScan,
        ]]],
        parameters: [
            new OA\Parameter(name: "id", description: "Summit ID", in: "path", required: true, schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "scan_id", description: "Badge scan ID", in: "path", required: true, schema: new OA\Schema(type: "integer")),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "OK",
                content: new OA\JsonContent(ref: "#/components/schemas/SponsorBadgeScan")
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: "Bad Request"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "not found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]

    #[OA\Put(
        path: "/api/v1/summits/{id}/badge-scans/{scan_id}",
        summary: "Update a badge scan",
        operationId: "updateBadgeScan",
        tags: ['Badge Scans'],
        x: [
            'required-groups' => [
                IGroup::SummitAdministrators,
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::Sponsors,
                IGroup::SponsorExternalUsers,
            ]
        ],
        security: [['summit_badge_scan_oauth2' => [
            SummitScopes::WriteSummitData,
            SummitScopes::WriteBadgeScan,
        ]]],
        parameters: [
            new OA\Parameter(name: "id", description: "Summit ID", in: "path", required: true, schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "scan_id", description: "Badge scan ID", in: "path", required: true, schema: new OA\Schema(type: "integer")),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: "#/components/schemas/BadgeScanUpdateRequest")
        ),
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "OK",
                content: new OA\JsonContent(ref: "#/components/schemas/SponsorBadgeScan")
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: "Bad Request"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "not found"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    /**
     * @inheritDoc
     */
    protected function getChildFromSummit(Summit $summit, $child_id): ?IEntity
    {
        $current_member = $this->resource_server_context->getCurrentUser();
        if (is_null($current_member))
            throw new HTTP403ForbiddenException();

        return $this->service->getBadgeScan($summit, $current_member, $child_id);
    }

}
