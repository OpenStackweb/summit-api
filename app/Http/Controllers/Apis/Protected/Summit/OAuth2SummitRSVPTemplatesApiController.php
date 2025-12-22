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
use App\Models\Foundation\Main\IGroup;
use App\Models\Foundation\Summit\Events\RSVP\RSVPMultiValueQuestionTemplate;
use App\Models\Foundation\Summit\Repositories\IRSVPTemplateRepository;
use App\Security\SummitScopes;
use App\Services\Model\IRSVPTemplateService;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Validator;
use libs\utils\PaginationValidationRules;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use models\main\IMemberRepository;
use models\oauth2\IResourceServerContext;
use models\summit\ISummitRepository;
use ModelSerializers\SerializerRegistry;
use OpenApi\Attributes as OA;
use utils\Filter;
use utils\FilterParser;
use utils\OrderParser;
use utils\PagingInfo;
use Exception;
/**
 * Class OAuth2SummitRSVPTemplatesApiController
 * @package App\Http\Controllers
 */
final class OAuth2SummitRSVPTemplatesApiController extends OAuth2ProtectedController
{

    /**
     * @var ISummitRepository
     */
    private $summit_repository;

    /**
     * @var IRSVPTemplateRepository
     */
    private $rsvp_template_repository;

    /**
     * @var IRSVPTemplateService
     */
    private $rsvp_template_service;

    /**
     * @var IMemberRepository
     */
    private $member_repository;

    /**
     * OAuth2SummitRSVPTemplatesApiController constructor.
     * @param ISummitRepository $summit_repository
     * @param IRSVPTemplateRepository $rsvp_template_repository
     * @param IMemberRepository $member_repository
     * @param IRSVPTemplateService $rsvp_template_service
     * @param IResourceServerContext $resource_server_context
     */
    public function __construct
    (
        ISummitRepository $summit_repository,
        IRSVPTemplateRepository $rsvp_template_repository,
        IMemberRepository $member_repository,
        IRSVPTemplateService $rsvp_template_service,
        IResourceServerContext $resource_server_context
    )
    {
        parent::__construct($resource_server_context);
        $this->summit_repository        = $summit_repository;
        $this->member_repository        = $member_repository;
        $this->rsvp_template_service    = $rsvp_template_service;
        $this->rsvp_template_repository = $rsvp_template_repository;
    }

    /**
     *  Template endpoints
     */

