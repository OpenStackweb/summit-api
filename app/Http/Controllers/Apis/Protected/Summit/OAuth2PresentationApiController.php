<?php

namespace App\Http\Controllers;

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

use App\Http\Utils\FileTypes;
use App\Http\Utils\MultipartFormDataCleaner;
use App\Jobs\VideoStreamUrlMUXProcessingForSummitJob;
use App\Models\Foundation\Main\IGroup;
use App\Models\Foundation\Summit\Repositories\ISummitPresentationCommentRepository;
use App\Models\Foundation\Summit\SelectionPlan;
use App\ModelSerializers\SerializerUtils;
use App\Security\SummitScopes;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request as LaravelRequest;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Validator;
use libs\utils\HTMLCleaner;
use models\exceptions\ValidationException;
use models\main\IMemberRepository;
use models\oauth2\IResourceServerContext;
use models\summit\ISummitEventRepository;
use models\summit\ISummitRepository;
use models\summit\Presentation;
use models\summit\SummitPresentationComment;
use models\utils\IEntity;
use ModelSerializers\IPresentationSerializerTypes;
use ModelSerializers\SerializerRegistry;
use OpenApi\Attributes as OA;
use services\model\IPresentationService;
use utils\Filter;
use utils\FilterElement;
use utils\FilterParser;
use utils\PagingInfo;
use utils\PagingResponse;

/**
 * Class OAuth2PresentationApiController
 * @package App\Http\Controllers
 */
final class OAuth2PresentationApiController extends OAuth2ProtectedController
{
    use GetAndValidateJsonPayload;

    use RequestProcessor;

    use GetAndValidateJsonPayload;

    /**
     * @var ISummitRepository
     */
    private $summit_repository;

    /**
     * @var IPresentationService
     */
    private $presentation_service;

    /**
     * @var ISummitEventRepository
     */
    private $presentation_repository;

    /**
     * @var IMemberRepository
     */
    private $member_repository;

    /**
     * @var ISummitPresentationCommentRepository
     */
    private $presentation_comments_repository;

    /**
     * OAuth2PresentationApiController constructor.
     * @param IPresentationService $presentation_service
     * @param ISummitRepository $summit_repository
     * @param ISummitEventRepository $presentation_repository
     * @param IMemberRepository $member_repository
     * @params ISummitPresentationCommentRepository $presentation_comments_repository
     * @param IResourceServerContext $resource_server_context
     */
    public function __construct
    (
        IPresentationService                 $presentation_service,
        ISummitRepository                    $summit_repository,
        ISummitEventRepository               $presentation_repository,
        IMemberRepository                    $member_repository,
        ISummitPresentationCommentRepository $presentation_comments_repository,
        IResourceServerContext               $resource_server_context
    )
    {
        parent::__construct($resource_server_context);
        $this->presentation_repository = $presentation_repository;
        $this->presentation_service = $presentation_service;
        $this->member_repository = $member_repository;
        $this->summit_repository = $summit_repository;
        $this->presentation_comments_repository = $presentation_comments_repository;
    }

    //presentations

    //videos

    #[OA\Get(
        path: "/api/v1/summits/{id}/presentations/{presentation_id}/videos",
        summary: "Get all videos from a presentation",
        operationId: "getPresentationVideos",
        tags: ['Presentations'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'presentation_id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "OK",
                content: new OA\JsonContent(
                    type: "array",
                    items: new OA\Items(ref: "#/components/schemas/PresentationVideo")
                )
            ),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    public function getPresentationVideos($summit_id, $presentation_id)
    {
        return $this->processRequest(function () use ($summit_id, $presentation_id) {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $presentation = $this->presentation_repository->getById(intval($presentation_id));

            if (!$presentation instanceof Presentation) return $this->error404();

            $videos = $presentation->getVideos();

            $items = [];
            foreach ($videos as $i) {
                if ($i instanceof IEntity) {
                    $i = SerializerRegistry::getInstance()->getSerializer($i)->serialize
                    (
                        SerializerUtils::getExpand(),
                        SerializerUtils::getFields(),
                        SerializerUtils::getRelations()
                    );
                }
                $items[] = $i;
            }

            return $this->ok($items);

        });
    }

    #[OA\Get(
        path: "/api/v1/summits/{id}/presentations/{presentation_id}/videos/{video_id}",
        summary: "Get a video from a presentation",
        operationId: "getPresentationVideo",
        tags: ['Presentations'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'presentation_id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'video_id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "OK",
                content: new OA\JsonContent(ref: "#/components/schemas/PresentationVideo")
            ),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    public function getPresentationVideo($summit_id, $presentation_id, $video_id)
    {
        return $this->processRequest(function () use ($summit_id, $presentation_id, $video_id) {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $presentation = $this->presentation_repository->getById(intval($presentation_id));

            if (!$presentation instanceof Presentation) return $this->error404();

            $video = $presentation->getVideoBy(intval($video_id));

            if (is_null($video)) return $this->error404();

            return $this->ok(SerializerRegistry::getInstance()->getSerializer($video)->serialize(
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));

        });
    }

