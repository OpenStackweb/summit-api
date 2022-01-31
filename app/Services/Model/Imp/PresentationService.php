<?php namespace services\model;
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

use App\Events\PresentationMaterialDeleted;
use App\Events\PresentationMaterialUpdated;
use App\Facades\ResourceServerContext;
use App\Http\Utils\FileSizeUtil;
use App\Http\Utils\FileUploadInfo;
use App\Http\Utils\IFileUploader;
use App\Jobs\Emails\PresentationSubmissions\PresentationCreatorNotificationEmail;
use App\Jobs\Emails\PresentationSubmissions\PresentationSpeakerNotificationEmail;
use App\Models\Foundation\Summit\Factories\PresentationFactory;
use App\Models\Foundation\Summit\Factories\PresentationLinkFactory;
use App\Models\Foundation\Summit\Factories\PresentationMediaUploadFactory;
use App\Models\Foundation\Summit\Factories\PresentationSlideFactory;
use App\Models\Foundation\Summit\Factories\PresentationVideoFactory;
use App\Models\Foundation\Summit\SelectionPlan;
use App\Models\Utils\IStorageTypesConstants;
use App\Services\Filesystem\FileUploadStrategyFactory;
use App\Services\Model\AbstractService;
use App\Services\Model\IFolderService;
use Illuminate\Http\Request as LaravelRequest;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use libs\utils\ITransactionService;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use models\main\IFolderRepository;
use models\main\ITagRepository;
use models\summit\ISpeakerRepository;
use models\summit\ISummitEventRepository;
use models\summit\Presentation;
use models\summit\PresentationLink;
use models\summit\PresentationMediaUpload;
use models\summit\PresentationSlide;
use models\summit\PresentationSpeaker;
use models\summit\PresentationType;
use models\summit\PresentationVideo;
use models\summit\Summit;

/**
 * Class PresentationService
 * @package services\model
 */
