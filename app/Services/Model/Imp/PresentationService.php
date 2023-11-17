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

use App\Facades\ResourceServerContext;
use App\Http\Utils\FileSizeUtil;
use App\Http\Utils\FileUploadInfo;
use App\Http\Utils\IFileUploader;
use App\Jobs\Emails\PresentationSubmissions\PresentationCreatorNotificationEmail;
use App\Jobs\ProcessMediaUpload;
use App\Models\Exceptions\AuthzException;
use App\Models\Foundation\Summit\Events\Presentations\TrackChairs\PresentationTrackChairScore;
use App\Models\Foundation\Summit\Events\Presentations\TrackChairs\PresentationTrackChairScoreType;
use App\Models\Foundation\Summit\Factories\PresentationFactory;
use App\Models\Foundation\Summit\Factories\PresentationLinkFactory;
use App\Models\Foundation\Summit\Factories\PresentationMediaUploadFactory;
use App\Models\Foundation\Summit\Factories\PresentationSlideFactory;
use App\Models\Foundation\Summit\Factories\PresentationVideoFactory;
use App\Models\Foundation\Summit\Factories\SummitPresentationCommentFactory;
use App\Models\Foundation\Summit\Repositories\IPresentationTrackChairScoreTypeRepository;
use App\Models\Foundation\Summit\SelectionPlan;
use App\Models\Utils\IStorageTypesConstants;
use App\Services\Filesystem\FileUploadStrategyFactory;
use App\Services\Model\AbstractService;
use App\Services\Model\IFolderService;
use Illuminate\Http\Request as LaravelRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use libs\utils\FileUtils;
use libs\utils\ITransactionService;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use models\main\IFolderRepository;
use models\main\ITagRepository;
use models\main\Member;
use models\summit\ISpeakerRepository;
use models\summit\ISummitEventRepository;
use models\summit\ISummitRepository;
use models\summit\Presentation;
use models\summit\PresentationAttendeeVote;
use models\summit\PresentationLink;
use models\summit\PresentationMediaUpload;
use models\summit\PresentationSlide;
use models\summit\PresentationSpeaker;
use models\summit\PresentationType;
use models\summit\PresentationVideo;
use models\summit\Summit;
use models\summit\SummitPresentationComment;

/**
 * Class PresentationService
 * @package services\model
 */
