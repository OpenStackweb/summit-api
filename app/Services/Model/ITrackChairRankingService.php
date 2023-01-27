<?php namespace App\Services\Model;
/**
 * Copyright 2022 OpenStack Foundation
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
use App\Models\Foundation\Summit\Events\Presentations\TrackChairs\PresentationTrackChairRatingType;
use App\Models\Foundation\Summit\Events\Presentations\TrackChairs\PresentationTrackChairScoreType;
use App\Models\Foundation\Summit\SelectionPlan;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;

/**
 * Interface ITrackChairRankingService
 * @package App\Services\Model
 */
interface ITrackChairRankingService
{
    /**
     * @param SelectionPlan $selection_plan
     * @param int $track_chair_rating_type_id
     * @return PresentationTrackChairRatingType
     * @throws EntityNotFoundException
     * @throws AuthzException
     */
    public function getTrackChairRatingType(SelectionPlan $selection_plan, int $track_chair_rating_type_id): PresentationTrackChairRatingType;

    /**
     * @param SelectionPlan $selection_plan
     * @param array $payload
     * @return PresentationTrackChairRatingType
     * @throws EntityNotFoundException
     * @throws ValidationException
     * @throws AuthzException
     */
    public function addTrackChairRatingType(SelectionPlan $selection_plan, array $payload): PresentationTrackChairRatingType;

    /**
     * @param SelectionPlan $selection_plan
     * @param int $track_chair_rating_type_id
     * @param array $payload
     * @return PresentationTrackChairRatingType
     * @throws EntityNotFoundException
     * @throws ValidationException
     * @throws AuthzException
     */
    public function updateTrackChairRatingType(
        SelectionPlan $selection_plan, int $track_chair_rating_type_id, array $payload): PresentationTrackChairRatingType;

    /**
     * @param SelectionPlan $selection_plan
     * @param int $track_chair_rating_type_id
     * @throws EntityNotFoundException
     * @throws ValidationException
     * @throws AuthzException
     */
    public function deleteTrackChairRatingType(SelectionPlan $selection_plan, int $track_chair_rating_type_id): void;

    /**
     * @param SelectionPlan $selection_plan
     * @param int $track_chair_rating_type_id
     * @param array $payload
     * @return PresentationTrackChairScoreType
     * @throws EntityNotFoundException
     * @throws ValidationException
     * @throws AuthzException
     * @throws \Exception
     */
    public function addTrackChairScoreType(
        SelectionPlan $selection_plan, int $track_chair_rating_type_id, array $payload): PresentationTrackChairScoreType;

    /**
     * @param SelectionPlan $selection_plan
     * @param int $track_chair_rating_type_id
     * @param int $track_chair_score_type_id
     * @param array $payload
     * @return PresentationTrackChairScoreType
     * @throws EntityNotFoundException
     * @throws ValidationException
     * @throws AuthzException
     */
    public function updateTrackChairScoreType(
        SelectionPlan $selection_plan, int $track_chair_rating_type_id, int $track_chair_score_type_id, array $payload): PresentationTrackChairScoreType;

    /**
     * @param SelectionPlan $selection_plan
     * @param int $track_chair_rating_type_id
     * @param int $track_chair_score_type_id
     * @throws EntityNotFoundException
     * @throws ValidationException
     * @throws AuthzException
     */
    public function deleteTrackChairScoreType(
        SelectionPlan $selection_plan, int $track_chair_rating_type_id, int $track_chair_score_type_id): void;
}