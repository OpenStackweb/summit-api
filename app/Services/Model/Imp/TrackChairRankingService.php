<?php namespace App\Services\Model\Imp;
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

use App\Models\Foundation\Summit\Events\Presentations\TrackChairs\PresentationTrackChairRatingType;
use App\Models\Foundation\Summit\Events\Presentations\TrackChairs\PresentationTrackChairScoreType;
use App\Models\Foundation\Summit\Factories\PresentationTrackChairRatingTypeFactory;
use App\Models\Foundation\Summit\Factories\PresentationTrackChairScoreTypeFactory;
use App\Models\Foundation\Summit\SelectionPlan;
use App\Services\Model\AbstractService;
use App\Services\Model\ITrackChairRankingService;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;

/**
 * Class TrackChairRankingService
 * @package App\Services\Model\Imp
 */
final class TrackChairRankingService
    extends AbstractService
    implements ITrackChairRankingService
{

    /**
     * @inheritDoc
     */
    public function getTrackChairRatingType(SelectionPlan $selection_plan, int $track_chair_rating_type_id): PresentationTrackChairRatingType
    {
        return $this->tx_service->transaction(function () use ($selection_plan, $track_chair_rating_type_id)
        {
            $track_chair_rating_type = $selection_plan->getTrackChairRatingTypeById($track_chair_rating_type_id);
            if (is_null($track_chair_rating_type))
                throw new EntityNotFoundException("Track chair rating type not found.");

            return $track_chair_rating_type;
        });
    }

    /**
     * @inheritDoc
     */
    public function addTrackChairRatingType(SelectionPlan $selection_plan, array $payload): PresentationTrackChairRatingType
    {
        return $this->tx_service->transaction(function () use ($selection_plan, $payload)
        {
            $rating_type_name = trim($payload['name']);
            if(empty($rating_type_name)){
                throw new ValidationException("name cannot be empty.");
            }
            $track_chair_rating_type = $selection_plan->getTrackChairRatingTypeByName($rating_type_name);

            if (!is_null($track_chair_rating_type))
                throw new ValidationException("There is another rating type with the same name.");

            $track_chair_rating_type = PresentationTrackChairRatingTypeFactory::build($payload);

            $selection_plan->addTrackChairRatingType($track_chair_rating_type);

            return $track_chair_rating_type;
        });
    }

    /**
     * @inheritDoc
     */
    public function updateTrackChairRatingType
    (
        SelectionPlan $selection_plan,
        int $track_chair_rating_type_id,
        array $payload
    ): PresentationTrackChairRatingType
    {
        return $this->tx_service->transaction(function () use ($selection_plan, $track_chair_rating_type_id, $payload)
        {
            $track_chair_rating_type = $selection_plan->getTrackChairRatingTypeById($track_chair_rating_type_id);
            if (is_null($track_chair_rating_type))
                throw new EntityNotFoundException("Track chair rating type not found.");

            if(isset($payload['name'])) {
                $rating_type_name = trim($payload['name']);
                if(empty($rating_type_name)){
                    throw new ValidationException("name cannot be empty.");
                }
                $track_chair_rating_type_by_name = $selection_plan->getTrackChairRatingTypeByName($rating_type_name);

                if (!is_null($track_chair_rating_type_by_name) && $track_chair_rating_type_by_name->getId() != $track_chair_rating_type_id)
                    throw new ValidationException("There is another rating type with the same name.");
            }

            $track_chair_rating_type = PresentationTrackChairRatingTypeFactory::populate($track_chair_rating_type, $payload);

            if (isset($payload['order']) && intval($payload['order']) != $track_chair_rating_type->getOrder()) {
                $selection_plan->recalculateTrackChairRatingTypeOrder($track_chair_rating_type, intval($payload['order']));
            }
            return $track_chair_rating_type;
        });
    }

    /**
     * @inheritDoc
     */
    public function deleteTrackChairRatingType(SelectionPlan $selection_plan, int $track_chair_rating_type_id): void
    {
        $this->tx_service->transaction(function () use ($selection_plan, $track_chair_rating_type_id)
        {
            $track_chair_rating_type = $selection_plan->getTrackChairRatingTypeById($track_chair_rating_type_id);
            if (is_null($track_chair_rating_type))
                throw new EntityNotFoundException("Track chair rating type not found.");

            $selection_plan->removeTrackChairRatingType($track_chair_rating_type);
        });
    }

    /**
     * @inheritDoc
     */
    public function addTrackChairScoreType
    (
        SelectionPlan $selection_plan,
        int $track_chair_rating_type_id,
        array $payload
    ): PresentationTrackChairScoreType
    {
        return $this->tx_service->transaction(function () use ($selection_plan, $track_chair_rating_type_id,  $payload)
        {

            $track_chair_rating_type = $selection_plan->getTrackChairRatingTypeById($track_chair_rating_type_id);
            if (is_null($track_chair_rating_type))
                throw new EntityNotFoundException("Track chair rating type not found.");

            $score_type_name = trim($payload['name']);
            if(empty($score_type_name)){
                throw new ValidationException("name cannot be empty.");
            }
            $track_chair_score_type = $track_chair_rating_type->getScoreTypeByName($score_type_name);

            if (!is_null($track_chair_score_type))
                throw new ValidationException("There is another score type with the same name.");

            $track_chair_score_type = PresentationTrackChairScoreTypeFactory::build($payload);

            $track_chair_rating_type->addScoreType($track_chair_score_type);

            return $track_chair_score_type;
        });
    }

    /**
     * @inheritDoc
     */
    public function updateTrackChairScoreType
    (
        SelectionPlan $selection_plan,
        int $track_chair_rating_type_id,
        int $track_chair_score_type_id,
        array $payload
    ): PresentationTrackChairScoreType
    {
        return $this->tx_service->transaction(function () use ($selection_plan, $track_chair_rating_type_id, $track_chair_score_type_id, $payload)
        {

            $track_chair_rating_type = $selection_plan->getTrackChairRatingTypeById($track_chair_rating_type_id);
            if (is_null($track_chair_rating_type))
                throw new EntityNotFoundException("Track chair rating type not found.");

            $track_chair_score_type = $track_chair_rating_type->getScoreTypeById($track_chair_score_type_id);

            if (is_null($track_chair_score_type))
                throw new EntityNotFoundException("Track chair score type not found.");

            if(isset($payload['name'])) {
                $score_type_name = trim($payload['name']);
                if(empty($score_type_name)){
                    throw new ValidationException("name cannot be empty.");
                }
                $track_chair_score_type_by_name = $track_chair_rating_type->getScoreTypeByName($score_type_name);

                if (!is_null($track_chair_score_type_by_name) && $track_chair_score_type_by_name->getId() != $track_chair_score_type_id)
                    throw new ValidationException("There is another score type with the same name.");
            }

            $track_chair_score_type = PresentationTrackChairScoreTypeFactory::populate($track_chair_score_type, $payload);

            if (isset($payload['score']) && intval($payload['score']) != $track_chair_score_type->getScore()) {
                $track_chair_rating_type->recalculateScoreTypeScore($track_chair_score_type, intval($payload['score']));
            }
            return $track_chair_score_type;
        });
    }

    /**
     * @inheritDoc
     */
    public function deleteTrackChairScoreType(
        SelectionPlan $selection_plan, int $track_chair_rating_type_id, int $track_chair_score_type_id): void
    {
        $this->tx_service->transaction(function () use ($selection_plan, $track_chair_rating_type_id, $track_chair_score_type_id)
        {

            $track_chair_rating_type = $selection_plan->getTrackChairRatingTypeById($track_chair_rating_type_id);
            if (is_null($track_chair_rating_type))
                throw new EntityNotFoundException("Track chair rating type not found.");

            $track_chair_score_type = $track_chair_rating_type->getScoreTypeById($track_chair_score_type_id);

            if (is_null($track_chair_score_type))
                throw new EntityNotFoundException("Track chair score type not found.");

            $track_chair_rating_type->removeScoreType($track_chair_score_type);
        });
    }
}