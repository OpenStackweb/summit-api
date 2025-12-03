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
use App\Models\Foundation\Summit\Events\Presentations\TrackQuestions\TrackMultiValueQuestionTemplate;
use App\Models\Foundation\Summit\Events\Presentations\TrackQuestions\TrackQuestionTemplateConstants;
use App\Models\Foundation\Summit\Repositories\ITrackQuestionTemplateRepository;
use App\Security\SummitScopes;
use App\Services\Model\ITrackQuestionTemplateService;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use libs\utils\PaginationValidationRules;
use OpenApi\Attributes as OA;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use Exception;
use models\oauth2\IResourceServerContext;
use ModelSerializers\SerializerRegistry;
use Illuminate\Support\Facades\Validator;
use utils\Filter;
use utils\FilterParser;
use utils\OrderParser;
use utils\PagingInfo;
/**
 * Class OAuth2TrackQuestionsTemplateApiController
 * @package App\Http\Controllers
 */
final class OAuth2TrackQuestionsTemplateApiController extends OAuth2ProtectedController
{
    /**
     * @var ITrackQuestionTemplateService
     */
    private $track_question_template_service;

    /**
     * @var ITrackQuestionTemplateRepository
     */
    private $track_question_template_repository;

    /**
     * OAuth2TrackQuestionsTemplateApiController constructor.
     * @param ITrackQuestionTemplateService $track_question_template_service
     * @param ITrackQuestionTemplateRepository $track_question_template_repository
     * @param IResourceServerContext $resource_server_context
     */
    public function __construct
    (
        ITrackQuestionTemplateService $track_question_template_service,
        ITrackQuestionTemplateRepository $track_question_template_repository,
        IResourceServerContext $resource_server_context
    )
    {
        parent::__construct($resource_server_context);
        $this->track_question_template_repository = $track_question_template_repository;
        $this->track_question_template_service = $track_question_template_service;
    }