final class PresentationService
    extends AbstractService
    implements IPresentationService
{
    const LocalChunkSize = 1024;
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
     * @var IPresentationTrackChairScoreTypeRepository
     */
    private $presentation_track_chair_score_type_repository;

    /**
     * @var ISummitRepository
     */
    private $summit_repository;

    /**
     * PresentationService constructor.
     * @param ISummitEventRepository $presentation_repository
     * @param ISpeakerRepository $speaker_repository
     * @param ITagRepository $tag_repository
     * @param IFolderService $folder_service
     * @param IFileUploader $file_uploader
     * @param IFolderRepository $folder_repository
     * @param IPresentationTrackChairScoreTypeRepository $presentation_track_chair_score_type_repository
     * @param ITransactionService $tx_service
     */
    public function __construct
    (
        ISummitEventRepository                     $presentation_repository,
        ISpeakerRepository                         $speaker_repository,
        ITagRepository                             $tag_repository,
        IFolderService                             $folder_service,
        IFileUploader                              $file_uploader,
        IFolderRepository                          $folder_repository,
        ISummitRepository                          $summit_repository,
        IPresentationTrackChairScoreTypeRepository $presentation_track_chair_score_type_repository,
        ITransactionService                        $tx_service
    )
    {
        parent::__construct($tx_service);
        $this->presentation_repository = $presentation_repository;
        $this->speaker_repository = $speaker_repository;
        $this->tag_repository = $tag_repository;
        $this->folder_service = $folder_service;
        $this->file_uploader = $file_uploader;
        $this->folder_repository = $folder_repository;
        $this->presentation_track_chair_score_type_repository = $presentation_track_chair_score_type_repository;
        $this->summit_repository = $summit_repository;
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

        });

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

            Log::debug(sprintf("PresentationService::submitPresentation summit %s payload %s", $summit->getId(), json_encode($data)));

            $member = ResourceServerContext::getCurrentUser(false);
            $selection_plan_id = $data['selection_plan_id'] ?? null;
            if (is_null($selection_plan_id))
                throw new ValidationException("selection_plan_id is required.");

            $current_selection_plan = $summit->getSelectionPlanById(intval($selection_plan_id));

            if (is_null($current_selection_plan))
                throw new ValidationException(trans(
                    'validation_errors.PresentationService.submitPresentation.NotValidSelectionPlan'
                ));

            if (!$current_selection_plan->IsEnabled()) {
                throw new ValidationException(sprintf("Submission Period is Closed."));
            }

            if (!$current_selection_plan->isSubmissionOpen()) {
                throw new ValidationException(sprintf("Submission Period is Closed."));
            }

            if (!$current_selection_plan->isAllowNewPresentations()) {
                throw new ValidationException(sprintf("Selection Plan %s does not allow new submissions", $current_selection_plan->getId()));
            }

            if (!$current_selection_plan->isAllowedMember($member->getEmail())) {
                throw new AuthzException(sprintf("Member is not Authorized on Selection Plan."));
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

            $limit = $current_selection_plan->getSubmissionLimitFor();

            Log::debug
            (
                sprintf
                (
                    "PresentationService::submitPresentation summit %s payload %s selection plan %s selection plan submission limit %s",
                    $summit->getId(),
                    json_encode($data),
                    $current_selection_plan->getId(),
                    $limit
                )
            );

            $presentations = [];

            foreach ($current_speaker->getPresentationsBySelectionPlanAndRole($current_selection_plan, PresentationSpeaker::ROLE_CREATOR) as $p) {
                if (isset($presentations[$p->getId()])) continue;
                $presentations[$p->getId()] = $p->getId();
            }

            foreach ($current_speaker->getPresentationsBySelectionPlanAndRole($current_selection_plan, PresentationSpeaker::ROLE_MODERATOR) as $p) {
                if (isset($presentations[$p->getId()])) continue;
                $presentations[$p->getId()] = $p->getId();
            }

            foreach ($current_speaker->getPresentationsBySelectionPlanAndRole($current_selection_plan, PresentationSpeaker::ROLE_SPEAKER) as $p) {
                if (isset($presentations[$p->getId()])) continue;
                $presentations[$p->getId()] = $p->getId();
            }

            $count = count($presentations);

            Log::debug
            (
                sprintf
                (
                    "PresentationService::submitPresentation summit %s speaker %s (%s) presentations count %s",
                    $summit->getId(),
                    $current_speaker->getEmail(),
                    $current_speaker->getId(),
                    $count
                )
            );

            if ($count >= $limit)
                throw new ValidationException(trans(
                    'validation_errors.PresentationService.submitPresentation.limitReached',
                    ['limit' => $limit]));

            $current_selection_plan->checkPresentationAllowedQuestions($data);

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
     * @param SelectionPlan $selection_plan
     * @param Presentation $presentation
     * @param PresentationSpeaker $current_speaker
     * @param array $data
     * @return Presentation
     * @throws \Exception
     */
    private function saveOrUpdatePresentation(Summit              $summit,
                                              SelectionPlan       $selection_plan,
                                              Presentation        $presentation,
                                              PresentationSpeaker $current_speaker,
                                              array               $data
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

            if (!$selection_plan->hasEventType($event_type)) {
                throw new ValidationException
                (
                    sprintf
                    (
                        "Event Type %s is not allowed on selection plan %s.",
                        $event_type->getType(),
                        $selection_plan->getName()
                    )
                );
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
     * @param array $data
     * @return Presentation
     * @throws ValidationException
     * @throws EntityNotFoundException
     */
    public function updatePresentationSubmission(Summit $summit, $presentation_id, array $data)
    {
        return $this->tx_service->transaction(function () use ($summit, $presentation_id, $data) {

            Log::debug(sprintf("PresentationService::updatePresentationSubmission presentation %s payload %s",  $presentation_id, json_encode($data)));

            $member = ResourceServerContext::getCurrentUser(false);
            $current_speaker = $this->speaker_repository->getByMember($member);

            if (is_null($current_speaker))
                throw new ValidationException(trans(
                    'validation_errors.PresentationService.updatePresentationSubmission.NotValidSpeaker'
                ));

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

            $current_selection_plan = $presentation->getSelectionPlan();

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

            if (!$current_selection_plan->isAllowedMember($member->getEmail())) {
                throw new AuthzException(sprintf("Member is not Authorized on Selection Plan."));
            }

            $current_selection_plan->checkPresentationAllowedQuestions($data);
            $current_selection_plan->checkPresentationAllowedEdtiableQuestions($data, $presentation->getSnapshot());

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

            $current_selection_plan = $presentation->getSelectionPlan();

            if (is_null($current_selection_plan))
                throw new ValidationException("Presentation is not assigned to any selection plan.");

            if (!$presentation->canEdit($current_speaker))
                throw new ValidationException(sprintf("member %s can not edit presentation %s",
                    $member->getId(),
                    $presentation_id
                ));

            if (!$current_selection_plan->isAllowedMember($member->getEmail())) {
                throw new AuthzException(sprintf("Member is not Authorized on Selection Plan."));
            }

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

            $current_speaker = $this->speaker_repository->getByMember($member);

            if (is_null($current_speaker))
                throw new EntityNotFoundException(sprintf("member %s does not has a speaker profile", $member->getId()));

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

            $current_selection_plan = $presentation->getSelectionPlan();

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

            if (!$current_selection_plan->isAllowedMember($member->getEmail())) {
                throw new AuthzException(sprintf("Member is not Authorized on Selection Plan."));
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

            if (empty($title)) {
                throw new ValidationException('Title is Mandatory.');
            }

            $presentation->setProgress(Presentation::PHASE_COMPLETE);
            $presentation->setStatus(Presentation::STATUS_RECEIVED);

            PresentationCreatorNotificationEmail::dispatch($presentation);

            $presentation->setUpdatedBy($member);

            return $presentation;
        });
    }

    use FileUtils;
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

                $file = $fileInfo->getFile();
                $localPath = null;
                if(is_null($file)){
                    Log::debug
                    (
                        sprintf
                        (
                            "PresentationService::addSlideTo file %s is not local, trying to retrieving from remote...",
                            $fileInfo->getFileName()
                        )
                    );
                    // is not local storage we need to retrieve it
                    $localPath = self::getFileFromRemoteStorageOnTempStorage($fileInfo->getFileName(), $fileInfo->getFilePath());
                    $file = new UploadedFile($localPath, $fileInfo->getFileName());
                }

                $slideFile = $this->file_uploader->build
                (
                    $file,
                    sprintf('summits/%s/presentations/%s/slides', $presentation->getSummitId(), $presentation_id),
                    false
                );

                $slide->setSlide($slideFile);

                if(!empty($localPath)){
                    self::cleanLocalAndRemoteFile($localPath, $fileInfo->getFilePath());
                }

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

        Log::debug
        (
            sprintf
            (
                "PresentationService::updateSlide presentation %s slide %s payload %s",
                $presentation_id,
                $slide_id,
                json_encode($slide_data)
            )
        );

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
                throw new EntityNotFoundException('Presentation not found.');

            if (!$presentation instanceof Presentation)
                throw new EntityNotFoundException('Presentation not found.');

            $slide = $presentation->getSlideBy($slide_id);

            if (is_null($slide))
                throw new EntityNotFoundException('Slide not found.');

            if (!$slide instanceof PresentationSlide)
                throw new EntityNotFoundException('slide not found!');


            $hasLink = isset($slide_data['link']) && !empty($slide_data['link']);
            $fileInfo = FileUploadInfo::build($request, $slide_data);
            $hasFile = !is_null($fileInfo);

            if ($hasFile && $hasLink) {
                throw new ValidationException("You must provide a file or a link, not both.");
            }

            if (!$hasLink && !$hasFile && !$slide->hasSlide()) {
                throw new ValidationException("You must provide a file or a link.");
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
                        sprintf("File does not has a valid extension '(%s)'.", implode("','", $allowed_extensions)));
                }

                if ($fileInfo->getSize(FileSizeUtil::B) > $max_file_size) {
                    throw new ValidationException(sprintf("File exceeds max_file_size (%s MB).", ($max_file_size / 1024) / 1024));
                }

                $file = $fileInfo->getFile();
                $localPath = null;
                if(is_null($file)){
                    // is not local storage we need to retrieve it
                    Log::debug
                    (
                        sprintf
                        (
                            "PresentationService::updateSlide file %s is not local, trying to retrieving from remote...",
                            $fileInfo->getFileName()
                        )
                    );
                    $localPath = self::getFileFromRemoteStorageOnTempStorage($fileInfo->getFileName(), $fileInfo->getFilePath());
                    $file = new UploadedFile($localPath, $fileInfo->getFileName());
                }

                $slideFile = $this->file_uploader->build
                (
                    $file,
                    sprintf('summits/%s/presentations/%s/slides', $presentation->getSummitId(), $presentation_id),
                    false
                );

                $slide->setSlide($slideFile);
                $slide->clearLink();

                if(!empty($localPath))
                    self::cleanLocalAndRemoteFile($localPath, $fileInfo->getFilePath());
            }

            if (isset($data['order']) && intval($slide_data['order']) != $slide->getOrder()) {
                // request to update order
                $presentation->recalculateMaterialOrder($slide, intval($slide_data['order']));
            }

            return $slide;

        });

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

        return $link;
    }

    // media uploads

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

        });
    }

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
        Summit         $summit,
        int            $presentation_id,
        array          $payload
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

            $maxUploadsQty = $mediaUploadType->getMaxUploadsQty();

            if ($maxUploadsQty != 0 && $presentation->getMediaUploadsCountByType($mediaUploadType) == $maxUploadsQty) {
                throw new ValidationException
                (
                    sprintf
                    (
                        "Presentation %s has reached the maximum media uploads qty allowed for the type %s.",
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

            ProcessMediaUpload::dispatch
            (
                $summit->getId(),
                $mediaUploadType->getId(),
                $mediaUpload->getPath(IStorageTypesConstants::PublicType),
                $mediaUpload->getPath(IStorageTypesConstants::PrivateType),
                $fileInfo->getFileName(),
                $fileInfo->getFilePath()
            );

            $mediaUpload->setFilename($fileInfo->getFileName());
            $presentation->addMediaUpload($mediaUpload);

            if (!$presentation->isCompleted()) {
                Log::debug(sprintf("PresentationService::addMediaUploadTo presentation %s is not complete", $presentation_id));
                $type = $presentation->getType();
                if ($type instanceof PresentationType) {
                    $summitMandatoryMediaUploadTypes = $type->getMandatoryAllowedMediaUploadTypes();
                    $summitMediaUploadCount = count($summitMandatoryMediaUploadTypes);

                    Log::debug("PresentationService::addMediaUploadTo presentation {$presentation_id} got summitMediaUploadCount {$summitMediaUploadCount}");

                    if ($summitMediaUploadCount == 0) {
                        Log::debug("PresentationService::addMediaUploadTo presentation {$presentation_id} marking as PHASE_UPLOADS (no mandatories uploads)");
                        $presentation->setProgress(Presentation::PHASE_UPLOADS);
                    } else {
                        $presentationMandatoryUploadsCountByType = $presentation->getMandatoryMediaUploadsCountByType();
                        $mandatoryIsCompleted = true;

                        foreach ($presentationMandatoryUploadsCountByType as $presentationMediaUploadTypeId => $uploadsCount) {
                            if (array_key_exists($presentationMediaUploadTypeId, $summitMandatoryMediaUploadTypes) &&
                                $uploadsCount < $summitMandatoryMediaUploadTypes[$presentationMediaUploadTypeId]->getMinUploadsQty()
                            ) {
                                $mandatoryIsCompleted = false;
                                break;
                            }
                        }

                        if ($mandatoryIsCompleted) {
                            Log::debug("PresentationService::addMediaUploadTo presentation {$presentation_id} marking as PHASE_UPLOADS (mandatories completed)");
                            $presentation->setProgress(Presentation::PHASE_UPLOADS);
                        }
                    }
                }
            }

            return $mediaUpload;
        });
    }

    /**
     * @param LaravelRequest $request
     * @param Summit $summit
     * @param int $presentation_id
     * @param int $media_upload_id
     * @param array $payload
     * @param Member|null $current_user
     * @return PresentationMediaUpload
     * @throws \Exception
     */
    public function updateMediaUploadFrom
    (
        LaravelRequest $request,
        Summit         $summit,
        int            $presentation_id,
        int            $media_upload_id,
        array          $payload,
        Member $current_user = null
    ): PresentationMediaUpload
    {
        return $this->tx_service->transaction(function () use (
            $request,
            $summit,
            $presentation_id,
            $media_upload_id,
            $payload,
            $current_user
        ) {

            Log::debug(sprintf("PresentationService::updateMediaUploadFrom summit %s presentation %s", $summit->getId(), $presentation_id));

            $presentation = $this->presentation_repository->getById($presentation_id);

            if (is_null($presentation) || !$presentation instanceof Presentation)
                throw new EntityNotFoundException('Presentation not found.');

            $mediaUpload = $presentation->getMediaUploadBy($media_upload_id);

            if (is_null($mediaUpload))
                throw new EntityNotFoundException('Presentation Media Upload not found.');

            $mediaUploadType = $mediaUpload->getMediaUploadType();

            // check edit permissions
            $canEdit = !is_null($current_user) && $current_user->isSummitAllowed($summit);
            if(!$canEdit){
                $canEdit = $mediaUploadType->isEditable();
            }

            if(!$canEdit){
                throw new ValidationException(sprintf("Media Upload Type %s is not editable.", $mediaUploadType->getName()));
            }

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

                ProcessMediaUpload::dispatch
                (
                    $summit->getId(),
                    $mediaUploadType->getId(),
                    $mediaUpload->getPath(IStorageTypesConstants::PublicType),
                    $mediaUpload->getPath(IStorageTypesConstants::PrivateType),
                    $fileInfo->getFileName(),
                    $fileInfo->getFilePath()
                );

                $payload['file_name'] = $fileInfo->getFileName();

            }

            return PresentationMediaUploadFactory::populate($mediaUpload, $payload);
        });
    }

    /**
     * @inheritDoc
     */
    public function deleteMediaUpload(Summit $summit, int $presentation_id, int $media_upload_id, Member $current_user = null): void
    {
        $this->tx_service->transaction(function () use (
            $summit,
            $presentation_id,
            $media_upload_id,
            $current_user
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

            // check edit permissions
            $canEdit = !is_null($current_user) && $current_user->isSummitAllowed($summit);
            if(!$canEdit){
                $canEdit = $mediaUploadType->isEditable();
            }

            if(!$canEdit){
                throw new ValidationException(sprintf("Media Upload Type %s is not editable.", $mediaUploadType->getName()));
            }

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

    /**
     * @param Summit $summit
     * @param Member $member
     * @param int $presentation_id
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function castAttendeeVote(Summit $summit, Member $member, int $presentation_id): PresentationAttendeeVote
    {
        return $this->tx_service->transaction(function () use ($summit, $member, $presentation_id) {

            Log::debug(sprintf("PresentationService::castAttendeeVote summit %s member %s presentation %s", $summit->getId(), $member->getId(), $presentation_id));
            $presentation = $this->presentation_repository->getById($presentation_id);
            if (is_null($presentation) || !$presentation instanceof Presentation || !$presentation->hasAccess($member))
                throw new EntityNotFoundException("Presentation not found.");

            if ($presentation->getSummitId() !== $summit->getId())
                throw new EntityNotFoundException("Presentation not found.");

            Log::debug("PresentationService::castAttendeeVote get attendee by member");
            $attendee = $summit->getAttendeeByMember($member);

            if (is_null($attendee))
                throw new ValidationException(sprintf("Current Member is not an attendee at Summit %s.", $summit->getId()));

            $currentTrack = $presentation->getCategory();

            foreach ($currentTrack->getGroups() as $currentTrackGroup) {
                Log::debug(sprintf("PresentationService::castAttendeeVote processing track group %s", $currentTrackGroup->getId()));
                // check voting period
                if (!$currentTrackGroup->isAttendeeVotingPeriodOpen())
                    throw new ValidationException(sprintf("Attendee Voting Period for track group %s is closed.", $currentTrackGroup->getName()));

                // check voting count

                if (!$currentTrackGroup->canEmitAttendeeVote($attendee)) {
                    throw new ValidationException(sprintf("You Reached the Max. allowed votes for Track Group %s [%s]",
                        $currentTrackGroup->getName(),
                        $currentTrackGroup->getMaxAttendeeVotes()));
                }
            }
            Log::debug("PresentationService::castAttendeeVote casting vote");
            return $presentation->castAttendeeVote($attendee);
        });
    }

    /**
     * @param Summit $summit
     * @param Member $member
     * @param int $presentation_id
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function unCastAttendeeVote(Summit $summit, Member $member, int $presentation_id): void
    {
        $this->tx_service->transaction(function () use ($summit, $member, $presentation_id) {
            $presentation = $this->presentation_repository->getById($presentation_id);
            if (is_null($presentation) || !$presentation instanceof Presentation)
                throw new EntityNotFoundException("Presentation not found.");

            if ($presentation->getSummitId() !== $summit->getId())
                throw new EntityNotFoundException("Presentation not found.");

            $attendee = $summit->getAttendeeByMember($member);

            if (is_null($attendee))
                throw new ValidationException(sprintf("Current Member is not an attendee at Summit %s.", $summit->getId()));

            $presentation->unCastAttendeeVote($attendee);
        });
    }

    /**
     * @inheritDoc
     */
    public function addTrackChairScore
    (
        Summit $summit,
        Member $member,
        int    $selection_plan_id,
        int    $presentation_id,
        int    $score_type_id
    ): PresentationTrackChairScore
    {
        return $this->tx_service->transaction(function () use ($summit, $member, $selection_plan_id, $presentation_id, $score_type_id) {

            $selectionPlan = $summit->getSelectionPlanById($selection_plan_id);

            if (is_null($selectionPlan))
                throw new EntityNotFoundException("Selection Plan not found.");

            if (!$selectionPlan->isSelectionOpen())
                throw new ValidationException(sprintf("Selection Period is over for Selection Plan %s", $selection_plan_id));

            $presentation = $this->presentation_repository->getById($presentation_id);
            if (is_null($presentation) || !$presentation instanceof Presentation)
                throw new EntityNotFoundException("Presentation not found.");

            if ($presentation->getSummitId() !== $summit->getId())
                throw new EntityNotFoundException("Presentation not found.");

            if ($presentation->getSelectionPlanId() !== $selection_plan_id)
                throw new EntityNotFoundException("Presentation not found.");

            $summit_track_chair = $summit->getTrackChairByMember($member);

            if (is_null($summit_track_chair))
                throw new ValidationException(sprintf("Can't find a track chair for current member at Summit %s.", $summit->getId()));

            if (!$summit_track_chair->isCategoryAllowed($presentation->getCategory())) {
                throw new ValidationException
                (
                    sprintf
                    (
                        "Track %s is not allowed for Track Chair %s.",
                        $presentation->getCategory()->getTitle(),
                        $summit_track_chair->getMember()->getFullName()
                    )
                );
            }

            $score_type = $this->presentation_track_chair_score_type_repository->getById($score_type_id);

            if (is_null($score_type) || !$score_type instanceof PresentationTrackChairScoreType)
                throw new EntityNotFoundException("Score type not found.");

            //Check if exists a score of the same rating type/presentation for this track chair, if so replace it by this new one
            $track_chair_score = $summit_track_chair->getScoreByRatingTypeAndPresentation($score_type->getType(), $presentation);

            if (!is_null($track_chair_score)) {

                // check if its the same type
                if ($track_chair_score->getType()->getId() === $score_type_id)
                    return $track_chair_score;
                // if not delete it
                $summit_track_chair->removeScore($track_chair_score);
                $track_chair_score = null;
            }

            $track_chair_score = new PresentationTrackChairScore();
            $track_chair_score->setType($score_type);
            $track_chair_score->setReviewer($summit_track_chair);
            $presentation->addTrackChairScore($track_chair_score);
            $summit_track_chair->addScore($track_chair_score);
            return $track_chair_score;
        });
    }

    /**
     * @inheritDoc
     */
    public function removeTrackChairScore
    (
        Summit $summit,
        Member $member,
        int    $selection_plan_id,
        int    $presentation_id,
        int    $score_type_id
    ): void
    {
        $this->tx_service->transaction(function () use ($summit, $member, $selection_plan_id, $presentation_id, $score_type_id) {

            $selectionPlan = $summit->getSelectionPlanById($selection_plan_id);

            if (is_null($selectionPlan))
                throw new EntityNotFoundException("Selection Plan not found.");

            if (!$selectionPlan->isSelectionOpen())
                throw new ValidationException(sprintf("Selection Period is over for Selection Plan %s", $selection_plan_id));

            $presentation = $this->presentation_repository->getById($presentation_id);
            if (is_null($presentation) || !$presentation instanceof Presentation)
                throw new EntityNotFoundException("Presentation not found.");

            if ($presentation->getSummitId() !== $summit->getId())
                throw new EntityNotFoundException("Presentation not found.");

            if ($presentation->getSelectionPlanId() !== $selection_plan_id)
                throw new EntityNotFoundException("Presentation not found.");

            $summit_track_chair = $summit->getTrackChairByMember($member);

            if (is_null($summit_track_chair))
                throw new ValidationException(sprintf("Can't find a track chair for current member at Summit %s.", $summit->getId()));

            if (!$summit_track_chair->isCategoryAllowed($presentation->getCategory())) {
                throw new ValidationException
                (
                    sprintf
                    (
                        "Track %s is not allowed for Track Chair %s.",
                        $presentation->getCategory()->getTitle(),
                        $summit_track_chair->getMember()->getFullName()
                    )
                );
            }

            $score_type = $this->presentation_track_chair_score_type_repository->getById($score_type_id);

            if (is_null($score_type) || !$score_type instanceof PresentationTrackChairScoreType)
                throw new EntityNotFoundException("Score type not found.");

            //Check if exists a score of the same rating type/presentation for this track chair, if so replace it by this new one
            $rating_type = $score_type->getType();

            $track_chair_score = $summit_track_chair->getScoreByRatingTypeAndPresentation($rating_type, $presentation);

            if (is_null($track_chair_score)) {
                throw new EntityNotFoundException("Score not found.");
            }

            $summit_track_chair->removeScore($track_chair_score);

        });
    }

    /**
     * @param Summit $summit
     * @param int $presentation_id
     * @param int $comment_id
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function deletePresentationComment(Summit $summit, int $presentation_id, int $comment_id): void
    {
        $this->tx_service->transaction(function () use ($summit, $presentation_id, $comment_id) {
            $presentation = $summit->getEvent($presentation_id);
            if (!$presentation instanceof Presentation)
                throw new EntityNotFoundException("Presentation not found.");

            $comment = $presentation->getComment($comment_id);
            if (!$comment instanceof SummitPresentationComment)
                throw new EntityNotFoundException("Presentation Comment not found.");

            $presentation->removeComment($comment);
        });
    }

    /**
     * @param Summit $summit
     * @param int $presentation_id
     * @param int $comment_id
     * @param array $payload
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function updatePresentationComment(Summit $summit, int $presentation_id, int $comment_id, array $payload): SummitPresentationComment
    {
        return $this->tx_service->transaction(function () use ($summit, $presentation_id, $comment_id, $payload) {
            $presentation = $summit->getEvent($presentation_id);
            if (!$presentation instanceof Presentation)
                throw new EntityNotFoundException("Presentation not found.");

            $comment = $presentation->getComment($comment_id);
            if (!$comment instanceof SummitPresentationComment)
                throw new EntityNotFoundException("Presentation Comment not found.");

            return SummitPresentationCommentFactory::populate($comment, $payload);
        });
    }

    /**
     * @param Summit $summit
     * @param int $presentation_id
     * @param Member $current_user
     * @param array $payload
     * @return SummitPresentationComment
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function createPresentationComment(Summit $summit, int $presentation_id, Member $current_user, array $payload): SummitPresentationComment
    {
        return $this->tx_service->transaction(function () use ($summit, $presentation_id, $current_user, $payload) {
            $presentation = $summit->getEvent($presentation_id);
            if (!$presentation instanceof Presentation)
                throw new EntityNotFoundException("Presentation not found.");

            $comment = SummitPresentationCommentFactory::build($current_user, $payload);
            $presentation->addPresentationComment($comment);
            return $comment;
        });
    } // bytes

    /**
     * @param int $summit_id
     * @param int $media_upload_type_id
     * @param string|null $public_path
     * @param string|null $private_path
     * @param string $file_name
     * @param string $path
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function processMediaUpload(int $summit_id, int $media_upload_type_id, ?string $public_path, ?string $private_path, string $file_name, string $path): void
    {
        $this->tx_service->transaction(function () use (
            $summit_id,
            $media_upload_type_id,
            $public_path,
            $private_path,
            $file_name,
            $path
        ) {
            Log::debug(sprintf("PresentationService::processMediaUpload summit id %s media upload type id %s public path %s private path %s file name %s path %s",
                $summit_id,
                $media_upload_type_id,
                $public_path,
                $private_path,
                $file_name,
                $path
            ));

            $summit = $this->summit_repository->getById($summit_id);
            if (is_null($summit)) {
                throw new EntityNotFoundException(sprintf("Summit %s not found.", $summit_id));
            }

            $mediaUploadType = $summit->getMediaUploadTypeById($media_upload_type_id);
            if (is_null($mediaUploadType)) {
                throw new EntityNotFoundException(sprintf("Media Upload Type %s not found.", $media_upload_type_id));
            }


            $localPath = self::getFileFromRemoteStorageOnTempStorage($file_name, $path);

            $strategy = FileUploadStrategyFactory::build($mediaUploadType->getPrivateStorageType());
            if (!is_null($strategy)) {
                Log::debug(sprintf("PresentationService::processMediaUpload saving file %s to private storage", $file_name));
                $strategy->saveFromPath(
                    $localPath,
                    $private_path,
                    $file_name
                );
            }

            $strategy = FileUploadStrategyFactory::build($mediaUploadType->getPublicStorageType());
            if (!is_null($strategy)) {
                Log::debug(sprintf("PresentationService::processMediaUpload saving file %s to public storage", $file_name));
                $options = $mediaUploadType->isUseTemporaryLinksOnPublicStorage() ? [] : 'public';
                $strategy->saveFromPath
                (
                    $localPath,
                    $public_path,
                    $file_name,
                    $options
                );
            }

            self::cleanLocalAndRemoteFile($localPath, $path);
        });
    }

    /**
     * @param Summit $summit
     * @param int $presentation_id
     * @param int $speaker_id
     * @param array $data
     * @return Presentation
     * @throws \Exception
     */
    public function upsertPresentationSpeaker(Summit $summit, int $presentation_id, int $speaker_id, array $data): Presentation {
        return $this->tx_service->transaction(function () use ($summit, $presentation_id, $speaker_id, $data) {

            $presentation = $summit->getEvent($presentation_id);
            if (!$presentation instanceof Presentation)
                throw new EntityNotFoundException("Presentation {$presentation_id} not found.");

            $speaker = $this->speaker_repository->getById($speaker_id);
            if (is_null($speaker) || !($speaker instanceof PresentationSpeaker))
                throw new EntityNotFoundException("Speaker {$speaker_id} not found.");

            if (!$presentation->isSpeaker($speaker)) {
                $presentation->addSpeaker($speaker);
            }

            if (isset($data['order'])) {
                $new_order = intval($data['order']);
                $current_order = $presentation->getSpeakerOrder($speaker);
                if ($current_order != $new_order) $presentation->updateSpeakerOrder($speaker, $new_order);
            }

            return $presentation;
        });
    }

    /**
     * @param Summit $summit
     * @param int $presentation_id
     * @param int $speaker_id
     * @return Presentation
     * @throws \Exception
     */
    public function removeSpeakerFromPresentation(Summit $summit, int $presentation_id, int $speaker_id): void
    {
         $this->tx_service->transaction(function () use ($summit, $presentation_id, $speaker_id) {

            $presentation = $summit->getEvent($presentation_id);
            if (!$presentation instanceof Presentation)
                throw new EntityNotFoundException("Presentation {$presentation_id} not found.");

            $speaker = $this->speaker_repository->getById(intval($speaker_id));
            if (is_null($speaker) || !($speaker instanceof PresentationSpeaker))
                throw new EntityNotFoundException("Speaker {$speaker_id} not found.");

            $presentation->removeSpeaker($speaker);

        });
    }
}