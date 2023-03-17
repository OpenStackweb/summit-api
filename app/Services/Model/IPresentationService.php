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

use App\Models\Foundation\Summit\Events\Presentations\TrackChairs\PresentationTrackChairScore;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use models\main\Member;
use models\summit\Presentation;
use models\summit\PresentationAttendeeVote;
use models\summit\PresentationLink;
use models\summit\PresentationMediaUpload;
use models\summit\PresentationSlide;
use models\summit\PresentationVideo;
use models\summit\Summit;
use Illuminate\Http\Request as LaravelRequest;
use models\summit\SummitPresentationComment;

/**
 * Interface IPresentationService
 * @package services\model
 */
interface IPresentationService
{
    /**
     * @param int $presentation_id
     * @param array $video_data
     * @return PresentationVideo
     */
    public function addVideoTo($presentation_id, array $video_data);

    /**
     * @param int $presentation_id
     * @param int $video_id
     * @param array $video_data
     * @return PresentationVideo
     */
    public function updateVideo($presentation_id, $video_id, array $video_data);


    /**
     * @param int $presentation_id
     * @param int $video_id
     * @return void
     */
    public function deleteVideo($presentation_id, $video_id);

    /**
     * @param Summit $summit
     * @param array $data
     * @return Presentation
     * @throws ValidationException
     * @throws EntityNotFoundException
     */
    public function submitPresentation(Summit $summit, array $data);

    /**
     * @param Summit $summit
     * @param int $presentation_id
     * @param array $data
     * @return Presentation
     * @throws ValidationException
     * @throws EntityNotFoundException
     */
    public function updatePresentationSubmission(Summit $summit, $presentation_id, array $data);


    /**
     * @param Summit $summit
     * @param int $presentation_id
     * @return Presentation
     * @throws ValidationException
     * @throws EntityNotFoundException
     */
    public function completePresentationSubmission(Summit $summit, $presentation_id);

    /**
     * @param Summit $summit
     * @param int $presentation_id
     * @return void
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function deletePresentation(Summit $summit, $presentation_id);

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
        $max_file_size = 1048576 // bytes
    );

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
        $max_file_size = 62914560
    );

    /**
     * @param int $presentation_id
     * @param int $slide_id
     * @return void
     */
    public function deleteSlide($presentation_id, $slide_id);

    /**
     * @param $presentation_id
     * @param array $link_data
     * @return PresentationLink
     */
    public function addLinkTo($presentation_id, array $link_data);

    /**
     * @param $presentation_id
     * @param $link_id
     * @param array $link_data
     * @return PresentationLink
     */
    public function updateLink($presentation_id, $link_id, array $link_data);

    /**
     * @param int $presentation_id
     * @param int $link_id
     * @return void
     */
    public function deleteLink($presentation_id, $link_id);

    /**
     * @param LaravelRequest $request
     * @param Summit $summit
     * @param $presentation_id
     * @param array $payload
     * @return PresentationMediaUpload
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function addMediaUploadTo
    (
        LaravelRequest $request,
        Summit $summit,
        int $presentation_id,
        array $payload
    ): PresentationMediaUpload;

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
    ): PresentationMediaUpload;

    /**
     * @param Summit $summit
     * @param int $presentation_id
     * @param int $media_upload_id
     * @throws EntityNotFoundException
     */
    public function deleteMediaUpload(Summit $summit, int $presentation_id, int $media_upload_id): void;

    /**
     * @param Summit $summit
     * @param Member $member
     * @param int $presentation_id
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function castAttendeeVote(Summit $summit, Member $member, int $presentation_id):PresentationAttendeeVote;

    /**
     * @param Summit $summit
     * @param Member $member
     * @param int $presentation_id
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function unCastAttendeeVote(Summit $summit, Member $member, int $presentation_id):void;

    /**
     * @param Summit $summit
     * @param Member $member
     * @param int $selection_plan_id
     * @param int $presentation_id
     * @param int $score_type_id
     * @return PresentationTrackChairScore
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function addTrackChairScore(Summit $summit, Member $member, int $selection_plan_id , int $presentation_id, int $score_type_id):PresentationTrackChairScore;

    /**
     * @param Summit $summit
     * @param Member $member
     * @param int $selection_plan_id
     * @param int $presentation_id
     * @param int $score_type_id
     * @return void
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function removeTrackChairScore
    (
        Summit $summit,
        Member $member,
        int $selection_plan_id,
        int $presentation_id,
        int $score_type_id
    ):void;

    /**
     * @param Summit $summit
     * @param int $presentation_id
     * @param int $comment_id
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function deletePresentationComment(Summit $summit, int $presentation_id, int $comment_id):void;

    /**
     * @param Summit $summit
     * @param int $presentation_id
     * @param Member $current_user
     * @param array $payload
     * @return SummitPresentationComment
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function createPresentationComment(Summit $summit, int $presentation_id, Member $current_user, array $payload):SummitPresentationComment;

    /**
     * @param Summit $summit
     * @param int $presentation_id
     * @param int $comment_id
     * @param array $payload
     * @return SummitPresentationComment
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function updatePresentationComment(Summit $summit, int $presentation_id, int $comment_id, array $payload):SummitPresentationComment;

    /**
     * @param Summit $summit
     * @param int $presentation_id
     * @param int $speaker_id
     * @param array $data
     * @return Presentation
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function upsertPresentationSpeaker(Summit $summit, int $presentation_id, int $speaker_id, array $data): Presentation;

    /**
     * @param Summit $summit
     * @param int $presentation_id
     * @param int $speaker_id
     * @return Presentation
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function removeSpeakerFromPresentation(Summit $summit, int $presentation_id, int $speaker_id): Presentation;
}