    #[OA\Post(
        path: "/api/v1/summits/{id}/presentations/{presentation_id}/videos",
        summary: "Add a video to a presentation",
        operationId: "addPresentationVideo",
        security: [['summit_oauth2' => [SummitScopes::WritePresentationData]]],
        tags: ['Presentations'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'presentation_id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: "#/components/schemas/PresentationVideoRequest")
        ),
        responses: [
            new OA\Response(
                response: Response::HTTP_CREATED,
                description: "Created",
                content: new OA\JsonContent(ref: "#/components/schemas/PresentationVideo")
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    public function addVideo(LaravelRequest $request, $summit_id, $presentation_id)
    {
        return $this->processRequest(function () use ($request, $summit_id, $presentation_id) {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $payload = $this->getJsonPayload(PresentationVideoValidationRulesFactory::build($this->getJsonData()), true);

            $video = $this->presentation_service->addVideoTo(intval($presentation_id), HTMLCleaner::cleanData($payload,
                [
                    'name',
                    'description',
                ]));

            return $this->created(SerializerRegistry::getInstance()->getSerializer($video)->serialize(
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));
        });
    }

    #[OA\Put(
        path: "/api/v1/summits/{id}/presentations/{presentation_id}/videos/{video_id}",
        summary: "Update a video from a presentation",
        operationId: "updatePresentationVideo",
        security: [['summit_oauth2' => [SummitScopes::WritePresentationData]]],
        tags: ['Presentations'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'presentation_id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'video_id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: "#/components/schemas/PresentationVideoRequest")
        ),
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "OK",
                content: new OA\JsonContent(ref: "#/components/schemas/PresentationVideo")
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    public function updateVideo(LaravelRequest $request, $summit_id, $presentation_id, $video_id)
    {
        return $this->processRequest(function () use ($request, $summit_id, $presentation_id, $video_id) {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $payload = $this->getJsonPayload(PresentationVideoValidationRulesFactory::build($this->getJsonData(), true), true);

            $video = $this->presentation_service->updateVideo(intval($presentation_id), intval($video_id), HTMLCleaner::cleanData($payload, [
                'name',
                'description',
            ]));

            return $this->updated(SerializerRegistry::getInstance()->getSerializer($video)->serialize(
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));

        });
    }

    #[OA\Delete(
        path: "/api/v1/summits/{id}/presentations/{presentation_id}/videos/{video_id}",
        summary: "Delete a video from a presentation",
        operationId: "deletePresentationVideo",
        security: [['summit_oauth2' => [SummitScopes::WritePresentationData]]],
        tags: ['Presentations'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'presentation_id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'video_id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: Response::HTTP_NO_CONTENT, description: "No Content"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    public function deleteVideo($summit_id, $presentation_id, $video_id)
    {
        return $this->processRequest(function () use ($summit_id, $presentation_id, $video_id) {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $this->presentation_service->deleteVideo(intval($presentation_id), intval($video_id));

            return $this->deleted();

        });
    }

    #[OA\Post(
        path: "/api/v1/summits/{id}/presentations",
        summary: "Submit a presentation",
        operationId: "submitPresentation",
        security: [['summit_oauth2' => [SummitScopes::WritePresentationData]]],
        tags: ['Presentations'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: "#/components/schemas/PresentationSubmissionRequest")
        ),
        responses: [
            new OA\Response(
                response: Response::HTTP_CREATED,
                description: "Created",
                content: new OA\JsonContent(ref: "#/components/schemas/Presentation")
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    public function submitPresentation($summit_id)
    {

        return $this->processRequest(function () use ($summit_id) {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $current_member = $this->resource_server_context->getCurrentUser();
            if (is_null($current_member)) return $this->error403();

            $payload = $this->getJsonPayload(SummitEventValidationRulesFactory::buildForSubmission($this->getJsonData()), true);

            $presentation = $this->presentation_service->submitPresentation($summit, HTMLCleaner::cleanData($payload, [
                'title',
                'description',
                'social_summary',
                'attendees_expected_learnt',
            ]));

            return $this->created(SerializerRegistry::getInstance()->getSerializer($presentation)->serialize(
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));

        });
    }

    #[OA\Get(
        path: "/api/v1/summits/{id}/presentations/{presentation_id}",
        summary: "Get a presentation submission",
        operationId: "getPresentationSubmission",
        tags: ['Presentations'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'presentation_id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "OK",
                content: new OA\JsonContent(ref: "#/components/schemas/PresentationSubmission")
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    public function getPresentationSubmission($summit_id, $presentation_id)
    {
        return $this->processRequest(function () use ($summit_id, $presentation_id) {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $current_member = $this->resource_server_context->getCurrentUser();
            if (is_null($current_member)) return $this->error403();

            $presentation = $summit->getEvent(intval($presentation_id));
            if(!$presentation instanceof Presentation) return $this->error404();

            if(!$presentation->memberCanEdit($current_member))
                return $this->error403();

            return $this->updated(SerializerRegistry::getInstance()->getSerializer
            (
                $presentation,
                IPresentationSerializerTypes::Submission
            )->serialize
            (
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));
        });
    }

    #[OA\Put(
        path: "/api/v1/summits/{id}/presentations/{presentation_id}",
        summary: "Update a presentation submission",
        operationId: "updatePresentationSubmission",
        security: [['summit_oauth2' => [SummitScopes::WritePresentationData]]],
        tags: ['Presentations'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'presentation_id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: "#/components/schemas/PresentationSubmissionRequest")
        ),
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "OK",
                content: new OA\JsonContent(ref: "#/components/schemas/Presentation")
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    public function updatePresentationSubmission($summit_id, $presentation_id)
    {
        return $this->processRequest(function () use ($summit_id, $presentation_id) {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $current_member = $this->resource_server_context->getCurrentUser();
            if (is_null($current_member)) return $this->error403();

            $payload = $this->getJsonPayload(SummitEventValidationRulesFactory::buildForSubmission($this->getJsonData(), true), true);

            Log::debug(sprintf("SummitEventApiController::updatePresentationSubmission presentation_id %s payload %s", $presentation_id, json_encode($payload)));
            $presentation = $this->presentation_service->updatePresentationSubmission
            (
                $summit,
                intval($presentation_id),
                HTMLCleaner::cleanData($payload, [
                    'title',
                    'description',
                    'social_summary',
                    'attendees_expected_learnt',
                ])
            );

            return $this->updated(SerializerRegistry::getInstance()->getSerializer($presentation)->serialize
            (
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));
        });
    }

    #[OA\Put(
        path: "/api/v1/summits/{id}/presentations/{presentation_id}/completed",
        summary: "Mark a presentation submission as completed",
        operationId: "completePresentationSubmission",
        security: [['summit_oauth2' => [SummitScopes::WritePresentationData]]],
        tags: ['Presentations'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'presentation_id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "OK",
                content: new OA\JsonContent(ref: "#/components/schemas/Presentation")
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    public function completePresentationSubmission($summit_id, $presentation_id)
    {
        return $this->processRequest(function () use ($summit_id, $presentation_id) {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $current_member = $this->resource_server_context->getCurrentUser();
            if (is_null($current_member)) return $this->error403();

            $presentation = $this->presentation_service->completePresentationSubmission
            (
                $summit,
                intval($presentation_id)
            );

            return $this->updated(SerializerRegistry::getInstance()->getSerializer($presentation)->serialize(
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));
        });
    }

    #[OA\Delete(
        path: "/api/v1/summits/{id}/presentations/{presentation_id}",
        summary: "Delete a presentation",
        operationId: "deletePresentation",
        security: [['summit_oauth2' => [SummitScopes::WritePresentationData]]],
        tags: ['Presentations'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'presentation_id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 204, description: "No Content"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    public function deletePresentation($summit_id, $presentation_id)
    {
        return $this->processRequest(function () use ($summit_id, $presentation_id) {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $current_member = $this->resource_server_context->getCurrentUser();
            if (is_null($current_member)) return $this->error403();

            $this->presentation_service->deletePresentation
            (
                $summit,
                intval($presentation_id),
                $this->resource_server_context->getCurrentUser()
            );

            return $this->deleted();

        });
    }

    // Slides

    #[OA\Get(
        path: "/api/v1/summits/{id}/presentations/{presentation_id}/slides",
        summary: "Get all slides from a presentation",
        operationId: "getPresentationSlides",
        tags: ['Presentations'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'presentation_id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "OK",
                content: new OA\JsonContent(
                    type: "array",
                    items: new OA\Items(ref: "#/components/schemas/PresentationSlide")
                )
            ),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    public function getPresentationSlides($summit_id, $presentation_id)
    {
        return $this->processRequest(function () use ($summit_id, $presentation_id) {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $presentation = $this->presentation_repository->getById(intval($presentation_id));

            if (!$presentation instanceof Presentation) return $this->error404();

            $slides = $presentation->getSlides();

            $items = [];
            foreach ($slides as $i) {
                if ($i instanceof IEntity) {
                    $i = SerializerRegistry::getInstance()->getSerializer($i)->serialize(
                        SerializerUtils::getExpand(),
                        SerializerUtils::getFields(),
                        SerializerUtils::getRelations()
                    );
                }
                $items[] = $i;
            }

            return $this->ok($items);

        });
    }

    #[OA\Get(
        path: "/api/v1/summits/{id}/presentations/{presentation_id}/slides/{slide_id}",
        summary: "Get a slide from a presentation",
        operationId: "getPresentationSlide",
        tags: ['Presentations'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'presentation_id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'slide_id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "OK",
                content: new OA\JsonContent(ref: "#/components/schemas/PresentationSlide")
            ),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    public function getPresentationSlide($summit_id, $presentation_id, $slide_id)
    {
        return $this->processRequest(function () use ($summit_id, $presentation_id, $slide_id) {
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $presentation = $this->presentation_repository->getById(intval($presentation_id));

            if (!$presentation instanceof Presentation) return $this->error404();

            $slide = $presentation->getSlideBy(intval($slide_id));

            if (is_null($slide)) return $this->error404();

            return $this->ok(SerializerRegistry::getInstance()->getSerializer($slide)->serialize(
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));

        });
    }

    #[OA\Post(
        path: "/api/v1/summits/{id}/presentations/{presentation_id}/slides",
        summary: "Add a slide to a presentation",
        operationId: "addPresentationSlide",
        security: [['summit_oauth2' => [SummitScopes::WritePresentationData]]],
        tags: ['Presentations'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'presentation_id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: "multipart/form-data",
                schema: new OA\Schema(ref: "#/components/schemas/PresentationSlideRequest")
            )
        ),
        responses: [
            new OA\Response(
                response: Response::HTTP_CREATED,
                description: "Created",
                content: new OA\JsonContent(ref: "#/components/schemas/PresentationSlide")
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    public function addPresentationSlide(LaravelRequest $request, $summit_id, $presentation_id)
    {
        return $this->processRequest(function () use ($request, $summit_id, $presentation_id) {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $current_member = $this->resource_server_context->getCurrentUser();
            if (is_null($current_member))
                return $this->error403();

            $isAdmin = $current_member->isAdmin() || $current_member->hasPermissionForOnGroup($summit, IGroup::SummitAdministrators);
            if (!$isAdmin) {
                // check if we could edit presentation
                $presentation = $summit->getEvent($presentation_id);
                if (!$presentation instanceof Presentation)
                    return $this->error404();
                if (!$current_member->hasSpeaker() || !$presentation->canEdit($current_member->getSpeaker()))
                    return $this->error403();
            }

            $data = $request->all();
            $data = MultipartFormDataCleaner::cleanBool('display_on_site', $data);
            $data = MultipartFormDataCleaner::cleanBool('featured', $data);

            // Creates a Validator instance and validates the data.
            $validation = Validator::make($data, PresentationSlideValidationRulesFactory::build($data));

            if ($validation->fails()) {
                $ex = new ValidationException;
                $ex->setMessages($validation->messages()->toArray());
                throw $ex;
            }

            $fields = [
                'name',
                'description',
            ];

            $slide = $this->presentation_service->addSlideTo
            (
                $request,
                intval($presentation_id),
                HTMLCleaner::cleanData($data, $fields),
                array_merge(FileTypes::ImagesExntesions, FileTypes::SlidesExtensions),
                intval(Config::get("mediaupload.slides_max_file_size"))
            );

            return $this->created(SerializerRegistry::getInstance()->getSerializer($slide)->serialize(
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));
        });
    }

    #[OA\Put(
        path: "/api/v1/summits/{id}/presentations/{presentation_id}/slides/{slide_id}",
        summary: "Update a slide from a presentation",
        operationId: "updatePresentationSlide",
        security: [['summit_oauth2' => [SummitScopes::WritePresentationData]]],
        tags: ['Presentations'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'presentation_id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'slide_id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: "multipart/form-data",
                schema: new OA\Schema(ref: "#/components/schemas/PresentationSlideRequest")
            )
        ),
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "OK",
                content: new OA\JsonContent(ref: "#/components/schemas/PresentationSlide")
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    public function updatePresentationSlide(LaravelRequest $request, $summit_id, $presentation_id, $slide_id)
    {
        return $this->processRequest(function () use ($request, $summit_id, $presentation_id, $slide_id) {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $current_member = $this->resource_server_context->getCurrentUser();
            if (is_null($current_member)) return $this->error403();
            $isAdmin = $current_member->isAdmin() || $current_member->hasPermissionForOnGroup($summit, IGroup::SummitAdministrators);
            if (!$isAdmin) {
                // check if we could edit presentation
                $presentation = $summit->getEvent(intval($presentation_id));
                if (!$presentation instanceof Presentation)
                    return $this->error404();
                if (!$current_member->hasSpeaker() || !$presentation->canEdit($current_member->getSpeaker()))
                    return $this->error403();
            }

            $data = $request->all();
            $data = MultipartFormDataCleaner::cleanBool('display_on_site', $data);
            $data = MultipartFormDataCleaner::cleanBool('featured', $data);
            $data = MultipartFormDataCleaner::cleanInt('order', $data);

            // Creates a Validator instance and validates the data.
            $validation = Validator::make($data, PresentationSlideValidationRulesFactory::build($data, true));

            if ($validation->fails()) {
                $ex = new ValidationException;
                $ex->setMessages($validation->messages()->toArray());
                throw $ex;
            }

            $fields = [
                'name',
                'description',
            ];

            $slide = $this->presentation_service->updateSlide
            (
                $request,
                intval($presentation_id),
                intval($slide_id),
                HTMLCleaner::cleanData($data, $fields),
                array_merge(FileTypes::ImagesExntesions, FileTypes::SlidesExtensions),
                intval(Config::get("mediaupload.slides_max_file_size"))
            );

            return $this->updated(SerializerRegistry::getInstance()->getSerializer($slide)->serialize(
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));
        });
    }

    #[OA\Delete(
        path: "/api/v1/summits/{id}/presentations/{presentation_id}/slides/{slide_id}",
        summary: "Delete a slide from a presentation",
        operationId: "deletePresentationSlide",
        security: [['summit_oauth2' => [SummitScopes::WritePresentationData]]],
        tags: ['Presentations'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'presentation_id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'slide_id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 204, description: "No Content"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    public function deletePresentationSlide($summit_id, $presentation_id, $slide_id)
    {
        return $this->processRequest(function () use ($summit_id, $presentation_id, $slide_id) {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $current_member = $this->resource_server_context->getCurrentUser();
            if (is_null($current_member)) return $this->error403();
            $isAdmin = $current_member->isAdmin() || $current_member->hasPermissionForOnGroup($summit, IGroup::SummitAdministrators);
            if (!$isAdmin) {
                // check if we could edit presentation
                $presentation = $summit->getEvent(intval($presentation_id));
                if (!$presentation instanceof Presentation)
                    return $this->error404();
                if (!$current_member->hasSpeaker() || !$presentation->canEdit($current_member->getSpeaker()))
                    return $this->error403();
            }

            $this->presentation_service->deleteSlide(intval($presentation_id), intval($slide_id));

            return $this->deleted();

        });
    }

    // Links

    #[OA\Get(
        path: "/api/v1/summits/{id}/presentations/{presentation_id}/links",
        summary: "Get all links from a presentation",
        operationId: "getPresentationLinks",
        tags: ['Presentations'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'presentation_id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "OK",
                content: new OA\JsonContent(
                    type: "array",
                    items: new OA\Items(ref: "#/components/schemas/PresentationLink")
                )
            ),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    public function getPresentationLinks($summit_id, $presentation_id)
    {
        return $this->processRequest(function () use ($summit_id, $presentation_id) {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $presentation = $this->presentation_repository->getById(intval($presentation_id));

            if (!$presentation instanceof Presentation) return $this->error404();

            $links = $presentation->getLinks();

            $items = [];
            foreach ($links as $i) {
                if ($i instanceof IEntity) {
                    $i = SerializerRegistry::getInstance()->getSerializer($i)->serialize(
                        SerializerUtils::getExpand(),
                        SerializerUtils::getFields(),
                        SerializerUtils::getRelations()
                    );
                }
                $items[] = $i;
            }

            return $this->ok($items);

        });
    }

    #[OA\Get(
        path: "/api/v1/summits/{id}/presentations/{presentation_id}/links/{link_id}",
        summary: "Get a link from a presentation",
        operationId: "getPresentationLink",
        tags: ['Presentations'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'presentation_id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'link_id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "OK",
                content: new OA\JsonContent(ref: "#/components/schemas/PresentationLink")
            ),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    public function getPresentationLink($summit_id, $presentation_id, $link_id)
    {
        return $this->processRequest(function () use ($summit_id, $presentation_id, $link_id) {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $presentation = $this->presentation_repository->getById(intval($presentation_id));

            if (!$presentation instanceof Presentation) return $this->error404();

            $link = $presentation->getLinkBy(intval($link_id));

            if (is_null($link)) return $this->error404();

            return $this->ok(SerializerRegistry::getInstance()->getSerializer($link)->serialize(
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));

        });

    }

    #[OA\Post(
        path: "/api/v1/summits/{id}/presentations/{presentation_id}/links",
        summary: "Add a link to a presentation",
        operationId: "addPresentationLink",
        security: [['summit_oauth2' => [SummitScopes::WritePresentationData]]],
        tags: ['Presentations'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'presentation_id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: "multipart/form-data",
                schema: new OA\Schema(ref: "#/components/schemas/PresentationLinkRequest")
            )
        ),
        responses: [
            new OA\Response(
                response: Response::HTTP_CREATED,
                description: "Created",
                content: new OA\JsonContent(ref: "#/components/schemas/PresentationLink")
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    public function addPresentationLink(LaravelRequest $request, $summit_id, $presentation_id)
    {
        return $this->processRequest(function () use ($request, $summit_id, $presentation_id) {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $current_member = $this->resource_server_context->getCurrentUser();
            if (is_null($current_member)) return $this->error403();
            $isAdmin = $current_member->isAdmin() || $current_member->hasPermissionForOnGroup($summit, IGroup::SummitAdministrators);
            if (!$isAdmin) {
                // check if we could edit presentation
                $presentation = $summit->getEvent(intval($presentation_id));
                if (!$presentation instanceof Presentation)
                    return $this->error404();
                if (!$current_member->hasSpeaker() || !$presentation->canEdit($current_member->getSpeaker()))
                    return $this->error403();
            }

            $data = $request->all();
            $data = MultipartFormDataCleaner::cleanBool('display_on_site', $data);
            $data = MultipartFormDataCleaner::cleanBool('featured', $data);

            // Creates a Validator instance and validates the data.
            $validation = Validator::make($data, PresentationLinkValidationRulesFactory::build($data));

            if ($validation->fails()) {
                $ex = new ValidationException;
                $ex->setMessages($validation->messages()->toArray());
                throw $ex;
            }

            $fields = [
                'name',
                'description',
            ];

            $link = $this->presentation_service->addLinkTo(intval($presentation_id), HTMLCleaner::cleanData($data, $fields));

            return $this->created(SerializerRegistry::getInstance()->getSerializer($link)->serialize(
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));

        });
    }

    #[OA\Put(
        path: "/api/v1/summits/{id}/presentations/{presentation_id}/links/{link_id}",
        summary: "Update a link from a presentation",
        operationId: "updatePresentationLink",
        security: [['summit_oauth2' => [SummitScopes::WritePresentationData]]],
        tags: ['Presentations'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'presentation_id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'link_id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: "multipart/form-data",
                schema: new OA\Schema(ref: "#/components/schemas/PresentationLinkRequest")
            )
        ),
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "OK",
                content: new OA\JsonContent(ref: "#/components/schemas/PresentationLink")
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    public function updatePresentationLink(LaravelRequest $request, $summit_id, $presentation_id, $link_id)
    {
        return $this->processRequest(function () use ($request, $summit_id, $presentation_id, $link_id) {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $current_member = $this->resource_server_context->getCurrentUser();
            if (is_null($current_member)) return $this->error403();
            $isAdmin = $current_member->isAdmin() || $current_member->hasPermissionForOnGroup($summit, IGroup::SummitAdministrators);
            if (!$isAdmin) {
                // check if we could edit presentation
                $presentation = $summit->getEvent(intval($presentation_id));
                if (!$presentation instanceof Presentation)
                    return $this->error404();
                if (!$current_member->hasSpeaker() || !$presentation->canEdit($current_member->getSpeaker()))
                    return $this->error403();
            }

            $data = $request->all();
            $data = MultipartFormDataCleaner::cleanBool('display_on_site', $data);
            $data = MultipartFormDataCleaner::cleanBool('featured', $data);
            $data = MultipartFormDataCleaner::cleanInt('order', $data);

            // Creates a Validator instance and validates the data.
            $validation = Validator::make($data, PresentationLinkValidationRulesFactory::build($data, true));

            if ($validation->fails()) {
                $ex = new ValidationException;
                $ex->setMessages($validation->messages()->toArray());
                throw $ex;
            }

            $fields = [
                'name',
                'description',
            ];

            $link = $this->presentation_service->updateLink(intval($presentation_id), intval($link_id), HTMLCleaner::cleanData($data, $fields));

            return $this->updated(SerializerRegistry::getInstance()->getSerializer($link)->serialize(
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));

        });
    }

    #[OA\Delete(
        path: "/api/v1/summits/{id}/presentations/{presentation_id}/links/{link_id}",
        summary: "Delete a link from a presentation",
        operationId: "deletePresentationLink",
        security: [['summit_oauth2' => [SummitScopes::WritePresentationData]]],
        tags: ['Presentations'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'presentation_id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'link_id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 204, description: "No Content"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    public function deletePresentationLink($summit_id, $presentation_id, $link_id)
    {
        return $this->processRequest(function () use ($summit_id, $presentation_id, $link_id) {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $current_member = $this->resource_server_context->getCurrentUser();
            if (is_null($current_member)) return $this->error403();
            $isAdmin = $current_member->isAdmin() || $current_member->hasPermissionForOnGroup($summit, IGroup::SummitAdministrators);
            if (!$isAdmin) {
                // check if we could edit presentation
                $presentation = $summit->getEvent(intval($presentation_id));
                if (!$presentation instanceof Presentation)
                    return $this->error404();
                if (!$current_member->hasSpeaker() || !$presentation->canEdit($current_member->getSpeaker()))
                    return $this->error403();
            }

            $this->presentation_service->deleteLink(intval($presentation_id), intval($link_id));

            return $this->deleted();
        });
    }

    // MediaUploads

    #[OA\Get(
        path: "/api/v1/summits/{id}/presentations/{presentation_id}/media-uploads",
        summary: "Get all media uploads from a presentation",
        operationId: "getPresentationMediaUploads",
        tags: ['Presentations'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'presentation_id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "OK",
                content: new OA\JsonContent(
                    type: "array",
                    items: new OA\Items(ref: "#/components/schemas/PresentationMediaUpload")
                )
            ),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    public function getPresentationMediaUploads($summit_id, $presentation_id)
    {
        return $this->processRequest(function () use ($summit_id, $presentation_id) {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $presentation = $this->presentation_repository->getById(intval($presentation_id));

            if (!$presentation instanceof Presentation) return $this->error404();

            $mediaUploads = $presentation->getMediaUploads();

            $items = [];
            foreach ($mediaUploads as $i) {
                if ($i instanceof IEntity) {
                    $i = SerializerRegistry::getInstance()->getSerializer($i)->serialize(
                        SerializerUtils::getExpand(),
                        SerializerUtils::getFields(),
                        SerializerUtils::getRelations()
                    );
                }
                $items[] = $i;
            }

            return $this->ok($items);

        });
    }

    #[OA\Get(
        path: "/api/v1/summits/{id}/presentations/{presentation_id}/media-uploads/{media_upload_id}",
        summary: "Get a media upload from a presentation",
        operationId: "getPresentationMediaUpload",
        tags: ['Presentations'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'presentation_id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'media_upload_id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "OK",
                content: new OA\JsonContent(ref: "#/components/schemas/PresentationMediaUpload")
            ),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    public function getPresentationMediaUpload($summit_id, $presentation_id, $media_upload_id)
    {
        return $this->processRequest(function () use ($summit_id, $presentation_id, $media_upload_id) {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $presentation = $this->presentation_repository->getById(intval($presentation_id));

            if (!$presentation instanceof Presentation) return $this->error404();

            $mediaUpload = $presentation->getMediaUploadBy(intval($media_upload_id));

            if (is_null($mediaUpload)) return $this->error404();

            return $this->ok(SerializerRegistry::getInstance()->getSerializer($mediaUpload)->serialize(
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));

        });
    }

    #[OA\Post(
        path: "/api/v1/summits/{id}/presentations/{presentation_id}/media-uploads",
        summary: "Add a media upload to a presentation",
        operationId: "addPresentationMediaUpload",
        security: [['summit_oauth2' => [SummitScopes::WritePresentationData]]],
        tags: ['Presentations'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'presentation_id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: "multipart/form-data",
                schema: new OA\Schema(ref: "#/components/schemas/PresentationMediaUploadRequest")
            )
        ),
        responses: [
            new OA\Response(
                response: Response::HTTP_CREATED,
                description: "Created",
                content: new OA\JsonContent(ref: "#/components/schemas/PresentationMediaUpload")
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    public function addPresentationMediaUpload(LaravelRequest $request, $summit_id, $presentation_id)
    {
        return $this->processRequest(function () use ($request, $summit_id, $presentation_id) {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $current_member = $this->resource_server_context->getCurrentUser();
            if (is_null($current_member)) return $this->error403();
            $serializeType = SerializerRegistry::SerializerType_Private;

            $isAdmin = $current_member->isAdmin() || $current_member->hasPermissionForOnGroup($summit, IGroup::SummitAdministrators);
            if (!$isAdmin) {
                $serializeType = SerializerRegistry::SerializerType_Public;
                // check if we could edit presentation
                $presentation = $summit->getEvent(intval($presentation_id));
                if (!$presentation instanceof Presentation)
                    return $this->error404();
                if (!$current_member->hasSpeaker() || !$presentation->canEdit($current_member->getSpeaker()))
                    return $this->error403();
            }

            $data = $request->all();

            $rules = [
                'media_upload_type_id' => 'required|integer',
                'display_on_site' => 'sometimes|boolean',
            ];

            $data = MultipartFormDataCleaner::cleanBool('display_on_site', $data);
            // Creates a Validator instance and validates the data.
            $validation = Validator::make($data, $rules);

            if ($validation->fails()) {
                $ex = new ValidationException;
                $ex->setMessages($validation->messages()->toArray());
                throw $ex;
            }

            $mediaUpload = $this->presentation_service->addMediaUploadTo
            (
                $request,
                $summit,
                intval($presentation_id),
                $data
            );

            return $this->created(SerializerRegistry::getInstance()->getSerializer
            (
                $mediaUpload, $serializeType)
                ->serialize
                (
                    SerializerUtils::getExpand(),
                    SerializerUtils::getFields(),
                    SerializerUtils::getRelations()
                )
            );
        });
    }

    #[OA\Put(
        path: "/api/v1/summits/{id}/presentations/{presentation_id}/media-uploads/{media_upload_id}",
        summary: "Update a media upload from a presentation",
        operationId: "updatePresentationMediaUpload",
        security: [['summit_oauth2' => [SummitScopes::WritePresentationData]]],
        tags: ['Presentations'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'presentation_id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'media_upload_id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: "multipart/form-data",
                schema: new OA\Schema(ref: "#/components/schemas/PresentationMediaUploadRequest")
            )
        ),
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "OK",
                content: new OA\JsonContent(ref: "#/components/schemas/PresentationMediaUpload")
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    public function updatePresentationMediaUpload(LaravelRequest $request, $summit_id, $presentation_id, $media_upload_id)
    {
        return $this->processRequest(function () use ($request, $summit_id, $presentation_id, $media_upload_id) {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $current_member = $this->resource_server_context->getCurrentUser();
            if (is_null($current_member)) return $this->error403();
            $serializeType = SerializerRegistry::SerializerType_Private;
            $isAdmin = $current_member->isAdmin() || $current_member->hasPermissionForOnGroup($summit, IGroup::SummitAdministrators);
            if (!$isAdmin) {
                $serializeType = SerializerRegistry::SerializerType_Public;
                // check if we could edit presentation
                $presentation = $summit->getEvent(intval($presentation_id));
                if (!$presentation instanceof Presentation)
                    return $this->error404();
                if (!$current_member->hasSpeaker() || !$presentation->canEdit($current_member->getSpeaker()))
                    return $this->error403();
            }

            $data = $request->all();

            $rules = [
                'display_on_site' => 'sometimes|boolean',
            ];

            $data = MultipartFormDataCleaner::cleanBool('display_on_site', $data);
            // Creates a Validator instance and validates the data.
            $validation = Validator::make($data, $rules);

            if ($validation->fails()) {
                $ex = new ValidationException;
                $ex->setMessages($validation->messages()->toArray());
                throw $ex;
            }

            $mediaUpload = $this->presentation_service->updateMediaUploadFrom
            (
                $request,
                $summit,
                intval($presentation_id),
                intval($media_upload_id),
                $data,
                $current_member
            );

            return $this->updated(SerializerRegistry::getInstance()->getSerializer
            (
                $mediaUpload, $serializeType)
                ->serialize
                (
                    SerializerUtils::getExpand(),
                    SerializerUtils::getFields(),
                    SerializerUtils::getRelations()
                )
            );
        });
    }

    #[OA\Delete(
        path: "/api/v1/summits/{id}/presentations/{presentation_id}/media-uploads/{media_upload_id}",
        summary: "Delete a media upload from a presentation",
        operationId: "deletePresentationMediaUpload",
        security: [['summit_oauth2' => [SummitScopes::WritePresentationData]]],
        tags: ['Presentations'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'presentation_id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'media_upload_id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 204, description: "No Content"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    public function deletePresentationMediaUpload($summit_id, $presentation_id, $media_upload_id)
    {
        return $this->processRequest(function () use ($summit_id, $presentation_id, $media_upload_id) {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit))
                return $this->error404();

            $current_member = $this->resource_server_context->getCurrentUser();
            if (is_null($current_member)) return $this->error403();
            $isAdmin = $current_member->isAdmin() || $current_member->hasPermissionForOnGroup($summit, IGroup::SummitAdministrators);
            if (!$isAdmin) {
                // check if we could edit presentation
                $presentation = $summit->getEvent(intval($presentation_id));
                if (!$presentation instanceof Presentation)
                    return $this->error404();
                if (!$current_member->hasSpeaker() || !$presentation->canEdit($current_member->getSpeaker()))
                    return $this->error403();
            }

            $this->presentation_service->deleteMediaUpload($summit, intval($presentation_id), intval($media_upload_id), $current_member);

            return $this->deleted();

        });
    }

    #[OA\Post(
        path: "/api/v1/summits/{id}/presentations/all/import/mux",
        summary: "Import assets from MUX",
        operationId: "importAssetsFromMUX",
        security: [['summit_oauth2' => [SummitScopes::WritePresentationData]]],
        tags: ['Presentations'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: "#/components/schemas/MuxImportRequest")
        ),
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: "OK"),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: "Bad Request"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    public function importAssetsFromMUX($summit_id)
    {
        return $this->processRequest(function () use ($summit_id) {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            if (!Request::isJson()) return $this->error400();

            $current_member = $this->resource_server_context->getCurrentUser();
            if (is_null($current_member)) return $this->error403();

            $data = Request::json();
            $data = $data->all();
            // Creates a Validator instance and validates the data.
            $validation = Validator::make($data, [
                'mux_token_id' => 'required|string',
                'mux_token_secret' => 'required|string',
                'email_to' => 'sometimes|email',
            ]);

            if ($validation->fails()) {
                $ex = new ValidationException;
                $ex->setMessages($validation->messages()->toArray());
                throw $ex;
            }

            VideoStreamUrlMUXProcessingForSummitJob::dispatch(
                $summit_id,
                $data['mux_token_id'],
                $data['mux_token_secret'],
                $data['email_to'] ?? null
            )->delay(now()->addMinutes());

            return $this->ok();

        });

    }

    /**
     * Attendees Votes
     */

    #[OA\Get(
        path: "/api/v1/summits/{id}/presentations/{presentation_id}/votes",
        summary: "Get attendee votes for a presentation",
        operationId: "getAttendeeVotes",
        tags: ['Presentations'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'presentation_id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: "OK"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    public function getAttendeeVotes($summit_id, $presentation_id)
    {
        return $this->processRequest(function () use ($summit_id, $presentation_id) {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            return $this->ok();
        });
    }

    #[OA\Post(
        path: "/api/v1/summits/{id}/presentations/{presentation_id}/vote",
        summary: "Cast an attendee vote for a presentation",
        operationId: "castAttendeeVote",
        security: [['summit_oauth2' => [SummitScopes::WritePresentationData]]],
        tags: ['Presentations'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'presentation_id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_CREATED,
                description: "Created",
                content: new OA\JsonContent(ref: "#/components/schemas/PresentationVote")
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    public function castAttendeeVote($summit_id, $presentation_id)
    {

        return $this->processRequest(function () use ($summit_id, $presentation_id) {
            Log::debug(sprintf("OAuth2PresentationApiController::castAttendeeVote summit %s presentation %s", $summit_id, $presentation_id));
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $current_member = $this->resource_server_context->getCurrentUser();
            if (is_null($current_member)) return $this->error403();

            $vote = $this->presentation_service->castAttendeeVote($summit, $current_member, intval($presentation_id));

            return $this->created(SerializerRegistry::getInstance()->getSerializer
            ($vote)
                ->serialize
                (
                    SerializerUtils::getExpand(),
                    SerializerUtils::getFields(),
                    SerializerUtils::getRelations()
                )
            );

        });
    }

    #[OA\Delete(
        path: "/api/v1/summits/{id}/presentations/{presentation_id}/vote",
        summary: "Remove an attendee vote for a presentation",
        operationId: "unCastAttendeeVote",
        security: [['summit_oauth2' => [SummitScopes::WritePresentationData]]],
        tags: ['Presentations'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'presentation_id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 204, description: "No Content"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    public function unCastAttendeeVote($summit_id, $presentation_id)
    {
        return $this->processRequest(function () use ($summit_id, $presentation_id) {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $current_member = $this->resource_server_context->getCurrentUser();
            if (is_null($current_member)) return $this->error403();

            $this->presentation_service->unCastAttendeeVote($summit, $current_member, intval($presentation_id));

            return $this->deleted();
        });
    }

    #[OA\Post(
        path: "/api/v1/summits/{id}/selection-plans/{selection_plan_id}/presentations/{presentation_id}/scores/{score_type_id}",
        summary: "Add a track chair score to a presentation",
        operationId: "addTrackChairScore",
        tags: ['Presentations'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'selection_plan_id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'presentation_id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'score_type_id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_CREATED,
                description: "Created",
                content: new OA\JsonContent(ref: "#/components/schemas/PresentationTrackChairScore")
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    public function addTrackChairScore($summit_id, $selection_plan_id, $presentation_id, $score_type_id)
    {

        return $this->processRequest(function () use ($summit_id, $selection_plan_id, $presentation_id, $score_type_id) {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit))
                return $this->error404();

            $current_member = $this->resource_server_context->getCurrentUser();
            if (is_null($current_member))
                return $this->error403();

            $score = $this->presentation_service->addTrackChairScore($summit, $current_member, intval($selection_plan_id), intval($presentation_id), intval($score_type_id));

            return $this->created(
                SerializerRegistry::getInstance()->getSerializer
                ($score)
                    ->serialize
                    (
                        SerializerUtils::getExpand(),
                        SerializerUtils::getFields(),
                        SerializerUtils::getRelations()
                    )
            );
        });
    }

    #[OA\Delete(
        path: "/api/v1/summits/{id}/selection-plans/{selection_plan_id}/presentations/{presentation_id}/scores/{score_type_id}",
        summary: "Remove a track chair score from a presentation",
        operationId: "removeTrackChairScore",
        security: [['summit_oauth2' => [SummitScopes::WriteTrackChairData]]],
        tags: ['Presentations'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'selection_plan_id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'presentation_id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'score_type_id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 204, description: "No Content"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    public function removeTrackChairScore($summit_id, $selection_plan_id, $presentation_id, $score_type_id)
    {

        return $this->processRequest(function () use ($summit_id, $selection_plan_id, $presentation_id, $score_type_id) {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit))
                return $this->error404();

            $current_member = $this->resource_server_context->getCurrentUser();
            if (is_null($current_member))
                return $this->error403();

            $this->presentation_service->removeTrackChairScore($summit, $current_member, intval($selection_plan_id), intval($presentation_id), intval($score_type_id));

            return $this->deleted();
        });
    }

    #[OA\Post(
        path: "/api/v1/summits/{id}/presentations/{presentation_id}/speakers/{speaker_id}",
        summary: "Add a speaker to a presentation",
        operationId: "addSpeaker2Presentation",
        security: [['summit_oauth2' => [SummitScopes::WritePresentationData]]],
        tags: ['Presentations'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'presentation_id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'speaker_id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: false,
            content: new OA\JsonContent(ref: "#/components/schemas/PresentationSpeakerRequest")
        ),
        responses: [
            new OA\Response(
                response: Response::HTTP_CREATED,
                description: "Created",
                content: new OA\JsonContent(ref: "#/components/schemas/Presentation")
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    public function addSpeaker2Presentation($summit_id, $presentation_id, $speaker_id)
    {

        return $this->processRequest(function () use ($summit_id, $presentation_id, $speaker_id) {

            $summit = SummitFinderStrategyFactory::build(
                $this->summit_repository, $this->resource_server_context)->find(intval($summit_id));
            if (is_null($summit)) return $this->error404();

            $current_member = $this->resource_server_context->getCurrentUser();
            if (is_null($current_member)) return $this->error403();

            $payload = $this->getJsonPayload(['order' => 'sometimes|integer|min:1']);

            $presentation = $this->presentation_service->upsertPresentationSpeaker(
                $summit, intval($presentation_id), intval($speaker_id), $payload);

            return $this->created(SerializerRegistry::getInstance()->getSerializer($presentation)->serialize
            (
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));
        });
    }

    #[OA\Put(
        path: "/api/v1/summits/{id}/presentations/{presentation_id}/speakers/{speaker_id}",
        summary: "Update a speaker in a presentation",
        operationId: "updateSpeakerInPresentation",
        security: [['summit_oauth2' => [SummitScopes::WritePresentationData]]],
        tags: ['Presentations'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'presentation_id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'speaker_id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: "#/components/schemas/PresentationSpeakerRequest")
        ),
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "OK",
                content: new OA\JsonContent(ref: "#/components/schemas/Presentation")
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    public function updateSpeakerInPresentation($summit_id, $presentation_id, $speaker_id)
    {

        return $this->processRequest(function () use ($summit_id, $presentation_id, $speaker_id) {

            $summit = SummitFinderStrategyFactory::build(
                $this->summit_repository, $this->resource_server_context)->find(intval($summit_id));
            if (is_null($summit)) return $this->error404();

            $current_member = $this->resource_server_context->getCurrentUser();
            if (is_null($current_member)) return $this->error403();

            $payload = $this->getJsonPayload(['order' => 'required|integer|min:1']);

            $presentation = $this->presentation_service->upsertPresentationSpeaker
            (
                $summit,
                intval($presentation_id), intval($speaker_id), $payload
            );

            return $this->updated(SerializerRegistry::getInstance()->getSerializer($presentation)->serialize
            (
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));
        });
    }

    #[OA\Delete(
        path: "/api/v1/summits/{id}/presentations/{presentation_id}/speakers/{speaker_id}",
        summary: "Remove a speaker from a presentation",
        operationId: "removeSpeakerFromPresentation",
        security: [['summit_oauth2' => [SummitScopes::WritePresentationData]]],
        tags: ['Presentations'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'presentation_id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'speaker_id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 204, description: "No Content"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    public function removeSpeakerFromPresentation($summit_id, $presentation_id, $speaker_id)
    {

        return $this->processRequest(function () use ($summit_id, $presentation_id, $speaker_id) {

            $summit = SummitFinderStrategyFactory::build(
                $this->summit_repository, $this->resource_server_context)->find(intval($summit_id));
            if (is_null($summit)) return $this->error404();

            $current_member = $this->resource_server_context->getCurrentUser();
            if (is_null($current_member)) return $this->error403();

            $this->presentation_service->removeSpeakerFromPresentation(
                $summit, intval($presentation_id), intval($speaker_id));

            return $this->deleted();
        });
    }

    use ParametrizedGetAll;

    #[OA\Get(
        path: "/api/v1/summits/{id}/presentations/{presentation_id}/comments",
        summary: "Get all comments from a presentation",
        operationId: "getPresentationComments",
        tags: ['Presentations'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'presentation_id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'filter', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'order', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "OK",
                content: new OA\JsonContent(ref: "#/components/schemas/PaginatedPresentationComments")
            ),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    public function getComments($summit_id, $presentation_id)
    {

        $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->getResourceServerContext())->find($summit_id);
        if (is_null($summit)) return $this->error404();

        return $this->_getAll(
            function () {
                return [
                    'is_activity' => ['=='],
                    'is_public' => ['=='],
                    'creator_id' => ['=='],
                    'body' => ['==', '@@', '=@'],
                ];
            },
            function () {
                return [
                    'is_activity' => 'sometimes|boolean',
                    'is_public' => 'sometimes|boolean',
                    'creator_id' => 'sometimes|integer',
                    'body' => 'sometimes|string',
                ];
            },
            function () {
                return [
                    'id',
                    'creator_id'
                ];
            },
            function ($filter) use ($summit, $presentation_id) {
                if ($filter instanceof Filter) {
                    $filter->addFilterCondition(FilterElement::makeEqual('presentation_id', intval($presentation_id)));
                }
                return $filter;
            },
            function () {
                return SerializerRegistry::SerializerType_Public;
            },
            null,
            null,
            function ($page, $per_page, $filter, $order, $applyExtraFilters) {
                return $this->presentation_comments_repository->getAllByPage
                (
                    new PagingInfo($page, $per_page),
                    call_user_func($applyExtraFilters, $filter),
                    $order
                );
            }
        );
    }

    #[OA\Get(
        path: "/api/v1/summits/{id}/presentations/{presentation_id}/comments/{comment_id}",
        summary: "Get a comment from a presentation",
        operationId: "getPresentationComment",
        tags: ['Presentations'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'presentation_id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'comment_id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "OK",
                content: new OA\JsonContent(ref: "#/components/schemas/SummitPresentationComment")
            ),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    public function getComment($summit_id, $presentation_id, $comment_id)
    {
        return $this->processRequest(function () use ($summit_id, $presentation_id, $comment_id) {
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->getResourceServerContext())->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $presentation = $summit->getEvent(intval($presentation_id));
            if (!$presentation instanceof Presentation)
                return $this->error404();
            $comment = $presentation->getComment(intval($comment_id));
            if (!$comment instanceof SummitPresentationComment)
                return $this->error404();

            return $this->ok(SerializerRegistry::getInstance()->getSerializer($comment)->serialize
            (
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));
        });
    }

    #[OA\Delete(
        path: "/api/v1/summits/{id}/presentations/{presentation_id}/comments/{comment_id}",
        summary: "Delete a comment from a presentation",
        operationId: "deletePresentationComment",
        security: [['summit_oauth2' => [SummitScopes::WritePresentationData]]],
        tags: ['Presentations'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'presentation_id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'comment_id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 204, description: "No Content"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    public function deleteComment($summit_id, $presentation_id, $comment_id)
    {
        return $this->processRequest(function () use ($summit_id, $presentation_id, $comment_id) {
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->getResourceServerContext())->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $this->presentation_service->deletePresentationComment($summit, intval($presentation_id), intval($comment_id));

            return $this->deleted();
        });
    }


    #[OA\Post(
        path: "/api/v1/summits/{id}/presentations/{presentation_id}/comments",
        summary: "Add a comment to a presentation",
        operationId: "addPresentationComment",
        security: [['summit_oauth2' => [SummitScopes::WritePresentationData]]],
        tags: ['Presentations'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'presentation_id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: "#/components/schemas/PresentationCommentRequest")
        ),
        responses: [
            new OA\Response(
                response: Response::HTTP_CREATED,
                description: "Created",
                content: new OA\JsonContent(ref: "#/components/schemas/SummitPresentationComment")
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    public function addComment($summit_id, $presentation_id)
    {
        return $this->processRequest(function () use ($summit_id, $presentation_id) {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->getResourceServerContext())->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $payload = $this->getJsonPayload(SummitPresentationCommentValidationRulesFactory::buildForAdd(), true);
            $current_user = $this->resource_server_context->getCurrentUser(false, false);
            $comment = $this->presentation_service->createPresentationComment($summit, intval($presentation_id), $current_user, $payload);

            return $this->created(SerializerRegistry::getInstance()->getSerializer($comment)->serialize
            (
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));
        });
    }

    #[OA\Put(
        path: "/api/v1/summits/{id}/presentations/{presentation_id}/comments/{comment_id}",
        summary: "Update a comment from a presentation",
        operationId: "updatePresentationComment",
        security: [['summit_oauth2' => [SummitScopes::WritePresentationData]]],
        tags: ['Presentations'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'presentation_id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'comment_id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: "#/components/schemas/PresentationCommentRequest")
        ),
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "OK",
                content: new OA\JsonContent(ref: "#/components/schemas/SummitPresentationComment")
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    public function updateComment($summit_id, $presentation_id, $comment_id)
    {
        return $this->processRequest(function () use ($summit_id, $presentation_id, $comment_id) {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->getResourceServerContext())->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $payload = $this->getJsonPayload(SummitPresentationCommentValidationRulesFactory::buildForUpdate(), true);

            $comment = $this->presentation_service->updatePresentationComment($summit, intval($presentation_id), intval($comment_id), $payload);

            return $this->updated(SerializerRegistry::getInstance()->getSerializer($comment)->serialize
            (
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));
        });
    }

    #[OA\Get(
        path: "/api/v1/summits/{id}/presentations/{presentation_id}/extra-questions",
        summary: "Get extra question answers from a presentation",
        operationId: "getPresentationsExtraQuestions",
        tags: ['Presentations'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'presentation_id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'filter', in: 'query', required: false, schema: new OA\Schema(type: 'string'), description: 'Filter by selection_plan_id'),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "OK",
                content: new OA\JsonContent(ref: "#/components/schemas/PaginatedExtraQuestionAnswers")
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    public function getPresentationsExtraQuestions($summit_id, $presentation_id)
    {
        return $this->processRequest(function () use ($summit_id, $presentation_id) {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->getResourceServerContext())->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $presentation = $summit->getEvent(intval($presentation_id));

            if (!$presentation instanceof Presentation) return $this->error404();

            $filter = null;

            if (Request::has('filter')) {
                $filter = FilterParser::parse(Request::get('filter'), [
                    'selection_plan_id' => ['=='],
                ]);
            }

            if (is_null($filter)) $filter = new Filter();

            $filter->validate([
                'selection_plan_id' => 'sometimes|integer'
            ]);

            $selection_plan_id = 'all';
            if ($filter->hasFilter('selection_plan_id')) {
                $element = $filter->getUniqueFilter('selection_plan_id');
                $selection_plan_id = intval($element->getRawValue());
            }

            $selection_plan = $selection_plan_id === 'all' ? null : $summit->getSelectionPlanById(intval($selection_plan_id));
            $res = [];

            foreach ($presentation->getAllExtraQuestionAnswers() as $answer) {
                if ($selection_plan instanceof SelectionPlan) {
                    if ($selection_plan->isExtraQuestionAssigned($answer->getQuestion())) {
                        $res[] = $answer;
                    }
                    continue;
                }
                $res[] = $answer;
            }

            $response = new PagingResponse
            (
                count($res),
                count($res),
                1,
                1,
                $res
            );

            return $this->ok($response->toArray(
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));
        });
    }
}
