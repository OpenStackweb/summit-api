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

use Illuminate\Http\UploadedFile;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use models\summit\PresentationCategory;
use models\summit\Summit;
use models\summit\SummitEvent;

/**
 * Interface ISummitTrackService
 * @package App\Services\Model
 */
interface ISummitTrackService
{
    /**
     * @param Summit $summit
     * @param array $data
     * @return PresentationCategory
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function addTrack(Summit $summit, array $data);

    /**
     * @param Summit $summit
     * @param int $track_id
     * @param array $data
     * @return PresentationCategory
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function updateTrack(Summit $summit, $track_id, array $data);

    /**
     * @param Summit $summit
     * @param int $track_id
     * @return void
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function deleteTrack(Summit $summit, $track_id);

    /**
     * @param Summit $from_summit
     * @param Summit $to_summit
     * @return PresentationCategory[]
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function copyTracks(Summit $from_summit, Summit $to_summit);

    /**
     * @param int $track_id
     * @param int $question_id
     * @return void
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function addTrackExtraQuestion($track_id, $question_id);


    /**
     * @param int $track_id
     * @param int $question_id
     * @return void
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function removeTrackExtraQuestion($track_id, $question_id);

    /**
     * @param Summit $summit
     * @param int $track_id
     * @param UploadedFile $file
     * @param int $max_file_size
     * @throws ValidationException
     * @throws EntityNotFoundException
     * @return SummitEvent
     */
    public function addTrackIcon(Summit $summit, $track_id, UploadedFile $file,  $max_file_size = 10485760);

    /**
     * @param Summit $summit
     * @param int $track_id
     * @throws ValidationException
     * @throws EntityNotFoundException
     * @return void
     */
    public function removeTrackIcon(Summit $summit, $track_id):void;

}