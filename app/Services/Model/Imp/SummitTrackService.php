<?php namespace App\Services\Model;

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

use App\Events\ScheduleEntityLifeCycleEvent;
use App\Http\Utils\IFileUploader;
use App\Models\Foundation\Summit\Factories\PresentationCategoryFactory;
use App\Models\Foundation\Summit\Repositories\ISummitTrackRepository;
use App\Models\Foundation\Summit\Repositories\ITrackQuestionTemplateRepository;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use libs\utils\ITransactionService;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use models\main\ITagRepository;
use models\summit\PresentationCategory;
use models\summit\Summit;

/**
 * Class SummitTrackService
 * @package App\Services\Model
 */
final class SummitTrackService
    extends AbstractService
    implements ISummitTrackService
{
    /**
     * @var ISummitTrackRepository
     */
    private $track_repository;

    /**
     * @var ITagRepository
     */
    private $tag_repository;

    /**
     * @var ITrackQuestionTemplateRepository
     */
    private $track_question_template_repository;

    /**
     * @var IFileUploader
     */
    private $file_uploader;

    /**
     * SummitTrackService constructor.
     * @param ISummitTrackRepository $track_repository
     * @param ITagRepository $tag_repository
     * @param ITrackQuestionTemplateRepository $track_question_template_repository
     * @param IFileUploader $file_uploader
     * @param ITransactionService $tx_service
     */
    public function __construct
    (
        ISummitTrackRepository           $track_repository,
        ITagRepository                   $tag_repository,
        ITrackQuestionTemplateRepository $track_question_template_repository,
        IFileUploader                    $file_uploader,
        ITransactionService              $tx_service
    )
    {
        parent::__construct($tx_service);
        $this->tag_repository = $tag_repository;
        $this->track_repository = $track_repository;
        $this->track_question_template_repository = $track_question_template_repository;
        $this->file_uploader = $file_uploader;
    }

    /**
     * @param Summit $summit
     * @param array $data
     * @return PresentationCategory
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function addTrack(Summit $summit, array $data)
    {
        return $this->tx_service->transaction(function () use ($summit, $data) {

            if (!empty($data['code'])) {
                $former_track = $summit->getPresentationCategoryByCode(trim($data['code']));
                if (!is_null($former_track))
                    throw new ValidationException(sprintf("track id %s already has code %s assigned on summit id %s", $former_track->getId(), $data['code'], $summit->getId()));
            }

            $former_track = $summit->getPresentationCategoryByTitle($data['name']);
            if (!is_null($former_track))
                throw new ValidationException(sprintf("track id %s already has title %s assigned on summit id %s", $former_track->getId(), $data['name'], $summit->getId()));

            $track = PresentationCategoryFactory::build($summit, $data);

            if (isset($data['allowed_tags'])) {
                foreach ($data['allowed_tags'] as $tag_value) {
                    $tackTagGroupAllowedTag = $summit->getAllowedTagOnTagTrackGroup($tag_value);
                    if (is_null($tackTagGroupAllowedTag)) {
                        throw new ValidationException(
                            sprintf("allowed_tags : tag value %s is not allowed on current track tag groups for summit %s", $tag_value, $summit->getId())
                        );
                    }
                    $track->addAllowedTag($tackTagGroupAllowedTag->getTag());
                }
            }

            if (isset($data['allowed_access_levels'])) {
                foreach ($data['allowed_access_levels'] as $access_level_id) {
                    $access_level = $summit->getBadgeAccessLevelTypeById(intval($access_level_id));
                    if (is_null($access_level)) {
                        throw new EntityNotFoundException(
                            sprintf("allowed_access_levels : access level %s does not exists.", $access_level_id)
                        );
                    }
                    $track->addAllowedAccessLevel($access_level);
                }
            }

            $summit->addPresentationCategory($track);

            return $track;
        });
    }

    /**
     * @param Summit $summit
     * @param int $track_id
     * @param array $data
     * @return PresentationCategory
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function updateTrack(Summit $summit, $track_id, array $data)
    {
        Log::debug(sprintf("SummitTrackService::UpdateTrack %s ", $track_id));

        return $this->tx_service->transaction(function () use ($summit, $track_id, $data) {

            $track = $summit->getPresentationCategory($track_id);

            if (is_null($track))
                throw new EntityNotFoundException
                (
                    sprintf("track id %s does not belong to summit id %s", $track_id, $summit->getId())
                );

            if (isset($data['code']) && !empty($data['code'])) {
                $former_track = $summit->getPresentationCategoryByCode($data['code']);
                if (!is_null($former_track) && $former_track->getId() != $track_id)
                    throw new ValidationException(sprintf("track id %s already has code %s assigned on summit id %s", $former_track->getId(), $data['code'], $summit->getId()));
            }

            if (isset($data['name'])) {
                $former_track = $summit->getPresentationCategoryByTitle($data['name']);
                if (!is_null($former_track) && $former_track->getId() != $track_id)
                    throw new ValidationException(sprintf("track id %s already has title %s assigned on summit id %s", $former_track->getId(), $data['name'], $summit->getId()));
            }

            $track = PresentationCategoryFactory::populate($track, $data);

            if (isset($data['allowed_tags'])) {
                $track->clearAllowedTags();
                foreach ($data['allowed_tags'] as $tag_value) {
                    $tackTagGroupAllowedTag = $summit->getAllowedTagOnTagTrackGroup($tag_value);
                    if (is_null($tackTagGroupAllowedTag)) {
                        throw new ValidationException(
                            sprintf("allowed_tags : tag value %s is not allowed on current track tag groups for summit %s", $tag_value, $summit->getId())
                        );
                    }
                    $track->addAllowedTag($tackTagGroupAllowedTag->getTag());
                }
            }

            if (isset($data['allowed_access_levels'])) {
                $track->clearAllowedAccessLevels();
                foreach ($data['allowed_access_levels'] as $access_level_id) {
                    Log::debug(sprintf("SummitTrackService::UpdateTrack %s trying to add access level %s", $track_id, $access_level_id));
                    $access_level = $summit->getBadgeAccessLevelTypeById(intval($access_level_id));
                    if (is_null($access_level)) {
                        throw new EntityNotFoundException(
                            sprintf("allowed_access_levels : access level %s does not exists.", $access_level_id)
                        );
                    }
                    $track->addAllowedAccessLevel($access_level);
                    Log::debug(sprintf("SummitTrackService::UpdateTrack %s added access level %s", $track_id, $access_level_id));
                }
            }

            if (isset($data['order']) && intval($data['order']) != $track->getOrder()) {
                // request to update order
                $summit->recalculateTrackOrder($track, intval($data['order']));
            }

            return $track;

        });
    }

    /**
     * @param Summit $summit
     * @param $track_id
     * @return void
     * @throws \Exception
     */
    public function deleteTrack(Summit $summit, $track_id):void
    {
        $this->tx_service->transaction(function () use ($summit, $track_id) {

            $track = $summit->getPresentationCategory($track_id);

            if (is_null($track))
                throw new EntityNotFoundException
                (
                    sprintf("track id %s does not belong to summit id %s", $track_id, $summit->getId())
                );

            $summit_events = $track->getRelatedPublishedSummitEventsIds();

            if (count($summit_events) > 0) {
                throw new ValidationException
                (
                    sprintf
                    (
                        "Track %s (%s) can not be deleted bc its assigned to published events on summit id %s",
                        $track->getTitle(),
                        $track_id,
                        $summit->getId())
                );
            }

            if($track->hasSubTracks()){
                foreach ($track->getSubTracks() as $subtrack){
                    $subtrack->clearParent();
                }
            }

            $this->track_repository->delete($track);
        });
    }

    /**
     * @param Summit $from_summit
     * @param Summit $to_summit
     * @return PresentationCategory[]
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function copyTracks(Summit $from_summit, Summit $to_summit)
    {
        return $this->tx_service->transaction(function () use ($from_summit, $to_summit) {

            if ($from_summit->getId() == $to_summit->getId()) {
                throw new ValidationException
                (
                    trans
                    (
                        'validation_errors.SummitTrackService.copyTracks.SameSummit'
                    )
                );
            }

            $added_tracks = [];
            foreach ($from_summit->getPresentationCategories() as $track_2_copy) {
                $former_track = $to_summit->getPresentationCategoryByTitle($track_2_copy->getTitle());
                if (!is_null($former_track)) continue;

                $former_track = $to_summit->getPresentationCategoryByCode($track_2_copy->getCode());
                if (!is_null($former_track)) continue;

                $data = [
                    'name' => $track_2_copy->getTitle(),
                    'code' => $track_2_copy->getCode(),
                    'color' => $track_2_copy->getColor(),
                    'description' => $track_2_copy->getDescription(),
                    'session_count' => $track_2_copy->getSessionCount(),
                    'alternate_count' => $track_2_copy->getAlternateCount(),
                    'lightning_count' => $track_2_copy->getLightningCount(),
                    'lightning_alternate_count' => $track_2_copy->getLightningAlternateCount(),
                    'voting_visible' => $track_2_copy->isVotingVisible(),
                    'chair_visible' => $track_2_copy->isChairVisible(),
                    'order' => $track_2_copy->getOrder(),
                ];

                $new_track = PresentationCategoryFactory::build($to_summit, $data);

                $to_summit->addPresentationCategory($new_track);
                $added_tracks[] = $new_track;
            }

            return $added_tracks;
        });

    }

    /**
     * @param int $track_id
     * @param int $question_id
     * @return void
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function addTrackExtraQuestion($track_id, $question_id)
    {
        return $this->tx_service->transaction(function () use ($track_id, $question_id) {
            $track = $this->track_repository->getById($track_id);
            if (is_null($track))
                throw new EntityNotFoundException(
                    trans
                    (
                        'not_found_errors.SummitTrackService.addTrackExtraQuestion.TrackNotFound',
                        ['track_id' => $track_id]
                    )
                );

            $track_question_template = $this->track_question_template_repository->getById($question_id);

            if (is_null($track_question_template))
                throw new EntityNotFoundException(
                    trans
                    (
                        'not_found_errors.SummitTrackService.addTrackExtraQuestion.QuestionNotFound',
                        ['question_id' => $question_id]
                    )
                );

            $track->addExtraQuestion($track_question_template);
        });
    }

    /**
     * @param int $track_id
     * @param int $question_id
     * @return void
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function removeTrackExtraQuestion($track_id, $question_id)
    {
        return $this->tx_service->transaction(function () use ($track_id, $question_id) {
            $track = $this->track_repository->getById($track_id);
            if (is_null($track))
                throw new EntityNotFoundException(
                    trans
                    (
                        'not_found_errors.SummitTrackService.removeTrackExtraQuestion.TrackNotFound',
                        ['track_id' => $track_id]
                    )
                );

            $track_question_template = $this->track_question_template_repository->getById($question_id);

            if (is_null($track_question_template))
                throw new EntityNotFoundException(
                    trans
                    (
                        'not_found_errors.SummitTrackService.removeTrackExtraQuestion.QuestionNotFound',
                        ['question_id' => $question_id]
                    )
                );

            $track->removeExtraQuestion($track_question_template);
        });
    }

    /**
     * @inheritDoc
     */
    public function addTrackIcon(Summit $summit, $track_id, UploadedFile $file, $max_file_size = 10485760)
    {
        return $this->tx_service->transaction(function () use ($summit, $track_id, $file, $max_file_size) {

            $allowed_extensions = ['png', 'jpg', 'jpeg', 'gif', 'svg'];

            $track = $summit->getPresentationCategory($track_id);

            if (is_null($track) || !$track instanceof PresentationCategory) {
                throw new EntityNotFoundException('track not found on summit!');
            }

            if (!in_array($file->extension(), $allowed_extensions)) {
                throw new ValidationException("file does not has a valid extension ('png','jpg','jpeg','gif').");
            }

            if ($file->getSize() > $max_file_size) {
                throw new ValidationException(sprintf("file exceeds max_file_size (%s MB).", ($max_file_size / 1024) / 1024));
            }

            $file = $this->file_uploader->build($file, 'summit-track-icon', true);
            $track->setIcon($file);

            return $file;
        });
    }

    /**
     * @inheritDoc
     */
    public function removeTrackIcon(Summit $summit, $track_id): void
    {
        $this->tx_service->transaction(function () use ($summit, $track_id) {

            $track = $summit->getPresentationCategory($track_id);

            if (is_null($track) || !$track instanceof PresentationCategory) {
                throw new EntityNotFoundException('track not found on summit!');
            }
            $track->clearIcon();
        });
    }

    /**
     * @inheritDoc
     */
    public function addSubTrack(Summit $summit, int $track_id, int $child_track_id, array $data):PresentationCategory
    {
        $track = $this->tx_service->transaction(function () use ($summit, $track_id, $child_track_id, $data) {

            $track = $summit->getPresentationCategory($track_id);

            if (!$track instanceof PresentationCategory) {
                throw new EntityNotFoundException('Track not found on summit.');
            }

            if ($summit->hasRelatedActivities($track)) {
                throw new ValidationException('Can not add a sub track to a track assigned to activities.');
            }

            if($track->hasParent()){
                throw new ValidationException('Parent Track already has a parent.');
            }

            $child_track = $summit->getPresentationCategory($child_track_id);

            if (!$child_track instanceof PresentationCategory) {
                throw new EntityNotFoundException('Sub track not found on summit.');
            }

            if($child_track->hasSubTracks()){
                throw new ValidationException('Sub track already has sub tracks.');
            }

            $track->addChild($child_track);

            if (isset($data['order'])) {
                $new_order = intval($data['order']);
                $track->recalculateSubTrackOrder($child_track, $new_order);
            }

            return $track;
        });

          Event::dispatch(new ScheduleEntityLifeCycleEvent(ScheduleEntityLifeCycleEvent::Operation_Update,
              $track->getSummitId(),
              $track->getId(),
              'PresentationCategory'));

          return $track;
    }

    /**
     * @inheritDoc
     */
    public function removeSubTrack(Summit $summit, int $track_id, int $child_track_id): PresentationCategory {

        $track = $this->tx_service->transaction(function () use ($summit, $track_id, $child_track_id) {

            $track = $summit->getPresentationCategory($track_id);

            if (!$track instanceof PresentationCategory) {
                throw new EntityNotFoundException('Track not found on summit.');
            }

            $child_track = $summit->getPresentationCategory($child_track_id);

            if (!$child_track instanceof PresentationCategory) {
                throw new EntityNotFoundException('Sub track not found on summit.');
            }

            $track->removeChild($child_track);

            return $track;
        });

        Event::dispatch(new ScheduleEntityLifeCycleEvent(ScheduleEntityLifeCycleEvent::Operation_Update,
            $track->getSummitId(),
            $track->getId(),
            'PresentationCategory'));
        return $track;
    }
}