    #[OA\Get(
        path: "/api/v1/summits/{id}/rsvp-templates",
        description: "Get all RSVP templates for a summit with optional filtering and pagination",
        summary: 'Read All RSVP Templates',
        operationId: 'getAllRSVPTemplatesBySummit',
        tags: ['RSVP Templates'],
        security: [
            [
                'summit_rsvp_templates_oauth2' => [
                    SummitScopes::ReadAllSummitData
                ]
            ]
        ],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
            ]
        ],
        parameters: [
            new OA\Parameter(
                name: 'access_token',
                in: 'query',
                required: false,
                description: 'OAuth2 access token (alternative to Authorization: Bearer)',
                schema: new OA\Schema(type: 'string', example: 'eyJhbGciOi...'),
            ),
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string'),
                description: 'The summit id'
            ),
            new OA\Parameter(
                name: 'filter[]',
                in: 'query',
                required: false,
                description: 'Filter expressions in the format field<op>value. Operators: @@, ==, =@.',
                style: 'form',
                explode: true,
                schema: new OA\Schema(
                    type: 'array',
                    items: new OA\Items(type: 'string', example: 'title@@template')
                )
            ),
            new OA\Parameter(
                name: 'order',
                in: 'query',
                required: false,
                description: 'Order by field(s)',
                schema: new OA\Schema(type: 'string', example: 'id,title')
            ),
            new OA\Parameter(
                name: 'expand',
                in: 'query',
                required: false,
                description: 'Comma-separated list of related resources to include',
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'page',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 1)
            ),
            new OA\Parameter(
                name: 'per_page',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 10)
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'List of RSVP templates',
                content: new OA\JsonContent(ref: '#/components/schemas/PaginatedRSVPTemplatesResponse')
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    /**
     * @param $summit_id
     * @return mixed
     */
    public function getAllBySummit($summit_id){

        $values = Request::all();
        $rules  = PaginationValidationRules::get();

        try {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit))
                return $this->error404();

            $validation = Validator::make($values, $rules);

            if ($validation->fails()) {
                $ex = new ValidationException();
                throw $ex->setMessages($validation->messages()->toArray());
            }

            // default values
            $page     = 1;
            $per_page = PaginationValidationRules::PerPageMin;

            if (Request::has('page')) {
                $page     = intval(Request::input('page'));
                $per_page = intval(Request::input('per_page'));
            }

            $filter = null;

            if (Request::has('filter')) {
                $filter = FilterParser::parse(Request::input('filter'), [
                    'title'          => ['=@', '=='],
                    'is_enabled'     => [ '=='],
                ]);
            }

            if(is_null($filter)) $filter = new Filter();

            $filter->validate([
                'title'      => 'sometimes|string',
                'is_enabled' => 'sometimes|boolean',
            ]);

            $order = null;

            if (Request::has('order'))
            {
                $order = OrderParser::parse(Request::input('order'), [
                    'id',
                    'title',
                ]);
            }

            $data = $this->rsvp_template_repository->getBySummit($summit, new PagingInfo($page, $per_page), $filter, $order);

            return $this->ok
            (
                $data->toArray
                (
                    Request::input('expand', ''),
                    [],
                    [],
                    []
                )
            );
        }
        catch (ValidationException $ex1)
        {
            Log::warning($ex1);
            return $this->error412([ $ex1->getMessage()]);
        }
        catch (EntityNotFoundException $ex2)
        {
            Log::warning($ex2);
            return $this->error404(['message' => $ex2->getMessage()]);
        }
        catch(\HTTP401UnauthorizedException $ex3)
        {
            Log::warning($ex3);
            return $this->error401();
        }
        catch (\Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    #[OA\Get(
        path: "/api/v1/summits/{id}/rsvp-templates/{template_id}",
        description: "Get a specific RSVP template",
        summary: 'Read RSVP Template',
        operationId: 'getRSVPTemplate',
        tags: ['RSVP Templates'],
        security: [
            [
                'summit_rsvp_templates_oauth2' => [
                    SummitScopes::ReadAllSummitData
                ]
            ]
        ],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
            ]
        ],
        parameters: [
            new OA\Parameter(
                name: 'access_token',
                in: 'query',
                required: false,
                description: 'OAuth2 access token (alternative to Authorization: Bearer)',
                schema: new OA\Schema(type: 'string', example: 'eyJhbGciOi...'),
            ),
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string'),
                description: 'The summit id'
            ),
            new OA\Parameter(
                name: 'template_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The RSVP template id'
            ),
            new OA\Parameter(
                name: 'expand',
                in: 'query',
                required: false,
                description: 'Comma-separated list of related resources to include',
                schema: new OA\Schema(type: 'string', example: 'questions')
            ),
            new OA\Parameter(
                name: 'relations',
                in: 'query',
                required: false,
                description: 'Relations to load eagerly',
                schema: new OA\Schema(type: 'string')
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'RSVP template details',
                content: new OA\JsonContent(ref: '#/components/schemas/RSVPTemplate')
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    /**
     * @param $summit_id
     * @param $template_id
     * @return mixed
     */
    public function getRSVPTemplate($summit_id, $template_id){
        try {

            $expand    = Request::input('expand', '');
            $relations = Request::input('relations', '');
            $relations = !empty($relations) ? explode(',', $relations) : [];

            $summit    = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $template = $summit->getRSVPTemplateById($template_id);

            if (is_null($template)) {
                return $this->error404();
            }

            return $this->ok(SerializerRegistry::getInstance()->getSerializer($template)->serialize($expand, [], $relations));
        }
        catch (ValidationException $ex1) {
            Log::warning($ex1);
            return $this->error412(array($ex1->getMessage()));
        }
        catch(EntityNotFoundException $ex2)
        {
            Log::warning($ex2);
            return $this->error404(array('message'=> $ex2->getMessage()));
        }
        catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    #[OA\Get(
        path: "/api/v1/summits/{id}/rsvp-templates/questions/metadata",
        description: "Get metadata about RSVP template questions (available question types, validators, etc)",
        summary: 'Read RSVP Template Questions Metadata',
        operationId: 'getRSVPTemplateQuestionsMetadata',
        tags: ['RSVP Templates'],
        security: [
            [
                'summit_rsvp_templates_oauth2' => [
                    SummitScopes::ReadAllSummitData
                ]
            ]
        ],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
            ]
        ],
        parameters: [
            new OA\Parameter(
                name: 'access_token',
                in: 'query',
                required: false,
                description: 'OAuth2 access token (alternative to Authorization: Bearer)',
                schema: new OA\Schema(type: 'string', example: 'eyJhbGciOi...'),
            ),
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string'),
                description: 'The summit id'
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Metadata about RSVP template questions',
                content: new OA\JsonContent(ref: '#/components/schemas/RSVPTemplateQuestionMetadata')
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    /**
     * @param $summit_id
     * @return mixed
     */
    public function getRSVPTemplateQuestionsMetadata($summit_id){
        $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
        if (is_null($summit)) return $this->error404();

        return $this->ok
        (
            $this->rsvp_template_repository->getQuestionsMetadata($summit)
        );
    }

    #[OA\Delete(
        path: "/api/v1/summits/{id}/rsvp-templates/{template_id}",
        description: "Delete an RSVP template",
        summary: 'Delete RSVP Template',
        operationId: 'deleteRSVPTemplate',
        tags: ['RSVP Templates'],
        security: [
            [
                'summit_rsvp_templates_oauth2' => [
                    SummitScopes::WriteSummitData,
                    SummitScopes::WriteRSVPTemplateData,
                ]
            ]
        ],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
            ]
        ],
        parameters: [
            new OA\Parameter(
                name: 'access_token',
                in: 'query',
                required: false,
                description: 'OAuth2 access token (alternative to Authorization: Bearer)',
                schema: new OA\Schema(type: 'string', example: 'eyJhbGciOi...'),
            ),
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string'),
                description: 'The summit id'
            ),
            new OA\Parameter(
                name: 'template_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The RSVP template id'
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_NO_CONTENT,
                description: "RSVP template deleted"
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    /**
     * @param $summit_id
     * @param $template_id
     * @return mixed
     */
    public function deleteRSVPTemplate($summit_id, $template_id){
        try {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $this->rsvp_template_service->deleteTemplate($summit, $template_id);

            return $this->deleted();
        }
        catch (ValidationException $ex1) {
            Log::warning($ex1);
            return $this->error412(array($ex1->getMessage()));
        }
        catch(EntityNotFoundException $ex2)
        {
            Log::warning($ex2);
            return $this->error404(array('message'=> $ex2->getMessage()));
        }
        catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    #[OA\Post(
        path: "/api/v1/summits/{id}/rsvp-templates",
        description: "Create a new RSVP template for a summit",
        summary: 'Create RSVP Template',
        operationId: 'addRSVPTemplate',
        tags: ['RSVP Templates'],
        security: [
            [
                'summit_rsvp_templates_oauth2' => [
                    SummitScopes::WriteSummitData,
                    SummitScopes::WriteRSVPTemplateData,
                ]
            ]
        ],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
            ]
        ],
        parameters: [
            new OA\Parameter(
                name: 'access_token',
                in: 'query',
                required: false,
                description: 'OAuth2 access token (alternative to Authorization: Bearer)',
                schema: new OA\Schema(type: 'string', example: 'eyJhbGciOi...'),
            ),
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string'),
                description: 'The summit id'
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: "#/components/schemas/RSVPTemplate")
        ),
        responses: [
            new OA\Response(
                response: Response::HTTP_CREATED,
                description: 'RSVP template created',
                content: new OA\JsonContent(ref: '#/components/schemas/RSVPTemplate')
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: "Bad Request"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    /**
     * @param $summit_id
     * @return mixed
     */
    public function addRSVPTemplate($summit_id){
        try {

            if(!Request::isJson()) return $this->error400();
            $payload = Request::json()->all();

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $rules = SummitRSVPTemplateValidationRulesFactory::build($payload);
            // Creates a Validator instance and validates the data.
            $validation = Validator::make($payload, $rules);

            if ($validation->fails()) {
                $messages = $validation->messages()->toArray();

                return $this->error412
                (
                    $messages
                );
            }

            $template = $this->rsvp_template_service->addTemplate($summit, $this->resource_server_context->getCurrentUser(), $payload);

            return $this->created(SerializerRegistry::getInstance()->getSerializer($template)->serialize());
        }
        catch (ValidationException $ex1) {
            Log::warning($ex1);
            return $this->error412([$ex1->getMessage()]);
        }
        catch(EntityNotFoundException $ex2)
        {
            Log::warning($ex2);
            return $this->error404(['message'=> $ex2->getMessage()]);
        }
        catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    #[OA\Put(
        path: "/api/v1/summits/{id}/rsvp-templates/{template_id}",
        description: "Update an RSVP template",
        summary: 'Update RSVP Template',
        operationId: 'updateRSVPTemplate',
        tags: ['RSVP Templates'],
        security: [
            [
                'summit_rsvp_templates_oauth2' => [
                    SummitScopes::WriteSummitData,
                    SummitScopes::WriteRSVPTemplateData,
                ]
            ]
        ],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
            ]
        ],
        parameters: [
            new OA\Parameter(
                name: 'access_token',
                in: 'query',
                required: false,
                description: 'OAuth2 access token (alternative to Authorization: Bearer)',
                schema: new OA\Schema(type: 'string', example: 'eyJhbGciOi...'),
            ),
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string'),
                description: 'The summit id'
            ),
            new OA\Parameter(
                name: 'template_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The RSVP template id'
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: "#/components/schemas/RSVPTemplate")
        ),
        responses: [
            new OA\Response(
                response: Response::HTTP_CREATED,
                description: 'RSVP template updated',
                content: new OA\JsonContent(ref: '#/components/schemas/RSVPTemplate')
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: "Bad Request"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    /**
     * @param $summit_id
     * @param $template_id
     * @return mixed
     */
    public function updateRSVPTemplate($summit_id, $template_id){
        try {

            if(!Request::isJson()) return $this->error400();
            $payload = Request::json()->all();

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $rules = SummitRSVPTemplateValidationRulesFactory::build($payload, true);
            // Creates a Validator instance and validates the data.
            $validation = Validator::make($payload, $rules);

            if ($validation->fails()) {
                $messages = $validation->messages()->toArray();

                return $this->error412
                (
                    $messages
                );
            }

            $template = $this->rsvp_template_service->updateTemplate($summit, $template_id, $payload);

            return $this->updated(SerializerRegistry::getInstance()->getSerializer($template)->serialize());
        }
        catch (ValidationException $ex1) {
            Log::warning($ex1);
            return $this->error412([$ex1->getMessage()]);
        }
        catch(EntityNotFoundException $ex2)
        {
            Log::warning($ex2);
            return $this->error404(['message'=> $ex2->getMessage()]);
        }
        catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    /**
     *  Questions endpoints
     */

    #[OA\Get(
        path: "/api/v1/summits/{id}/rsvp-templates/{template_id}/questions/{question_id}",
        description: "Get a specific question from an RSVP template",
        summary: 'Read RSVP Template Question',
        operationId: 'getRSVPTemplateQuestion',
        tags: ['RSVP Templates', 'RSVP Template Questions'],
        security: [
            [
                'summit_rsvp_templates_oauth2' => [
                    SummitScopes::ReadAllSummitData,
                ]
            ]
        ],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
            ]
        ],
        parameters: [
            new OA\Parameter(
                name: 'access_token',
                in: 'query',
                required: false,
                description: 'OAuth2 access token (alternative to Authorization: Bearer)',
                schema: new OA\Schema(type: 'string', example: 'eyJhbGciOi...'),
            ),
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string'),
                description: 'The summit id'
            ),
            new OA\Parameter(
                name: 'template_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The RSVP template id'
            ),
            new OA\Parameter(
                name: 'question_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The question id'
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Question details',
                content: new OA\JsonContent(ref: '#/components/schemas/RSVPTemplateQuestion')
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    /**
     * @param $summit_id
     * @param $template_id
     * @param $question_id
     * @return mixed
     */
    public function getRSVPTemplateQuestion($summit_id, $template_id, $question_id){
        try {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $template = $summit->getRSVPTemplateById($template_id);
            if (is_null($template)) return $this->error404();

            $question = $template->getQuestionById($question_id);
            if (is_null($question)) return $this->error404();
            return $this->ok(SerializerRegistry::getInstance()->getSerializer($question)->serialize());
        }
        catch (ValidationException $ex1) {
            Log::warning($ex1);
            return $this->error412(array($ex1->getMessage()));
        }
        catch(EntityNotFoundException $ex2)
        {
            Log::warning($ex2);
            return $this->error404(array('message'=> $ex2->getMessage()));
        }
        catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    #[OA\Post(
        path: "/api/v1/summits/{id}/rsvp-templates/{template_id}/questions",
        description: "Add a new question to an RSVP template",
        summary: 'Create RSVP Template Question',
        operationId: 'addRSVPTemplateQuestion',
        tags: ['RSVP Templates', 'RSVP Template Questions'],
        security: [
            [
                'summit_rsvp_templates_oauth2' => [
                    SummitScopes::WriteSummitData,
                    SummitScopes::WriteRSVPTemplateData,
                ]
            ]
        ],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
            ]
        ],
        parameters: [
            new OA\Parameter(
                name: 'access_token',
                in: 'query',
                required: false,
                description: 'OAuth2 access token (alternative to Authorization: Bearer)',
                schema: new OA\Schema(type: 'string', example: 'eyJhbGciOi...'),
            ),
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string'),
                description: 'The summit id'
            ),
            new OA\Parameter(
                name: 'template_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The RSVP template id'
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: "#/components/schemas/RSVPTemplateQuestion")
        ),
        responses: [
            new OA\Response(
                response: Response::HTTP_CREATED,
                description: 'Question created',
                content: new OA\JsonContent(ref: '#/components/schemas/RSVPTemplateQuestion')
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: "Bad Request"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    /**
     * @param $summit_id
     * @param $template_id
     * @return mixed
     */
    public function addRSVPTemplateQuestion($summit_id, $template_id){
        try {

            if(!Request::isJson()) return $this->error400();
            $payload = Request::json()->all();

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $rules = SummitRSVPTemplateQuestionValidationRulesFactory::build($payload);
            // Creates a Validator instance and validates the data.
            $validation = Validator::make($payload, $rules);

            if ($validation->fails()) {
                $messages = $validation->messages()->toArray();

                return $this->error412
                (
                    $messages
                );
            }

            $question = $this->rsvp_template_service->addQuestion($summit, $template_id, $payload);

            return $this->created(SerializerRegistry::getInstance()->getSerializer($question)->serialize());
        }
        catch (ValidationException $ex1) {
            Log::warning($ex1);
            return $this->error412(array($ex1->getMessage()));
        }
        catch(EntityNotFoundException $ex2)
        {
            Log::warning($ex2);
            return $this->error404(array('message'=> $ex2->getMessage()));
        }
        catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    #[OA\Put(
        path: "/api/v1/summits/{id}/rsvp-templates/{template_id}/questions/{question_id}",
        description: "Update a question in an RSVP template",
        summary: 'Update RSVP Template Question',
        operationId: 'updateRSVPTemplateQuestion',
        tags: ['RSVP Templates', 'RSVP Template Questions'],
        security: [
            [
                'summit_rsvp_templates_oauth2' => [
                    SummitScopes::WriteSummitData,
                    SummitScopes::WriteRSVPTemplateData,
                ]
            ]
        ],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
            ]
        ],
        parameters: [
            new OA\Parameter(
                name: 'access_token',
                in: 'query',
                required: false,
                description: 'OAuth2 access token (alternative to Authorization: Bearer)',
                schema: new OA\Schema(type: 'string', example: 'eyJhbGciOi...'),
            ),
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string'),
                description: 'The summit id'
            ),
            new OA\Parameter(
                name: 'template_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The RSVP template id'
            ),
            new OA\Parameter(
                name: 'question_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The question id'
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: "#/components/schemas/RSVPTemplateQuestion")
        ),
        responses: [
            new OA\Response(
                response: Response::HTTP_CREATED,
                description: 'Question updated',
                content: new OA\JsonContent(ref: '#/components/schemas/RSVPTemplateQuestion')
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: "Bad Request"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    /**
     * @param $summit_id
     * @param $template_id
     * @param $question_id
     * @return mixed
     */
    public function updateRSVPTemplateQuestion($summit_id, $template_id, $question_id){
        try {

            if(!Request::isJson()) return $this->error400();
            $payload = Request::json()->all();

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $rules = SummitRSVPTemplateQuestionValidationRulesFactory::build($payload, true);
            // Creates a Validator instance and validates the data.
            $validation = Validator::make($payload, $rules);

            if ($validation->fails()) {
                $messages = $validation->messages()->toArray();

                return $this->error412
                (
                    $messages
                );
            }

            $question = $this->rsvp_template_service->updateQuestion($summit, $template_id, $question_id, $payload);

            return $this->updated(SerializerRegistry::getInstance()->getSerializer($question)->serialize());
        }
        catch (ValidationException $ex1) {
            Log::warning($ex1);
            return $this->error412(array($ex1->getMessage()));
        }
        catch(EntityNotFoundException $ex2)
        {
            Log::warning($ex2);
            return $this->error404(array('message'=> $ex2->getMessage()));
        }
        catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    #[OA\Delete(
        path: "/api/v1/summits/{id}/rsvp-templates/{template_id}/questions/{question_id}",
        description: "Delete a question from an RSVP template",
        summary: 'Delete RSVP Template Question',
        operationId: 'deleteRSVPTemplateQuestion',
        tags: ['RSVP Templates', 'RSVP Template Questions'],
        security: [
            [
                'summit_rsvp_templates_oauth2' => [
                    SummitScopes::WriteSummitData,
                    SummitScopes::WriteRSVPTemplateData,
                ]
            ]
        ],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
            ]
        ],
        parameters: [
            new OA\Parameter(
                name: 'access_token',
                in: 'query',
                required: false,
                description: 'OAuth2 access token (alternative to Authorization: Bearer)',
                schema: new OA\Schema(type: 'string', example: 'eyJhbGciOi...'),
            ),
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string'),
                description: 'The summit id'
            ),
            new OA\Parameter(
                name: 'template_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The RSVP template id'
            ),
            new OA\Parameter(
                name: 'question_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The question id'
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_NO_CONTENT,
                description: "Question deleted"
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    /**
     * @param $summit_id
     * @param $template_id
     * @param $question_id
     * @return mixed
     */
    public function deleteRSVPTemplateQuestion($summit_id, $template_id, $question_id){
        try {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $this->rsvp_template_service->deleteQuestion($summit, $template_id, $question_id);

            return $this->deleted();
        }
        catch (ValidationException $ex1) {
            Log::warning($ex1);
            return $this->error412(array($ex1->getMessage()));
        }
        catch(EntityNotFoundException $ex2)
        {
            Log::warning($ex2);
            return $this->error404(array('message'=> $ex2->getMessage()));
        }
        catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    /**
     * values endpoints
     */

    #[OA\Get(
        path: "/api/v1/summits/{id}/rsvp-templates/{template_id}/questions/{question_id}/values/{value_id}",
        description: "Get a specific value/option for a multi-select question",
        summary: 'Read RSVP Template Question Value',
        operationId: 'getRSVPTemplateQuestionValue',
        tags: ['RSVP Templates', 'RSVP Template Question Values'],
        security: [
            [
                'summit_rsvp_templates_oauth2' => [
                    SummitScopes::ReadAllSummitData,
                ]
            ]
        ],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
            ]
        ],
        parameters: [
            new OA\Parameter(
                name: 'access_token',
                in: 'query',
                required: false,
                description: 'OAuth2 access token (alternative to Authorization: Bearer)',
                schema: new OA\Schema(type: 'string', example: 'eyJhbGciOi...'),
            ),
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string'),
                description: 'The summit id'
            ),
            new OA\Parameter(
                name: 'template_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The RSVP template id'
            ),
            new OA\Parameter(
                name: 'question_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The question id'
            ),
            new OA\Parameter(
                name: 'value_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The value id'
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Value details',
                content: new OA\JsonContent(ref: '#/components/schemas/RSVPTemplateQuestionValue')
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    /**
     * @param $summit_id
     * @param $template_id
     * @param $question_id
     * @param $value_id
     * @return mixed
     */
    public function getRSVPTemplateQuestionValue($summit_id, $template_id, $question_id, $value_id){
        try {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $template = $summit->getRSVPTemplateById($template_id);
            if (is_null($template)) return $this->error404();

            $question = $template->getQuestionById($question_id);
            if (is_null($question)) return $this->error404();

            if (!$question instanceof RSVPMultiValueQuestionTemplate) return $this->error404();

            $value = $question->getValueById($value_id);
            if (is_null($value)) return $this->error404();

            return $this->ok(SerializerRegistry::getInstance()->getSerializer($value)->serialize());
        }
        catch (ValidationException $ex1) {
            Log::warning($ex1);
            return $this->error412(array($ex1->getMessage()));
        }
        catch(EntityNotFoundException $ex2)
        {
            Log::warning($ex2);
            return $this->error404(array('message'=> $ex2->getMessage()));
        }
        catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    #[OA\Post(
        path: "/api/v1/summits/{id}/rsvp-templates/{template_id}/questions/{question_id}/values",
        description: "Add a value/option to a multi-select question",
        summary: 'Create RSVP Template Question Value',
        operationId: 'addRSVPTemplateQuestionValue',
        tags: ['RSVP Templates', 'RSVP Template Question Values'],
        security: [
            [
                'summit_rsvp_templates_oauth2' => [
                    SummitScopes::WriteSummitData,
                    SummitScopes::WriteRSVPTemplateData,
                ]
            ]
        ],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
            ]
        ],
        parameters: [
            new OA\Parameter(
                name: 'access_token',
                in: 'query',
                required: false,
                description: 'OAuth2 access token (alternative to Authorization: Bearer)',
                schema: new OA\Schema(type: 'string', example: 'eyJhbGciOi...'),
            ),
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string'),
                description: 'The summit id'
            ),
            new OA\Parameter(
                name: 'template_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The RSVP template id'
            ),
            new OA\Parameter(
                name: 'question_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The question id'
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: "#/components/schemas/RSVPTemplateQuestionValue")
        ),
        responses: [
            new OA\Response(
                response: Response::HTTP_CREATED,
                description: 'Value created',
                content: new OA\JsonContent(ref: '#/components/schemas/RSVPTemplateQuestionValue')
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: "Bad Request"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    /**
     * @param $summit_id
     * @param $template_id
     * @param $question_id
     * @return mixed
     */
    public function addRSVPTemplateQuestionValue($summit_id, $template_id, $question_id){
        try {

            if(!Request::isJson()) return $this->error400();
            $payload = Request::json()->all();

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $rules = SummitRSVPTemplateQuestionValueValidationRulesFactory::build($payload);
            // Creates a Validator instance and validates the data.
            $validation = Validator::make($payload, $rules);

            if ($validation->fails()) {
                $messages = $validation->messages()->toArray();

                return $this->error412
                (
                    $messages
                );
            }

            $value = $this->rsvp_template_service->addQuestionValue($summit, $template_id, $question_id, $payload);

            return $this->created(SerializerRegistry::getInstance()->getSerializer($value)->serialize());
        }
        catch (ValidationException $ex1) {
            Log::warning($ex1);
            return $this->error412(array($ex1->getMessage()));
        }
        catch(EntityNotFoundException $ex2)
        {
            Log::warning($ex2);
            return $this->error404(array('message'=> $ex2->getMessage()));
        }
        catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    #[OA\Put(
        path: "/api/v1/summits/{id}/rsvp-templates/{template_id}/questions/{question_id}/values/{value_id}",
        description: "Update a value/option for a multi-select question",
        summary: 'Update RSVP Template Question Value',
        operationId: 'updateRSVPTemplateQuestionValue',
        tags: ['RSVP Templates', 'RSVP Template Question Values'],
        security: [
            [
                'summit_rsvp_templates_oauth2' => [
                    SummitScopes::WriteSummitData,
                    SummitScopes::WriteRSVPTemplateData,
                ]
            ]
        ],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
            ]
        ],
        parameters: [
            new OA\Parameter(
                name: 'access_token',
                in: 'query',
                required: false,
                description: 'OAuth2 access token (alternative to Authorization: Bearer)',
                schema: new OA\Schema(type: 'string', example: 'eyJhbGciOi...'),
            ),
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string'),
                description: 'The summit id'
            ),
            new OA\Parameter(
                name: 'template_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The RSVP template id'
            ),
            new OA\Parameter(
                name: 'question_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The question id'
            ),
            new OA\Parameter(
                name: 'value_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The value id'
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: "#/components/schemas/RSVPTemplateQuestionValue")
        ),
        responses: [
            new OA\Response(
                response: Response::HTTP_CREATED,
                description: 'Value updated',
                content: new OA\JsonContent(ref: '#/components/schemas/RSVPTemplateQuestionValue')
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: "Bad Request"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    /**
     * @param $summit_id
     * @param $template_id
     * @param $question_id
     * @param $value_id
     * @return mixed
     */
    public function updateRSVPTemplateQuestionValue($summit_id, $template_id, $question_id, $value_id){
        try {

            if(!Request::isJson()) return $this->error400();
            $payload = Request::json()->all();

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $rules = SummitRSVPTemplateQuestionValueValidationRulesFactory::build($payload, true);
            // Creates a Validator instance and validates the data.
            $validation = Validator::make($payload, $rules);

            if ($validation->fails()) {
                $messages = $validation->messages()->toArray();

                return $this->error412
                (
                    $messages
                );
            }

            $value = $this->rsvp_template_service->updateQuestionValue($summit, $template_id, $question_id, $value_id, $payload);

            return $this->updated(SerializerRegistry::getInstance()->getSerializer($value)->serialize());
        }
        catch (ValidationException $ex1) {
            Log::warning($ex1);
            return $this->error412(array($ex1->getMessage()));
        }
        catch(EntityNotFoundException $ex2)
        {
            Log::warning($ex2);
            return $this->error404(array('message'=> $ex2->getMessage()));
        }
        catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    #[OA\Delete(
        path: "/api/v1/summits/{id}/rsvp-templates/{template_id}/questions/{question_id}/values/{value_id}",
        description: "Delete a value/option from a multi-select question",
        summary: 'Delete RSVP Template Question Value',
        operationId: 'deleteRSVPTemplateQuestionValue',
        tags: ['RSVP Templates', 'RSVP Template Question Values'],
        security: [
            [
                'summit_rsvp_templates_oauth2' => [
                    SummitScopes::WriteSummitData,
                    SummitScopes::WriteRSVPTemplateData,
                ]
            ]
        ],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
            ]
        ],
        parameters: [
            new OA\Parameter(
                name: 'access_token',
                in: 'query',
                required: false,
                description: 'OAuth2 access token (alternative to Authorization: Bearer)',
                schema: new OA\Schema(type: 'string', example: 'eyJhbGciOi...'),
            ),
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string'),
                description: 'The summit id'
            ),
            new OA\Parameter(
                name: 'template_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The RSVP template id'
            ),
            new OA\Parameter(
                name: 'question_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The question id'
            ),
            new OA\Parameter(
                name: 'value_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The value id'
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_NO_CONTENT,
                description: "Value deleted"
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    /**
     * @param $summit_id
     * @param $template_id
     * @param $question_id
     * @param $value_id
     * @return mixed
     */
    public function deleteRSVPTemplateQuestionValue($summit_id, $template_id, $question_id, $value_id){
        try {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $this->rsvp_template_service->deleteQuestionValue($summit, $template_id, $question_id, $value_id);

            return $this->deleted();
        }
        catch (ValidationException $ex1) {
            Log::warning($ex1);
            return $this->error412(array($ex1->getMessage()));
        }
        catch(EntityNotFoundException $ex2)
        {
            Log::warning($ex2);
            return $this->error404(array('message'=> $ex2->getMessage()));
        }
        catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }
}
