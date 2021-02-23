<?php namespace App\Services\Model;
/**
 * Copyright 2021 OpenStack Foundation
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
use App\Models\Exceptions\AuthzException;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use models\summit\Summit;
use models\summit\SummitTrackChair;
/**
 * Interface ITrackChairService
 * @package App\Services\Model
 */
interface ITrackChairService
{
    /**
     * @param Summit $summit
     * @param array $payload
     * @return SummitTrackChair
     * @throws EntityNotFoundException
     * @throws ValidationException
     * @throws AuthzException
     */
    public function addTrackChair(Summit $summit, array $payload):SummitTrackChair;

    /**
     * @param Summit $summit
     * @param int $track_chair_id
     * @param array $payload
     * @return SummitTrackChair
     * @throws EntityNotFoundException
     * @throws ValidationException
     * @throws AuthzException
     */
    public function updateTrackChair(Summit $summit, int $track_chair_id, array $payload): SummitTrackChair;

    /**
     * @param Summit $summit
     * @param int $track_chair_id
     * @throws EntityNotFoundException
     * @throws ValidationException
     * @throws AuthzException
     */
    public function deleteTrackChair(Summit $summit, int $track_chair_id):void;

    /**
     * @param Summit $summit
     * @param int $track_chair_id
     * @param int $track_id
     * @return SummitTrackChair
     */
    public function addTrack2TrackChair(Summit $summit, int $track_chair_id, int $track_id): SummitTrackChair;

    /**
     * @param Summit $summit
     * @param int $track_chair_id
     * @param int $track_id
     * @return SummitTrackChair
     */
    public function removeFromTrackChair(Summit $summit, int $track_chair_id, int $track_id): SummitTrackChair;
}