final class PresentationService
    extends AbstractService
    implements IPresentationService
{
    /**
     * @var ISummitEventRepository
     */
    private $presentation_repository;

    /**
     * @var ISpeakerRepository
     */
    private $speaker_repository;

    /**
     * @var ITagRepository
     */
    private $tag_repository;

    /**
     * @var IFolderService
     */
    private $folder_service;

    /**
     * @var IFileUploader
     */
    private $file_uploader;

    /**
     * @var IFolderRepository
     */
    private $folder_repository;

    /**
     * PresentationService constructor.
     * @param ISummitEventRepository $presentation_repository
     * @param ISpeakerRepository $speaker_repository
     * @param ITagRepository $tag_repository
     * @param IFolderService $folder_service
     * @param IFileUploader $file_uploader
     * @param IFolderRepository $folder_repository
     * @param ITransactionService $tx_service
     */
    public function __construct
    (
        ISummitEventRepository $presentation_repository,
        ISpeakerRepository $speaker_repository,
        ITagRepository $tag_repository,
        IFolderService $folder_service,
        IFileUploader $file_uploader,
        IFolderRepository $folder_repository,
        ITransactionService $tx_service
    )
    {
        parent::__construct($tx_service);
        $this->presentation_repository = $presentation_repository;
        $this->speaker_repository = $speaker_repository;
        $this->tag_repository = $tag_repository;
        $this->folder_service = $folder_service;
        $this->file_uploader = $file_uploader;
        $this->folder_repository = $folder_repository;
    }

    /**
     * @param int $presentation_id
     * @param array $video_data
     * @return PresentationVideo
     */
    public function addVideoTo($presentation_id, array $video_data)
    {
        $video = $this->tx_service->transaction(function () use ($presentation_id, $video_data) {

            $presentation = $this->presentation_repository->getById($presentation_id);

            if (is_null($presentation))
                throw new EntityNotFoundException('presentation not found!');

            if (!$presentation instanceof Presentation)
                throw new EntityNotFoundException('presentation not found!');

            if ($presentation->hasVideos())
                throw new ValidationException(sprintf('presentation %s already has a video!', $presentation_id));

            if (!isset($video_data['name'])) $video_data['name'] = $presentation->getTitle();

            $video = PresentationVideoFactory::build($video_data);

            $presentation->addVideo($video);

            return $video;
        });

        return $video;
    }

    /**
     * @param int $presentation_id
     * @param int $video_id
     * @param array $video_data
     * @return PresentationVideo
     */
    public function updateVideo($presentation_id, $video_id, array $video_data)
    {
        $video = $this->tx_service->transaction(function () use ($presentation_id, $video_id, $video_data) {

            $presentation = $this->presentation_repository->getById($presentation_id);

            if (is_null($presentation))
                throw new EntityNotFoundException('presentation not found!');

            if (!$presentation instanceof Presentation)
                throw new EntityNotFoundException('presentation not found!');

            $video = $presentation->getVideoBy($video_id);

            if (is_null($video))
                throw new EntityNotFoundException('video not found!');

            if (!$video instanceof PresentationVideo)
                throw new EntityNotFoundException('video not found!');

            PresentationVideoFactory::populate($video, $video_data);

            if (isset($data['order']) && intval($video_data['order']) != $video->getOrder()) {
                // request to update order
                $presentation->recalculateMaterialOrder($video, intval($video_data['order']));
            }

            return $video;

        });
        Event::dispatch(new PresentationMaterialUpdated($video));
        return $video;
    }

    /**
     * @param int $presentation_id
     * @param int $video_id
     * @return void
     */
    public function deleteVideo($presentation_id, $video_id)
    {
        $this->tx_service->transaction(function () use ($presentation_id, $video_id) {

            $presentation = $this->presentation_repository->getById($presentation_id);

            if (is_null($presentation))
                throw new EntityNotFoundException('presentation not found!');

            if (!$presentation instanceof Presentation)
                throw new EntityNotFoundException('presentation not found!');

            $video = $presentation->getVideoBy($video_id);

            if (is_null($video))
                throw new EntityNotFoundException('video not found!');

            if (!$video instanceof PresentationVideo)
                throw new EntityNotFoundException('video not found!');

            $presentation->removeVideo($video);

            Event::dispatch(new PresentationMaterialDeleted($presentation, $video_id, 'PresentationVideo'));
        });

    }

    /**
     * @param Summit $summit
     * @return int
     */
    public function getSubmissionLimitFor(Summit $summit)
    {
        $res = -1;
        if ($summit->isSubmissionOpen()) {
            $res = intval($summit->getCurrentSelectionPlanByStatus(SelectionPlan::STATUS_SUBMISSION)->getMaxSubmissionAllowedPerUser());
        }

        // zero means infinity
        return $res === 0 ? PHP_INT_MAX : $res;
    }

    /**
     * @param Summit $summit
     * @param array $data
     * @return Presentation
     * @throws ValidationException
     * @throws EntityNotFoundException
     * @throws \Exception
     */
    public function submitPresentation(Summit $summit, array $data)
    {
        return $this->tx_service->transaction(function () use ($summit, $data) {

            $member = ResourceServerContext::getCurrentUser(false);
            $current_selection_plan = $summit->getCurrentSelectionPlanByStatus(SelectionPlan::STATUS_SUBMISSION);

            if (is_null($current_selection_plan))
                throw new ValidationException(trans(
                    'validation_errors.PresentationService.submitPresentation.NotValidSelectionPlan'
                ));

            if (!$current_selection_plan->isAllowNewPresentations()) {
                throw new ValidationException(sprintf("Selection Plan %s does not allow new submissions", $current_selection_plan->getId()));
            }

            $current_speaker = $this->speaker_repository->getByMember($member);

            if (is_null($current_speaker))
                throw new ValidationException(trans(
                    'validation_errors.PresentationService.submitPresentation.NotValidSpeaker'
                ));

            if (!$current_selection_plan->IsEnabled()) {
                throw new ValidationException(sprintf("Submission Period is Closed."));
            }
            if (!$current_selection_plan->isSubmissionOpen()) {
                throw new ValidationException(sprintf("Submission Period is Closed."));
            }
            // check qty

            $limit = $this->getSubmissionLimitFor($summit);
            $presentations = [];

            foreach ($current_speaker->getPresentationsBySelectionPlanAndRole($current_selection_plan, PresentationSpeaker::ROLE_MODERATOR) as $p) {
                if (isset($presentations[$p->getId()])) continue;
                $presentations[$p->getId()] = $p->getId();
            }

            foreach ($current_speaker->getPresentationsBySelectionPlanAndRole($current_selection_plan, PresentationSpeaker::ROLE_SPEAKER) as $p) {
                if (isset($presentations[$p->getId()])) continue;
                $presentations[$p->getId()] = $p->getId();
            }

            $count = count($presentations);

            if ($count >= $limit)
                throw new ValidationException(trans(
                    'validation_errors.PresentationService.submitPresentation.limitReached',
                    ['limit' => $limit]));

            $presentation = new Presentation();
            $presentation->setSelectionPlan($current_selection_plan);

            $presentation->setCreatedBy(ResourceServerContext::getCurrentUser(false));
            $presentation->setUpdatedBy(ResourceServerContext::getCurrentUser(false));

            $summit->addEvent($presentation);

            if (!$presentation->isCompleted())
                $presentation->setProgress(Presentation::PHASE_SUMMARY);

            $presentation = $this->saveOrUpdatePresentation
            (
                $summit,
                $current_selection_plan,
                $presentation,
                $current_speaker,
                $data
            );

            return $presentation;
        });

    }

    /**
     * @param Summit $summit
     * @param int $presentation_id
     * @param array $data
     * @return Presentation
     * @throws ValidationException
     * @throws EntityNotFoundException
     */
    public function updatePresentationSubmission(Summit $summit, $presentation_id, array $data)
    {
        return $this->tx_service->transaction(function () use ($summit, $presentation_id, $data) {

            $member = ResourceServerContext::getCurrentUser(false);

            $current_selection_plan = $summit->getCurrentSelectionPlanByStatus(SelectionPlan::STATUS_SUBMISSION);
            $current_speaker = $this->speaker_repository->getByMember($member);

            if (is_null($current_speaker))
                throw new ValidationException(trans(
                    'validation_errors.PresentationService.updatePresentationSubmission.NotValidSpeaker'
                ));

            if (is_null($current_selection_plan))
                throw new ValidationException(trans(
                    'validation_errors.PresentationService.updatePresentationSubmission.NotValidSelectionPlan'
                ));

            if (!$current_selection_plan->IsEnabled()) {
                throw new ValidationException(sprintf("Submission Period is Closed."));
            }
            if (!$current_selection_plan->isSubmissionOpen()) {
                throw new ValidationException(sprintf("Submission Period is Closed."));
            }

            $presentation = $summit->getEvent($presentation_id);

            if (is_null($presentation))
                throw new EntityNotFoundException(trans(
                    'not_found_errors.PresentationService.updatePresentationSubmission.PresentationNotFound',
                    ['presentation_id' => $presentation_id]
                ));

            if (!$presentation instanceof Presentation)
                throw new EntityNotFoundException(trans(
                    'not_found_errors.PresentationService.updatePresentationSubmission.PresentationNotFound',
                    ['presentation_id' => $presentation_id]
                ));

            if (!$presentation->canEdit($current_speaker))
                throw new ValidationException(trans(
                    'validation_errors.PresentationService.updatePresentationSubmission.CurrentSpeakerCanNotEditPresentation',
                    ['presentation_id' => $presentation_id]
                ));

            $presentation->setUpdatedBy(ResourceServerContext::getCurrentUser(false));

            return $this->saveOrUpdatePresentation
            (
                $summit,
                $current_selection_plan,
                $presentation,
                $current_speaker,
                $data
            );
        });
    }

    /**
     * @param Summit $summit
     * @param SelectionPlan $selection_plan
     * @param Presentation $presentation
     * @param PresentationSpeaker $current_speaker
     * @param array $data
     * @return Presentation
     * @throws \Exception
     */
    private function saveOrUpdatePresentation(Summit $summit,
                                              SelectionPlan $selection_plan,
                                              Presentation $presentation,
                                              PresentationSpeaker $current_speaker,
                                              array $data
    )
    {
        return $this->tx_service->transaction(function () use ($summit, $selection_plan, $presentation, $current_speaker, $data) {

            $event_type = $summit->getEventType(intval($data['type_id']));
            if (is_null($event_type)) {
                throw new EntityNotFoundException(
                    trans(
                        'not_found_errors.PresentationService.saveOrUpdatePresentation.eventTypeNotFound',
                        ['type_id' => $data['type_id']]
                    )
                );
            }

            if (!$event_type instanceof PresentationType) {
                throw new ValidationException(trans(
                        'validation_errors.PresentationService.saveOrUpdatePresentation.invalidPresentationType',
                        ['type_id' => $event_type->getIdentifier()])
                );
            }

            if (!$event_type->isShouldBeAvailableOnCfp()) {
                throw new ValidationException(trans(
                    'validation_errors.PresentationService.saveOrUpdatePresentation.notAvailableCFP',
                    ['type_id' => $event_type->getIdentifier()]));
            }

            if ($presentation->getId() > 0 && $presentation->getTypeId() != $event_type->getId()) {
                // presentation is not new and we are trying to change the presentation type
                throw new ValidationException("you cant change the presentation type");
            }

            $track = $summit->getPresentationCategory(intval($data['track_id']));
            if (is_null($track)) {
                throw new EntityNotFoundException(
                    trans(
                        'not_found_errors.PresentationService.saveOrUpdatePresentation.trackNotFound',
                        ['track_id' => $data['track_id']]
                    )
                );
            }

            if (!$selection_plan->hasTrack($track)) {
                throw new ValidationException(trans(
                    'validation_errors.PresentationService.saveOrUpdatePresentation.trackDontBelongToSelectionPlan',
                    [
                        'selection_plan_id' => $selection_plan->getIdentifier(),
                        'track_id' => $track->getIdentifier(),
                    ]));
            }

            $presentation->setType($event_type);
            $presentation->setCategory($track);
            // tags
            if (isset($data['tags'])) {
                $presentation->clearTags();

                if (count($data['tags']) > 0) {
                    if (!$presentation->isCompleted())
                        $presentation->setProgress(Presentation::PHASE_TAGS);
                }

                foreach ($data['tags'] as $tag_value) {
                    $tag = $track->getAllowedTagByVal($tag_value);
                    if (is_null($tag)) {
                        throw new ValidationException(
                            trans(
                                'validation_errors.PresentationService.saveOrUpdatePresentation.TagNotAllowed',
                                [
                                    'tag' => $tag_value,
                                    'track_id' => $track->getId()
                                ]
                            )
                        );
                    }
                    $presentation->addTag($tag);
                }
            }

            return PresentationFactory::populate($presentation, $data);
        });
    }

    /**
     * @param Summit $summit
     * @param int $presentation_id
     * @return void
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function deletePresentation(Summit $summit, $presentation_id)
    {
        return $this->tx_service->transaction(function () use ($summit, $presentation_id) {

            $member = ResourceServerContext::getCurrentUser(false);

            $current_speaker = $this->speaker_repository->getByMember($member);
            if (is_null($current_speaker))
                throw new EntityNotFoundException(sprintf("member %s does not has a speaker profile", $member->getId()));

            $presentation = $summit->getEvent($presentation_id);
            if (is_null($presentation))
                throw new EntityNotFoundException(sprintf("presentation %s not found", $presentation_id));

            if (!$presentation instanceof Presentation)
                throw new EntityNotFoundException(sprintf("presentation %s not found", $presentation_id));

            if (!$presentation->canEdit($current_speaker))
                throw new ValidationException(sprintf("member %s can not edit presentation %s",
                    $member->getId(),
                    $presentation_id
                ));

            $presentation->clearMediaUploads();

            $summit->removeEvent($presentation);

        });
    }

    /**
     * @param Summit $summit
     * @param int $presentation_id
     * @return Presentation
     * @throws ValidationException
     * @throws EntityNotFoundException
     */
    public function completePresentationSubmission(Summit $summit, $presentation_id)
    {
        return $this->tx_service->transaction(function () use ($summit, $presentation_id) {

            $member = ResourceServerContext::getCurrentUser(false);

            $current_selection_plan = $summit->getCurrentSelectionPlanByStatus(SelectionPlan::STATUS_SUBMISSION);
            $current_speaker = $this->speaker_repository->getByMember($member);

            if (is_null($current_speaker))
                throw new EntityNotFoundException(sprintf("member %s does not has a speaker profile", $member->getId()));

            if (is_null($current_selection_plan))
                throw new ValidationException(trans(
                    'validation_errors.PresentationService.updatePresentationSubmission.NotValidSelectionPlan'
                ));

            if (!$current_selection_plan->IsEnabled()) {
                throw new ValidationException(sprintf("Submission Period is Closed."));
            }
            if (!$current_selection_plan->isSubmissionOpen()) {
                throw new ValidationException(sprintf("Submission Period is Closed."));
            }

            $presentation = $summit->getEvent($presentation_id);
            if (is_null($presentation))
                throw new EntityNotFoundException(sprintf("Presentation %s not found.", $presentation_id));

            if (!$presentation instanceof Presentation)
                throw new EntityNotFoundException(sprintf("Presentation %s not found.", $presentation_id));

            if ($presentation->isSubmitted()) {
                throw new ValidationException
                (
                    sprintf("Presentation %s is not allowed to mark as completed.", $presentation_id)
                );
            }

            if (!$presentation->canEdit($current_speaker))
                throw new ValidationException(sprintf("Member %s can not edit presentation %s.",
                    $member->getId(),
                    $presentation_id
                ));

            if (!$presentation->fulfilMediaUploadsConditions()) {
                throw new ValidationException
                (
                    sprintf("Presentation %s is not allowed to mark as completed because does not fulfil media uploads conditions.", $presentation_id)
                );
            }

            if (!$presentation->fulfilSpeakersConditions()) {
                throw new ValidationException
                (
                    sprintf("Presentation %s is not allowed to mark as completed because does not fulfil speakers conditions.", $presentation_id)
                );
            }

            $title = $presentation->getTitle();
            $abstract = $presentation->getAbstract();
            $level = $presentation->getLevel();

            if (empty($title)) {
                throw new ValidationException('Title is Mandatory.');
            }

            if (empty($abstract)) {
                throw new ValidationException('Abstract is mandatory.');
            }

            if ($presentation->getType()->isAllowsLevel() && empty($level)) {
                throw new ValidationException('Level is mandatory.');
            }

            $presentation->setProgress(Presentation::PHASE_COMPLETE);
            $presentation->setStatus(Presentation::STATUS_RECEIVED);

            PresentationCreatorNotificationEmail::dispatch($presentation);

            $presentation->setUpdatedBy($member);

            return $presentation;
        });
    }

    /**
     * @param LaravelRequest $request
     * @param int $presentation_id
     * @param array $slide_data
     * @param array $allowed_extensions
     * @param int $max_file_size
     * @return mixed|PresentationSlide
     * @throws \Exception
     */
    public function addSlideTo
    (
        LaravelRequest $request,
        $presentation_id,
        array $slide_data,
        array $allowed_extensions = [],
        $max_file_size = 10485760 // bytes
    )
    {
        $slide = $this->tx_service->transaction(function () use (
            $request,
            $presentation_id,
            $slide_data,
            $max_file_size,
            $allowed_extensions
        ) {

            $presentation = $this->presentation_repository->getById($presentation_id);

            if (is_null($presentation))
                throw new EntityNotFoundException('presentation not found!');

            if (!$presentation instanceof Presentation)
                throw new EntityNotFoundException('presentation not found!');

            $hasLink = isset($slide_data['link']) && !empty($slide_data['link']);
            $fileInfo = FileUploadInfo::build($request, $slide_data);
            $hasFile = !is_null($fileInfo);

            if ($hasFile && $hasLink) {
                throw new ValidationException("you must provide a file or a link, not both.");
            }

            $slide = PresentationSlideFactory::build($slide_data);
            // check if there is any file sent
            if ($hasFile) {

                if (!in_array($fileInfo->getFileExt(), $allowed_extensions)) {
                    throw new ValidationException(
                        sprintf("file does not has a valid extension '(%s)'.", implode("','", $allowed_extensions)));
                }

                if ($fileInfo->getSize(FileSizeUtil::B) > $max_file_size) {
                    throw new ValidationException(sprintf("file exceeds max_file_size (%s MB).", ($max_file_size / 1024) / 1024));
                }

                $slideFile = $this->file_uploader->build
                (
                    $fileInfo->getFile(),
                    sprintf('summits/%s/presentations/%s/slides', $presentation->getSummitId(), $presentation_id),
                    false
                );

                $slide->setSlide($slideFile);
            }

            $presentation->addSlide($slide);

            return $slide;
        });

        return $slide;
    }

    /**
     * @param LaravelRequest $request
     * @param int $presentation_id
     * @param int $slide_id
     * @param array $slide_data
     * @param array $allowed_extensions
     * @param int $max_file_size
     * @return mixed|PresentationSlide
     * @throws \Exception
     */
    public function updateSlide
    (
        LaravelRequest $request,
        $presentation_id,
        $slide_id,
        array $slide_data,
        array $allowed_extensions = [],
        $max_file_size = 10485760 // bytes
    )
    {

        $slide = $this->tx_service->transaction(function () use (
            $request,
            $presentation_id,
            $slide_data,
            $max_file_size,
            $allowed_extensions,
            $slide_id
        ) {

            $presentation = $this->presentation_repository->getById($presentation_id);

            if (is_null($presentation))
                throw new EntityNotFoundException('presentation not found!');

            if (!$presentation instanceof Presentation)
                throw new EntityNotFoundException('presentation not found!');

            $slide = $presentation->getSlideBy($slide_id);

            if (is_null($slide))
                throw new EntityNotFoundException('slide not found!');

            if (!$slide instanceof PresentationSlide)
                throw new EntityNotFoundException('slide not found!');


            $hasLink = isset($slide_data['link']) && !empty($slide_data['link']);
            $fileInfo = FileUploadInfo::build($request, $slide_data);
            $hasFile = !is_null($fileInfo);

            if ($hasFile && $hasLink) {
                throw new ValidationException("you must provide a file or a link, not both.");
            }

            if (!$hasLink && !$hasFile && !$slide->hasSlide()) {
                throw new ValidationException("you must provide a file or a link.");
            }

            PresentationSlideFactory::populate($slide, $slide_data);

            if ($hasLink && $slide->hasSlide()) {
                // drop file
                $file = $slide->getSlide();
                $this->folder_repository->delete($file);
                $slide->clearSlide();
            }

            // check if there is any file sent
            if ($hasFile) {
                if (!in_array($fileInfo->getFileExt(), $allowed_extensions)) {
                    throw new ValidationException(
                        sprintf("file does not has a valid extension '(%s)'.", implode("','", $allowed_extensions)));
                }

                if ($fileInfo->getSize(FileSizeUtil::B) > $max_file_size) {
                    throw new ValidationException(sprintf("file exceeds max_file_size (%s MB).", ($max_file_size / 1024) / 1024));
                }

                $slideFile = $this->file_uploader->build
                (
                    $fileInfo->getFile(),
                    sprintf('summits/%s/presentations/%s/slides', $presentation->getSummitId(), $presentation_id),
                    false
                );

                $slide->setSlide($slideFile);
                $slide->clearLink();
            }

            if (isset($data['order']) && intval($slide_data['order']) != $slide->getOrder()) {
                // request to update order
                $presentation->recalculateMaterialOrder($slide, intval($slide_data['order']));
            }

            return $slide;

        });

        Event::dispatch(new PresentationMaterialUpdated($slide));
        return $slide;
    }

    /**
     * @param int $presentation_id
     * @param int $slide_id
     * @return void
     * @throws EntityNotFoundException
     */
    public function deleteSlide($presentation_id, $slide_id)
    {
        $this->tx_service->transaction(function () use ($presentation_id, $slide_id) {

            $presentation = $this->presentation_repository->getById($presentation_id);

            if (is_null($presentation))
                throw new EntityNotFoundException('presentation not found!');

            if (!$presentation instanceof Presentation)
                throw new EntityNotFoundException('presentation not found!');

            $slide = $presentation->getSlideBy($slide_id);

            if (is_null($slide))
                throw new EntityNotFoundException('slide not found!');

            if (!$slide instanceof PresentationSlide)
                throw new EntityNotFoundException('slide not found!');

            $presentation->removeSlide($slide);

            Event::dispatch(new PresentationMaterialDeleted($presentation, $slide_id, 'PresentationSlide'));
        });
    }

    /**
     * @param $presentation_id
     * @param array $link_data
     * @return PresentationLink
     */
    public function addLinkTo($presentation_id, array $link_data)
    {
        $link = $this->tx_service->transaction(function () use (
            $presentation_id,
            $link_data
        ) {

            $presentation = $this->presentation_repository->getById($presentation_id);

            if (is_null($presentation))
                throw new EntityNotFoundException('presentation not found!');

            if (!$presentation instanceof Presentation)
                throw new EntityNotFoundException('presentation not found!');
            $link = PresentationLinkFactory::build($link_data);

            $presentation->addLink($link);

            return $link;
        });

        return $link;
    }

    /**
     * @param $presentation_id
     * @param $link_id
     * @param array $link_data
     * @return PresentationLink
     */
    public function updateLink($presentation_id, $link_id, array $link_data)
    {
        $link = $this->tx_service->transaction(function () use (
            $presentation_id,
            $link_id,
            $link_data
        ) {

            $presentation = $this->presentation_repository->getById($presentation_id);

            if (is_null($presentation))
                throw new EntityNotFoundException('presentation not found!');

            if (!$presentation instanceof Presentation)
                throw new EntityNotFoundException('presentation not found!');

            $link = $presentation->getLinkBy($link_id);

            if (is_null($link))
                throw new EntityNotFoundException('link not found!');

            if (!$link instanceof PresentationLink)
                throw new EntityNotFoundException('link not found!');

            $link = PresentationLinkFactory::populate($link, $link_data);


            return $link;
        });

        Event::dispatch(new PresentationMaterialUpdated($link));

        return $link;
    }

    /**
     * @param int $presentation_id
     * @param int $link_id
     * @return void
     */
    public function deleteLink($presentation_id, $link_id)
    {
        $this->tx_service->transaction(function () use ($presentation_id, $link_id) {

            $presentation = $this->presentation_repository->getById($presentation_id);

            if (is_null($presentation))
                throw new EntityNotFoundException('presentation not found!');

            if (!$presentation instanceof Presentation)
                throw new EntityNotFoundException('presentation not found!');

            $link = $presentation->getLinkBy($link_id);

            if (is_null($link))
                throw new EntityNotFoundException('link not found!');

            if (!$link instanceof PresentationLink)
                throw new EntityNotFoundException('link not found!');

            $presentation->removeLink($link);

            Event::dispatch(new PresentationMaterialDeleted($presentation, $link_id, 'PresentationLink'));
        });
    }

    // media uploads

    /**
     * @param LaravelRequest $request
     * @param Summit $summit
     * @param $presentation_id
     * @param array $payload
     * @return PresentationMediaUpload
     * @throws \Exception
     */
    public function addMediaUploadTo
    (
        LaravelRequest $request,
        Summit $summit,
        int $presentation_id,
        array $payload
    ): PresentationMediaUpload
    {
        return $this->tx_service->transaction(function () use (
            $request,
            $summit,
            $presentation_id,
            $payload
        ) {

            Log::debug(sprintf("PresentationService::addMediaUploadTo summit %s presentation %s", $summit->getId(), $presentation_id));

            $presentation = $this->presentation_repository->getById($presentation_id);

            if (is_null($presentation) || !$presentation instanceof Presentation)
                throw new EntityNotFoundException('Presentation not found.');

            $media_upload_type_id = intval($payload['media_upload_type_id']);

            $mediaUploadType = $summit->getMediaUploadTypeById($media_upload_type_id);

            if (is_null($mediaUploadType))
                throw new EntityNotFoundException(sprintf("Media Upload Type %s not found.", $media_upload_type_id));

            if (!$mediaUploadType->isPresentationTypeAllowed($presentation->getType())) {
                throw new ValidationException(sprintf("Presentation Type %s is not allowed on Media Upload %s", $presentation->getTypeId(), $media_upload_type_id));
            }

            if ($presentation->hasMediaUploadByType($mediaUploadType)) {
                throw new ValidationException
                (
                    sprintf
                    (
                        "Presentation %s already has a media upload for that type %s.",
                        $presentation_id, $mediaUploadType->getName()
                    )
                );
            }

            $fileInfo = FileUploadInfo::build($request, $payload);

            if (is_null($fileInfo)) {
                throw new ValidationException("You must provide a file.");
            }

            if ($mediaUploadType->getMaxSize() < $fileInfo->getSize()) {
                throw new ValidationException(sprintf("Max Size is %s MB (%s).", $mediaUploadType->getMaxSizeMB(), $fileInfo->getSize() / 1024));
            }

            if (!$mediaUploadType->isValidExtension($fileInfo->getFileExt())) {
                throw new ValidationException(sprintf("File Extension %s is not valid (%s).", $fileInfo->getFileExt(), $mediaUploadType->getValidExtensions()));
            }

            $mediaUpload = PresentationMediaUploadFactory::build(array_merge(
                $payload,
                [
                    'media_upload_type' => $mediaUploadType,
                    'presentation' => $presentation
                ]
            ));

            $strategy = FileUploadStrategyFactory::build($mediaUploadType->getPrivateStorageType());
            if (!is_null($strategy)) {
                $strategy->save($fileInfo->getFile(), $mediaUpload->getPath(IStorageTypesConstants::PrivateType), $fileInfo->getFileName());
            }

            $strategy = FileUploadStrategyFactory::build($mediaUploadType->getPublicStorageType());
            if (!is_null($strategy)) {
                $options = $mediaUploadType->isUseTemporaryLinksOnPublicStorage() ? []: 'public';
                $strategy->save
                (
                    $fileInfo->getFile(),
                    $mediaUpload->getPath(IStorageTypesConstants::PublicType),
                    $fileInfo->getFileName(),
                    $options
                );
            }

            $mediaUpload->setFilename($fileInfo->getFileName());
            $presentation->addMediaUpload($mediaUpload);

            if (!$presentation->isCompleted()) {
                Log::debug(sprintf("PresentationService::addMediaUploadTo presentation %s is not complete", $presentation_id));
                $summitMediaUploadCount = $summit->getMediaUploadsMandatoryCount();
                Log::debug(sprintf("PresentationService::addMediaUploadTo presentation %s got summitMediaUploadCount %s", $presentation_id, $summitMediaUploadCount));
                if ($summitMediaUploadCount == 0) {
                    Log::debug(sprintf("PresentationService::addMediaUploadTo presentation %s marking as PHASE_UPLOADS ( no mandatories uploads)", $presentation_id));
                    $presentation->setProgress(Presentation::PHASE_UPLOADS);
                }

                if ($summitMediaUploadCount > 0 && $summitMediaUploadCount == $presentation->getMediaUploadsMandatoryCount()) {
                    Log::debug(sprintf("PresentationService::addMediaUploadTo presentation %s marking as PHASE_UPLOADS ( mandatories completed)", $presentation_id));
                    $presentation->setProgress(Presentation::PHASE_UPLOADS);
                }
            }
            Log::debug(sprintf("PresentationService::addMediaUploadTo presentation %s  deleting original file %s", $presentation_id, $fileInfo->getFileName()));
            $fileInfo->delete();

            return $mediaUpload;
        });
    }

    /**
     * @param LaravelRequest $request
     * @param Summit $summit
     * @param int $presentation_id
     * @param int $media_upload_id
     * @param array $payload
     * @return PresentationMediaUpload
     * @throws \Exception
     */
    public function updateMediaUploadFrom
    (
        LaravelRequest $request,
        Summit $summit,
        int $presentation_id,
        int $media_upload_id,
        array $payload
    ): PresentationMediaUpload
    {
        return $this->tx_service->transaction(function () use (
            $request,
            $summit,
            $presentation_id,
            $media_upload_id,
            $payload
        ) {

            Log::debug(sprintf("PresentationService::updateMediaUploadFrom summit %s presentation %s", $summit->getId(), $presentation_id));

            $presentation = $this->presentation_repository->getById($presentation_id);

            if (is_null($presentation) || !$presentation instanceof Presentation)
                throw new EntityNotFoundException('Presentation not found.');

            $mediaUpload = $presentation->getMediaUploadBy($media_upload_id);

            if (is_null($mediaUpload))
                throw new EntityNotFoundException('Presentation Media Upload not found.');

            $fileInfo = FileUploadInfo::build($request, $payload);

            if (!is_null($fileInfo)) {
                // process file
                $mediaUploadType = $mediaUpload->getMediaUploadType();
                if (is_null($mediaUploadType))
                    throw new ValidationException("Media Upload Type is not set.");

                $fileInfo = FileUploadInfo::build($request, $payload);
                if (is_null($fileInfo)) {
                    throw new ValidationException("You must provide a file.");
                }

                if ($mediaUploadType->getMaxSize() < $fileInfo->getSize()) {
                    throw new ValidationException(sprintf("Max Size is %s MB (%s).", $mediaUploadType->getMaxSizeMB(), $fileInfo->getSize() / 1024));
                }

                if (!$mediaUploadType->isValidExtension($fileInfo->getFileExt())) {
                    throw new ValidationException(sprintf("File Extension %s is not valid (%s).", $fileInfo->getFileExt(), $mediaUploadType->getValidExtensions()));
                }

                $strategy = FileUploadStrategyFactory::build($mediaUploadType->getPrivateStorageType());

                if (!is_null($strategy)) {
                    $strategy->save($fileInfo->getFile(), $mediaUpload->getPath(IStorageTypesConstants::PrivateType), $fileInfo->getFileName());
                }

                $strategy = FileUploadStrategyFactory::build($mediaUploadType->getPublicStorageType());
                if (!is_null($strategy)) {
                    $options = $mediaUploadType->isUseTemporaryLinksOnPublicStorage() ? []: 'public';
                    $strategy->save
                    (
                        $fileInfo->getFile(),
                        $mediaUpload->getPath(IStorageTypesConstants::PublicType),
                        $fileInfo->getFileName(),
                        $options
                    );
                }

                $payload['file_name'] = $fileInfo->getFileName();

                $fileInfo->delete();
            }

            return PresentationMediaUploadFactory::populate($mediaUpload, $payload);
        });
    }


    /**
     * @inheritDoc
     */
    public function deleteMediaUpload(Summit $summit, int $presentation_id, int $media_upload_id): void
    {
        $this->tx_service->transaction(function () use (
            $summit,
            $presentation_id,
            $media_upload_id
        ) {
            Log::debug(sprintf("PresentationService::deleteMediaUpload summit %s presentation %s", $summit->getId(), $presentation_id));

            $presentation = $this->presentation_repository->getById($presentation_id);

            if (is_null($presentation) || !$presentation instanceof Presentation)
                throw new EntityNotFoundException('Presentation not found.');

            $mediaUpload = $presentation->getMediaUploadBy($media_upload_id);
            if (is_null($mediaUpload)) {
                throw new EntityNotFoundException("Media Upload not found.");
            }

            $mediaUploadType = $mediaUpload->getMediaUploadType();
            $strategy = FileUploadStrategyFactory::build($mediaUploadType->getPrivateStorageType());

            if (!is_null($strategy)) {
                $strategy->markAsDeleted($mediaUpload->getPath(IStorageTypesConstants::PrivateType), $mediaUpload->getFilename());
            }

            $strategy = FileUploadStrategyFactory::build($mediaUploadType->getPublicStorageType());

            if (!is_null($strategy)) {
                $strategy->markAsDeleted($mediaUpload->getPath(IStorageTypesConstants::PublicType), $mediaUpload->getFilename());
            }

            $presentation->removeMediaUpload($mediaUpload);
        });
    }
}