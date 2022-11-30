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

use App\Http\Utils\FileTypes;
use App\Http\Utils\MultipartFormDataCleaner;
use App\Jobs\VideoStreamUrlMUXProcessingForSummitJob;
use App\Models\Foundation\Main\IGroup;
use App\ModelSerializers\SerializerUtils;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request as LaravelRequest;
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
use models\utils\IEntity;
use ModelSerializers\SerializerRegistry;
use services\model\IPresentationService;

/**
 * Class OAuth2PresentationApiController
 * @package App\Http\Controllers
 */
final class OAuth2PresentationApiController extends OAuth2ProtectedController
{
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
     * OAuth2PresentationApiController constructor.
     * @param IPresentationService $presentation_service
     * @param ISummitRepository $summit_repository
     * @param ISummitEventRepository $presentation_repository
     * @param IMemberRepository $member_repository
     * @param IResourceServerContext $resource_server_context
     */
    public function __construct
    (
        IPresentationService   $presentation_service,
        ISummitRepository      $summit_repository,
        ISummitEventRepository $presentation_repository,
        IMemberRepository      $member_repository,
        IResourceServerContext $resource_server_context
    )
    {
        parent::__construct($resource_server_context);
        $this->presentation_repository = $presentation_repository;
        $this->presentation_service = $presentation_service;
        $this->member_repository = $member_repository;
        $this->summit_repository = $summit_repository;
    }

    //presentations

    //videos

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

    /**
     * @param $summit_id
     * @param $presentation_id
     * @param $video_id
     * @return JsonResponse|mixed
     */
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

    /**
     * @param LaravelRequest $request
     * @param $summit_id
     * @param $presentation_id
     * @return JsonResponse|mixed
     */
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

    /**
     * @param LaravelRequest $request
     * @param $summit_id
     * @param $presentation_id
     * @param $video_id
     * @return JsonResponse|mixed
     */
    public function updateVideo(LaravelRequest $request, $summit_id, $presentation_id, $video_id)
    {
        return $this->processRequest(function () use ($request, $summit_id, $presentation_id, $video_id) {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $payload = $this->getJsonPayload(PresentationVideoValidationRulesFactory::build($this->getJsonData(), true), true);

            $video = $this->presentation_service->updateVideo(intval($presentation_id), intval($video_id), HTMLCleaner::cleanData($payload,[
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

    /**
     * @param $summit_id
     * @param $presentation_id
     * @param $video_id
     * @return JsonResponse|mixed
     */
    public function deleteVideo($summit_id, $presentation_id, $video_id)
    {
        return $this->processRequest(function () use ($summit_id, $presentation_id, $video_id) {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $this->presentation_service->deleteVideo(intval($presentation_id), intval($video_id));

            return $this->deleted();

        });
    }

    /**
     * @param $summit_id
     * @return mixed
     */
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

    /**
     * @param $summit_id
     * @param $presentation_id
     * @return mixed
     */
    public function updatePresentationSubmission($summit_id, $presentation_id)
    {
        return $this->processRequest(function () use ($summit_id, $presentation_id) {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $current_member = $this->resource_server_context->getCurrentUser();
            if (is_null($current_member)) return $this->error403();

            $payload = $this->getJsonPayload(SummitEventValidationRulesFactory::buildForSubmission($this->getJsonData(), true), true);

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

    /**
     * @param $summit_id
     * @param $presentation_id
     * @return mixed
     */
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

    /**
     * @param $summit_id
     * @param $presentation_id
     * @return mixed
     */
    public function deletePresentation($summit_id, $presentation_id)
    {
        return $this->processRequest(function () use ($summit_id, $presentation_id) {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $current_member = $this->resource_server_context->getCurrentUser();
            if (is_null($current_member)) return $this->error403();

            $this->presentation_service->deletePresentation($summit, intval($presentation_id));

            return $this->deleted();

        });
    }

    // Slides

    /**
     * @param $summit_id
     * @param $presentation_id
     * @return JsonResponse|mixed
     */
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

    /**
     * @param $summit_id
     * @param $presentation_id
     * @param $slide_id
     * @return JsonResponse|mixed
     */
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

    /**
     * @param LaravelRequest $request
     * @param $summit_id
     * @param $presentation_id
     * @return JsonResponse|mixed
     */
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

    /**
     * @param LaravelRequest $request
     * @param $summit_id
     * @param $presentation_id
     * @param $slide_id
     * @return JsonResponse|mixed
     */
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

    /**
     * @param $summit_id
     * @param $presentation_id
     * @param $slide_id
     * @return JsonResponse|mixed
     */
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

    /**
     * @param $summit_id
     * @param $presentation_id
     * @return JsonResponse|mixed
     */
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

    /**
     * @param $summit_id
     * @param $presentation_id
     * @param $link_id
     * @return JsonResponse|mixed
     */
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

    /**
     * @param LaravelRequest $request
     * @param $summit_id
     * @param $presentation_id
     * @return JsonResponse|mixed
     */
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

    /**
     * @param LaravelRequest $request
     * @param $summit_id
     * @param $presentation_id
     * @param $link_id
     * @return JsonResponse|mixed
     */
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

    /**
     * @param $summit_id
     * @param $presentation_id
     * @param $link_id
     * @return JsonResponse|mixed
     */
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

    /**
     * @param $summit_id
     * @param $presentation_id
     * @return JsonResponse|mixed
     */
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

    /**
     * @param $summit_id
     * @param $presentation_id
     * @param $media_upload_id
     * @return JsonResponse|mixed
     */
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

    /**
     * @param LaravelRequest $request
     * @param $summit_id
     * @param $presentation_id
     * @return JsonResponse|mixed
     */
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

    /**
     * @param LaravelRequest $request
     * @param $summit_id
     * @param $presentation_id
     * @param $media_upload_id
     * @return JsonResponse|mixed
     */
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
                $data
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

    /**
     * @param $summit_id
     * @param $presentation_id
     * @param $media_upload_id
     * @return JsonResponse|mixed
     */
    public function deletePresentationMediaUpload($summit_id, $presentation_id, $media_upload_id)
    {
        return $this->processRequest(function () use ($summit_id, $presentation_id, $media_upload_id) {

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

            $this->presentation_service->deleteMediaUpload($summit, intval($presentation_id), intval($media_upload_id));

            return $this->deleted();

        });
    }

    /**
     * @param $summit_id
     * @return JsonResponse|mixed
     */
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

    /**
     * @param $summit_id
     * @param $presentation_id
     * @return JsonResponse|mixed
     */
    public function getAttendeeVotes($summit_id, $presentation_id)
    {
        return $this->processRequest(function () use ($summit_id, $presentation_id) {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            return $this->ok();
        });
    }

    /**
     * @param $summit_id
     * @param $presentation_id
     * @return JsonResponse|mixed
     */
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

    /**
     * @param $summit_id
     * @param $presentation_id
     * @return JsonResponse|mixed
     */
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

    /**
     * @param $summit_id
     * @param $selection_plan_id
     * @param $presentation_id
     * @param $score_type_id
     * @return JsonResponse|mixed
     */
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

    /**
     * @param $summit_id
     * @param $selection_plan_id
     * @param $presentation_id
     * @param $score_type_id
     * @return JsonResponse|mixed
     */
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
}