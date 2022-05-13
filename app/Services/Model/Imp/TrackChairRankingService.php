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

use App\Models\Exceptions\AuthzException;
use App\Models\Foundation\Summit\Events\Presentations\TrackChairs\PresentationTrackChairRatingType;
use App\Models\Foundation\Summit\Events\Presentations\TrackChairs\PresentationTrackChairScoreType;
use App\Models\Foundation\Summit\SelectionPlan;
use App\Services\Model\AbstractService;
use App\Services\Model\ITrackChairRankingService;
use libs\utils\ITransactionService;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use models\main\Member;
use models\oauth2\IResourceServerContext;
use models\summit\Summit;
use models\summit\SummitTrackChair;

/**
 * Class TrackChairRankingService
 * @package App\Services\Model\Imp
 */
final class TrackChairRankingService
    extends AbstractService
    implements ITrackChairRankingService
{
    /**
     * @var IResourceServerContext
     */
    private $resource_server_ctx;

    /**
     * TrackChairRankingService constructor.
     * @param IResourceServerContext $resource_server_ctx
     * @param ITransactionService $tx_service
     */
    public function __construct
    (
        IResourceServerContext $resource_server_ctx,
        ITransactionService $tx_service
    )
    {
        parent::__construct($tx_service);
        $this->resource_server_ctx = $resource_server_ctx;
    }

    private function checkPermissions(Summit $summit, ?Member $member): void
    {
        if (is_null($member))
            throw new AuthzException("User not Found.");

        $isAuth = $summit->isTrackChairAdmin($member);

        if (!$isAuth)
            throw new AuthzException(sprintf("User %s is not authorized to perform this action.", $member->getId()));
    }

    /**
     * @inheritDoc
     */
    public function getTrackChairRatingType(SelectionPlan $selection_plan, int $track_chair_rating_type_id): PresentationTrackChairRatingType
    {
        return $this->tx_service->transaction(function () use ($selection_plan, $track_chair_rating_type_id)
        {
            $current_member = $this->resource_server_ctx->getCurrentUser();
            $this->checkPermissions($selection_plan->getSummit(), $current_member);

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
            $current_member = $this->resource_server_ctx->getCurrentUser();
            $this->checkPermissions($selection_plan->getSummit(), $current_member);

            $track_chair_rating_type = new PresentationTrackChairRatingType();
            $track_chair_rating_type->setWeight(floatval($payload['weight']));
            $track_chair_rating_type->setName($payload['name']);
            $track_chair_rating_type->setOrder(intval($payload['order']));

            $selection_plan->addTrackChairRatingType($track_chair_rating_type);

            return $track_chair_rating_type;
        });
    }

    /**
     * @inheritDoc
     */
    public function updateTrackChairRatingType(
        SelectionPlan $selection_plan, int $track_chair_rating_type_id, array $payload): PresentationTrackChairRatingType
    {
        return $this->tx_service->transaction(function () use ($selection_plan, $track_chair_rating_type_id, $payload)
        {
            $current_member = $this->resource_server_ctx->getCurrentUser();
            $this->checkPermissions($selection_plan->getSummit(), $current_member);

            $track_chair_rating_type = $selection_plan->getTrackChairRatingTypeById($track_chair_rating_type_id);
            if (is_null($track_chair_rating_type))
                throw new EntityNotFoundException("Track chair rating type not found.");

            $track_chair_rating_type->setWeight(floatval($payload['weight']));
            $track_chair_rating_type->setName($payload['name']);
            $track_chair_rating_type->setOrder(intval($payload['order']));

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
            $current_member = $this->resource_server_ctx->getCurrentUser();
            $this->checkPermissions($selection_plan->getSummit(), $current_member);

            $track_chair_rating_type = $selection_plan->getTrackChairRatingTypeById($track_chair_rating_type_id);
            if (is_null($track_chair_rating_type))
                throw new EntityNotFoundException("Track chair rating type not found.");

            $selection_plan->removeTrackChairRatingType($track_chair_rating_type);
        });
    }

    /**
     * @inheritDoc
     */
    public function addTrackChairScoreType(
        SelectionPlan $selection_plan, int $track_chair_rating_type_id, array $payload): PresentationTrackChairScoreType
    {
        return $this->tx_service->transaction(function () use ($selection_plan, $track_chair_rating_type_id,  $payload)
        {
            $current_member = $this->resource_server_ctx->getCurrentUser();
            $this->checkPermissions($selection_plan->getSummit(), $current_member);

            $track_chair_rating_type = $selection_plan->getTrackChairRatingTypeById($track_chair_rating_type_id);
            if (is_null($track_chair_rating_type))
                throw new EntityNotFoundException("Track chair rating type not found.");

            $track_chair_score_type = new PresentationTrackChairScoreType();
            $track_chair_score_type->setScore(intval($payload['score']));
            $track_chair_score_type->setName($payload['name']);
            $track_chair_score_type->setDescription($payload['description']);

            $track_chair_rating_type->addScoreType($track_chair_score_type);

            return $track_chair_score_type;
        });
    }

    /**
     * @inheritDoc
     */
    public function updateTrackChairScoreType(
        SelectionPlan $selection_plan, int $track_chair_rating_type_id, int $track_chair_score_type_id, array $payload): PresentationTrackChairScoreType
    {
        return $this->tx_service->transaction(function () use ($selection_plan, $track_chair_rating_type_id, $track_chair_score_type_id, $payload)
        {
            $current_member = $this->resource_server_ctx->getCurrentUser();
            $this->checkPermissions($selection_plan->getSummit(), $current_member);

            $track_chair_rating_type = $selection_plan->getTrackChairRatingTypeById($track_chair_rating_type_id);
            if (is_null($track_chair_rating_type))
                throw new EntityNotFoundException("Track chair rating type not found.");

            $track_chair_score_type = $track_chair_rating_type->getScoreTypeById($track_chair_score_type_id);

            if (is_null($track_chair_score_type))
                throw new EntityNotFoundException("Track chair score type not found.");

            $track_chair_score_type->setScore(intval($payload['score']));
            $track_chair_score_type->setName($payload['name']);
            $track_chair_score_type->setDescription($payload['description']);

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
            $current_member = $this->resource_server_ctx->getCurrentUser();
            $this->checkPermissions($selection_plan->getSummit(), $current_member);

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