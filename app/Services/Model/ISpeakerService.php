<?php namespace services\model;
/**
 * Copyright 2017 OpenStack Foundation
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
use App\Models\Foundation\Summit\Speakers\SpeakerEditPermissionRequest;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use models\main\File;
use models\main\Member;
use models\summit\PresentationSpeaker;
use models\summit\PresentationSpeakerSummitAssistanceConfirmationRequest;
use models\summit\SpeakerSummitRegistrationPromoCode;
use models\summit\Summit;
use Illuminate\Http\UploadedFile;
use utils\Filter;

/**
 * Interface ISpeakerService
 * @package services\model
 */
interface ISpeakerService
{
    /**
     * @param Summit $summit
     * @param array $data
     * @return PresentationSpeaker
     * @throws ValidationException
     */
    public function addSpeakerBySummit(Summit $summit, array $data);

    /**
     * @param array $data
     * @param null|Member $creator
     * @param bool $send_email
     * @return PresentationSpeaker
     * @throws ValidationException
     */
    public function addSpeaker(array $data, ?Member $creator = null, $send_email = true);

    /**
     * @param Summit $summit
     * @param array $data
     * @param PresentationSpeaker $speaker
     * @return PresentationSpeaker
     * @throws ValidationException
     */
    public function updateSpeakerBySummit(Summit $summit, PresentationSpeaker $speaker, array $data);

    /**
     * @param array $data
     * @param PresentationSpeaker $speaker
     * @return PresentationSpeaker
     * @throws ValidationException
     */
    public function updateSpeaker(PresentationSpeaker $speaker, array $data);

    /**
     * @param PresentationSpeaker $speaker
     * @param Summit $summit
     * @param string $reg_code
     * @return SpeakerSummitRegistrationPromoCode
     * @throws ValidationException
     */
    public function registerSummitPromoCodeByValue(PresentationSpeaker $speaker, Summit $summit, $reg_code);

    /**
     * @param PresentationSpeaker $speaker_from
     * @param PresentationSpeaker $speaker_to
     * @param array $data
     * @return void
     */
    public function merge(PresentationSpeaker $speaker_from, PresentationSpeaker $speaker_to, array $data);

    /**
     * @param int $speaker_id
     * @throws ValidationException
     * @throws EntityNotFoundException
     * @return void
     */
    public function deleteSpeaker($speaker_id);

    /**
     * @param Summit $summit
     * @param array $data
     * @throws ValidationException
     * @throws EntityNotFoundException
     * @return PresentationSpeakerSummitAssistanceConfirmationRequest
     */
    public function addSpeakerAssistance(Summit $summit, array $data);

    /**
     * @param Summit $summit
     * @param int $assistance_id
     * @param array $data
     * @throws ValidationException
     * @throws EntityNotFoundException
     * @return PresentationSpeakerSummitAssistanceConfirmationRequest
     */
    public function updateSpeakerAssistance(Summit $summit, $assistance_id, array $data);

    /**
     * @param Summit $summit
     * @param int $assistance_id
     * @throws ValidationException
     * @throws EntityNotFoundException
     * @return void
     */
    public function deleteSpeakerAssistance(Summit $summit, $assistance_id);

    /**
     * @param int $requested_by_id
     * @param int $speaker_id
     * @return SpeakerEditPermissionRequest
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function requestSpeakerEditPermission(int $requested_by_id, int $speaker_id):SpeakerEditPermissionRequest;

    /**
     * @param int $requested_by_id
     * @param int $speaker_id
     * @return SpeakerEditPermissionRequest
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function getSpeakerEditPermission(int $requested_by_id, int $speaker_id):SpeakerEditPermissionRequest;

    /**
     * @param string $token
     * @param int $speaker_id
     * @return SpeakerEditPermissionRequest
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function approveSpeakerEditPermission(string $token, int $speaker_id):SpeakerEditPermissionRequest;

    /**
     * @param string $token
     * @param int $speaker_id
     * @return SpeakerEditPermissionRequest
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function rejectSpeakerEditPermission(string $token, int $speaker_id):SpeakerEditPermissionRequest;

    /**
     * @param int $speaker_id
     * @param UploadedFile $file
     * @param int $max_file_size
     * @throws ValidationException
     * @throws EntityNotFoundException
     * @return File
     */
    public function addSpeakerPhoto($speaker_id, UploadedFile $file,  $max_file_size = 10485760);

    /**
     * @param $speaker_id
     * @throws ValidationException
     * @throws EntityNotFoundException
     */
    public function deleteSpeakerPhoto($speaker_id):void;

    /**
     * @param int $speaker_id
     * @param UploadedFile $file
     * @param int $max_file_size
     * @throws ValidationException
     * @throws EntityNotFoundException
     * @return File
     */
    public function addSpeakerBigPhoto($speaker_id, UploadedFile $file,  $max_file_size = 10485760);

    /**
     * @param $speaker_id
     * @throws ValidationException
     * @throws EntityNotFoundException
     */
    public function deleteSpeakerBigPhoto($speaker_id):void;

    /**
     * @param Summit $summit
     * @param array $payload
     * @param mixed $filter
     */
    public function triggerSendEmails(Summit $summit, array $payload, $filter = null):void;

    /**
     * @param int $summit_id
     * @param array $payload
     * @param Filter|null $filter
     */
    public function sendEmails(int $summit_id, array $payload, Filter $filter = null):void;

    /**
     * @param Member $member
     * @return PresentationSpeaker|null
     */
    public function getSpeakerByMember(Member $member):?PresentationSpeaker;
}