    /**
     * @return mixed
     */
    #[OA\Get(
        path: '/api/v1/track-question-templates',
        operationId: 'getAllTrackQuestionTemplates',
        summary: 'Get all track question templates',
        description: 'Returns a paginated list of track question templates',
        tags: ['Track Question Templates'],
        x: [
            'authz_groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
            ]
        ],
        security: [['track_question_templates_oauth2' => [
            SummitScopes::ReadAllSummitData,
        ]]],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 5)),
            new OA\Parameter(name: 'filter', in: 'query', required: false, description: 'Filter by name, label or class_name', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'order', in: 'query', required: false, description: 'Order by id, name, or label', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'expand', in: 'query', required: false, description: 'Expand relations (tracks)', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Success',
                content: new OA\JsonContent(ref: '#/components/schemas/PaginatedTrackQuestionTemplatesResponse')
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    public function getTrackQuestionTemplates(){
        $values = Request::all();
        $rules  = PaginationValidationRules::get();

        try {

            $validation = Validator::make($values, $rules);

            if ($validation->fails()) {
                $ex = new ValidationException();
                throw $ex->setMessages($validation->messages()->toArray());
            }

            // default values
            $page     = 1;
            $per_page = 5;

            if (Request::has('page')) {
                $page     = intval(Request::input('page'));
                $per_page = intval(Request::input('per_page'));
            }

            $filter = null;

            if (Request::has('filter')) {
                $filter = FilterParser::parse(Request::input('filter'), [
                    'name'  => ['=@', '=='],
                    'label' => ['=@', '=='],
                    'class_name' => ['=='],
                ]);
            }

            if(is_null($filter)) $filter = new Filter();

            $filter->validate([
                'class_name'      => sprintf('sometimes|in:%s',implode(',', TrackQuestionTemplateConstants::$valid_class_names)),
                'name'            => 'sometimes|string',
                'label'           => 'sometimes|string',
            ], [
                'class_name.in' =>  sprintf
                (
                    ":attribute has an invalid value ( valid values are %s )",
                    implode(", ", TrackQuestionTemplateConstants::$valid_class_names)
                ),
            ]);

            $order = null;

            if (Request::has('order'))
            {
                $order = OrderParser::parse(Request::input('order'), [

                    'id',
                    'name',
                    'label',
                ]);
            }

            $data = $this->track_question_template_repository->getAllByPage(new PagingInfo($page, $per_page), $filter, $order);

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
            return $this->error412(array( $ex1->getMessage()));
        }
        catch (EntityNotFoundException $ex2)
        {
            Log::warning($ex2);
            return $this->error404(array('message' => $ex2->getMessage()));
        }
        catch(\HTTP401UnauthorizedException $ex3)
        {
            Log::warning($ex3);
            return $this->error401();
        }
        catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    /**
     * @return mixed
     */
    #[OA\Post(
        path: '/api/v1/track-question-templates',
        operationId: 'createTrackQuestionTemplate',
        summary: 'Create a new track question template',
        description: 'Creates a new track question template',
        tags: ['Track Question Templates'],
        x: [
            'authz_groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
            ]
        ],
        security: [['track_question_templates_oauth2' => [
            SummitScopes::WriteSummitData,
            SummitScopes::WriteTrackQuestionTemplateData,
        ]]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/TrackQuestionTemplateRequest')
        ),
        parameters: [
            new OA\Parameter(name: 'expand', in: 'query', required: false, description: 'Expand relations', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_CREATED,
                description: 'Track Question Template Created',
                content: new OA\JsonContent(ref: '#/components/schemas/TrackQuestionTemplate')
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: "Bad Request"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "not found"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    public function addTrackQuestionTemplate(){
        try {

            if(!Request::isJson()) return $this->error400();
            $payload = Request::json()->all();

            $rules = TrackQuestionTemplateValidationRulesFactory::build($payload);
            // Creates a Validator instance and validates the data.
            $validation = Validator::make($payload, $rules);

            if ($validation->fails()) {
                $messages = $validation->messages()->toArray();

                return $this->error412
                (
                    $messages
                );
            }

            $question = $this->track_question_template_service->addTrackQuestionTemplate($payload);

            return $this->created(SerializerRegistry::getInstance()->getSerializer($question)->serialize(
                Request::input('expand', '')
            ));
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
     * @param $track_question_template_id
     * @return mixed
     */
    #[OA\Get(
        path: '/api/v1/track-question-templates/{track_question_template_id}',
        operationId: 'getTrackQuestionTemplate',
        summary: 'Get a track question template by id',
        description: 'Returns a single track question template',
        tags: ['Track Question Templates'],
        x: [
            'authz_groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
            ]
        ],
        security: [['track_question_templates_oauth2' => [
            SummitScopes::ReadAllSummitData,
        ]]],
        parameters: [
            new OA\Parameter(name: 'track_question_template_id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'), description: 'The track question template id'),
            new OA\Parameter(name: 'expand', in: 'query', required: false, description: 'Expand relations', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Success',
                content: new OA\JsonContent(ref: '#/components/schemas/TrackQuestionTemplate')
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "not found"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    public function getTrackQuestionTemplate($track_question_template_id){
        try {

            $track_question_template = $this->track_question_template_repository->getById($track_question_template_id);
            if (is_null($track_question_template)) return $this->error404();

            return $this->ok(SerializerRegistry::getInstance()->getSerializer($track_question_template)->serialize(
                Request::input('expand', '')
            ));
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
     * @param $track_question_template_id
     * @return mixed
     */
    #[OA\Put(
        path: '/api/v1/track-question-templates/{track_question_template_id}',
        operationId: 'updateTrackQuestionTemplate',
        summary: 'Update a track question template',
        description: 'Updates an existing track question template',
        tags: ['Track Question Templates'],
        x: [
            'authz_groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
            ]
        ],
        security: [['track_question_templates_oauth2' => [
            SummitScopes::WriteSummitData,
            SummitScopes::WriteTrackQuestionTemplateData,
        ]]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/TrackQuestionTemplateRequest')
        ),
        parameters: [
            new OA\Parameter(name: 'track_question_template_id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'), description: 'The track question template id'),
            new OA\Parameter(name: 'expand', in: 'query', required: false, description: 'Expand relations', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Track Question Template Updated',
                content: new OA\JsonContent(ref: '#/components/schemas/TrackQuestionTemplate')
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: "Bad Request"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "not found"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    public function updateTrackQuestionTemplate($track_question_template_id){
        try {

            if(!Request::isJson()) return $this->error400();
            $payload = Request::json()->all();

            $rules = TrackQuestionTemplateValidationRulesFactory::build($payload, true);
            // Creates a Validator instance and validates the data.
            $validation = Validator::make($payload, $rules);

            if ($validation->fails()) {
                $messages = $validation->messages()->toArray();

                return $this->error412
                (
                    $messages
                );
            }

            $question = $this->track_question_template_service->updateTrackQuestionTemplate($track_question_template_id, $payload);

            return $this->updated(SerializerRegistry::getInstance()->getSerializer($question)->serialize(
                Request::input('expand', '')
            ));
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
     * @param $track_question_template_id
     * @return mixed
     */
    #[OA\Delete(
        path: '/api/v1/track-question-templates/{track_question_template_id}',
        operationId: 'deleteTrackQuestionTemplate',
        summary: 'Delete a track question template',
        description: 'Deletes a track question template',
        tags: ['Track Question Templates'],
        x: [
            'authz_groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
            ]
        ],
        security: [['track_question_templates_oauth2' => [
            SummitScopes::WriteSummitData,
            SummitScopes::WriteTrackQuestionTemplateData,
        ]]],
        parameters: [
            new OA\Parameter(name: 'track_question_template_id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'), description: 'The track question template id'),
        ],
        responses: [
            new OA\Response(response: Response::HTTP_NO_CONTENT, description: 'Track Question Template Deleted'),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "not found"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    public function deleteTrackQuestionTemplate($track_question_template_id){
        try {

            $this->track_question_template_service->deleteTrackQuestionTemplate($track_question_template_id);
            return $this->deleted();
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
     * @return mixed
     */
    #[OA\Get(
        path: '/api/v1/track-question-templates/metadata',
        operationId: 'getTrackQuestionTemplateMetadata',
        summary: 'Get track question templates metadata',
        description: 'Returns metadata about available track question template types',
        tags: ['Track Question Templates'],
        x: [
            'authz_groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
            ]
        ],
        security: [['track_question_templates_oauth2' => [
            SummitScopes::ReadAllSummitData,
        ]]],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Success',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'class_names', type: 'array', items: new OA\Items(type: 'string')),
                    ]
                )
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    public function getTrackQuestionTemplateMetadata(){
        return $this->ok
        (
            $this->track_question_template_repository->getQuestionsMetadata()
        );
    }

    /**
     * values endpoints
     */

    /**
     * @param $track_question_template_id
     * @param $track_question_template_value_id
     * @return mixed
     */
    #[OA\Get(
        path: '/api/v1/track-question-templates/{track_question_template_id}/values/{track_question_template_value_id}',
        operationId: 'getTrackQuestionTemplateValue',
        summary: 'Get a track question template value',
        description: 'Returns a single track question template value',
        tags: ['Track Question Templates'],
        x: [
            'authz_groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
            ]
        ],
        security: [['track_question_templates_oauth2' => [
            SummitScopes::ReadAllSummitData,
        ]]],
        parameters: [
            new OA\Parameter(name: 'track_question_template_id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'), description: 'The track question template id'),
            new OA\Parameter(name: 'track_question_template_value_id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'), description: 'The track question template value id'),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Success',
                content: new OA\JsonContent(ref: '#/components/schemas/TrackQuestionValueTemplate')
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "not found"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    public function getTrackQuestionTemplateValue($track_question_template_id, $track_question_template_value_id){
        try {

            $track_question_template = $this->track_question_template_repository->getById($track_question_template_id);
            if (is_null($track_question_template)) return $this->error404();

            if (!$track_question_template instanceof TrackMultiValueQuestionTemplate) return $this->error404();

            $value = $track_question_template->getValueById($track_question_template_value_id);
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

    /**
     * @param $track_question_template_id
     * @return mixed
     */
    #[OA\Post(
        path: '/api/v1/track-question-templates/{track_question_template_id}/values',
        operationId: 'createTrackQuestionTemplateValue',
        summary: 'Add a value to a track question template',
        description: 'Adds a new value to a multi-value track question template',
        tags: ['Track Question Templates'],
        x: [
            'authz_groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
            ]
        ],
        security: [['track_question_templates_oauth2' => [
            SummitScopes::WriteSummitData,
            SummitScopes::WriteTrackQuestionTemplateData,
        ]]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/TrackQuestionValueTemplateRequest')
        ),
        parameters: [
            new OA\Parameter(name: 'track_question_template_id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'), description: 'The track question template id'),
            new OA\Parameter(name: 'expand', in: 'query', required: false, description: 'Expand relations', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_CREATED,
                description: 'Track Question Template Value Created',
                content: new OA\JsonContent(ref: '#/components/schemas/TrackQuestionValueTemplate')
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: "Bad Request"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "not found"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    public function addTrackQuestionTemplateValue($track_question_template_id){
        try {

            if(!Request::isJson()) return $this->error400();
            $payload = Request::json()->all();

            $rules = TrackQuestionValueTemplateValidationRulesFactory::build($payload);
            // Creates a Validator instance and validates the data.
            $validation = Validator::make($payload, $rules);

            if ($validation->fails()) {
                $messages = $validation->messages()->toArray();

                return $this->error412
                (
                    $messages
                );
            }

            $value = $this->track_question_template_service->addTrackQuestionValueTemplate
            (
                $track_question_template_id,
                $payload
            );

            return $this->created(SerializerRegistry::getInstance()->getSerializer($value)->serialize(
                Request::input('expand', '')
            ));
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
     * @param $track_question_template_id
     * @param $track_question_template_value_id
     * @return mixed
     */
    #[OA\Put(
        path: '/api/v1/track-question-templates/{track_question_template_id}/values/{track_question_template_value_id}',
        operationId: 'updateTrackQuestionTemplateValue',
        summary: 'Update a track question template value',
        description: 'Updates an existing track question template value',
        tags: ['Track Question Templates'],
        x: [
            'authz_groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
            ]
        ],
        security: [['track_question_templates_oauth2' => [
            SummitScopes::WriteSummitData,
            SummitScopes::WriteTrackQuestionTemplateData,
        ]]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/TrackQuestionValueTemplateRequest')
        ),
        parameters: [
            new OA\Parameter(name: 'track_question_template_id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'), description: 'The track question template id'),
            new OA\Parameter(name: 'track_question_template_value_id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'), description: 'The track question template value id'),
            new OA\Parameter(name: 'expand', in: 'query', required: false, description: 'Expand relations', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Track Question Template Value Updated',
                content: new OA\JsonContent(ref: '#/components/schemas/TrackQuestionValueTemplate')
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: "Bad Request"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "not found"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    public function updateTrackQuestionTemplateValue($track_question_template_id, $track_question_template_value_id){
        try {

            if(!Request::isJson()) return $this->error400();
            $payload = Request::json()->all();

            $rules = TrackQuestionValueTemplateValidationRulesFactory::build($payload, true);
            // Creates a Validator instance and validates the data.
            $validation = Validator::make($payload, $rules);

            if ($validation->fails()) {
                $messages = $validation->messages()->toArray();

                return $this->error412
                (
                    $messages
                );
            }

            $value = $this->track_question_template_service->updateTrackQuestionValueTemplate
            (
                $track_question_template_id,
                $track_question_template_value_id,
                $payload
            );

            return $this->updated(SerializerRegistry::getInstance()->getSerializer($value)->serialize(
                Request::input('expand', '')
            ));
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
     * @param $track_question_template_id
     * @param $track_question_template_value_id
     * @return mixed
     */
    #[OA\Delete(
        path: '/api/v1/track-question-templates/{track_question_template_id}/values/{track_question_template_value_id}',
        operationId: 'deleteTrackQuestionTemplateValue',
        summary: 'Delete a track question template value',
        description: 'Deletes a track question template value',
        tags: ['Track Question Templates'],
        x: [
            'authz_groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
            ]
        ],
        security: [['track_question_templates_oauth2' => [
            SummitScopes::WriteSummitData,
            SummitScopes::WriteTrackQuestionTemplateData,
        ]]],
        parameters: [
            new OA\Parameter(name: 'track_question_template_id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'), description: 'The track question template id'),
            new OA\Parameter(name: 'track_question_template_value_id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'), description: 'The track question template value id'),
        ],
        responses: [
            new OA\Response(response: Response::HTTP_NO_CONTENT, description: 'Track Question Template Value Deleted'),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "not found"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    public function deleteTrackQuestionTemplateValue($track_question_template_id, $track_question_template_value_id){
        try {
            $this->track_question_template_service->deleteTrackQuestionValueTemplate
            (
                $track_question_template_id,
                $track_question_template_value_id
            );
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
