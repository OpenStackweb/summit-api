<?php namespace App\Services\Model\Imp;
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
use App\Models\Foundation\Main\IGroup;
use App\Services\Model\AbstractService;
use App\Services\Model\ITrackChairService;
use libs\utils\ITransactionService;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use models\main\IGroupRepository;
use models\main\IMemberRepository;
use models\main\Member;
use models\oauth2\IResourceServerContext;
use models\summit\PresentationCategory;
use models\summit\Summit;
use models\summit\SummitTrackChair;

/**
 * Class TrackChairService
 * @package App\Services\Model\Imp
 */
final class TrackChairService
    extends AbstractService
    implements ITrackChairService
{

    /**
     * @var IMemberRepository
     */
    private $member_repository;

    /**
     * @var IGroupRepository
     */
    private $group_repository;

    /**
     * @var IResourceServerContext
     */
    private $resource_server_ctx;

    /**
     * TrackChairService constructor.
     * @param IMemberRepository $member_repository
     * @param IGroupRepository $group_repository
     * @param IResourceServerContext $resource_server_ctx
     * @param ITransactionService $tx_service
     */
    public function __construct
    (
        IMemberRepository $member_repository,
        IGroupRepository $group_repository,
        IResourceServerContext $resource_server_ctx,
        ITransactionService $tx_service
    )
    {
        parent::__construct($tx_service);
        $this->member_repository = $member_repository;
        $this->group_repository = $group_repository;
        $this->resource_server_ctx = $resource_server_ctx;
    }

    /**
     * @param Summit $summit
     * @param array $payload
     * @return SummitTrackChair
     * @throws \Exception
     */
    public function addTrackChair(Summit $summit, array $payload): SummitTrackChair
    {
        return $this->tx_service->transaction(function () use ($summit, $payload) {

            $current_member = $this->resource_server_ctx->getCurrentUser();
            if (is_null($current_member))
                throw new AuthzException("User not Found");

            $isAuth = $summit->isTrackChairAdmin($current_member);

            if (!$isAuth)
                throw new AuthzException(sprintf("User %s is not authorized to perform this action.", $current_member->getId()));

            $member = $this->member_repository->getById(intval($payload['member_id']));
            if (is_null($member) || !$member instanceof Member)
                throw new EntityNotFoundException(sprintf("Member %s not found.", $payload['member_id']));

            $former_track_chair = $summit->getTrackChairByMember($member);

            if (!is_null($former_track_chair)) {
                throw new ValidationException(sprintf("Member %s already is a track chair on summit %s", $payload['member_id'], $summit->getId()));
            }

            $group = $this->group_repository->getBySlug(IGroup::TrackChairs);
            if (is_null($group)) {
                throw new EntityNotFoundException(sprintf("Group %s not found.", IGroup::TrackChairs));
            }

            $member->add2Group($group);

            $categories = [];

            foreach ($payload['categories'] as $track_id) {
                $track = $summit->getPresentationCategory(intval($track_id));
                if (is_null($track) || !$track instanceof PresentationCategory || !$track->isChairVisible())
                    throw new EntityNotFoundException(sprintf("Presentation Category %s not found.", $track_id));
                $categories[] = $track;
            }

            $track_chair =  $summit->addTrackChair($member, $categories);

            return $track_chair;

        });
    }

    /**
     * @param Summit $summit
     * @param int $track_chair_id
     * @param array $payload
     * @return SummitTrackChair
     * @throws \Exception
     */
    public function updateTrackChair(Summit $summit, int $track_chair_id, array $payload): SummitTrackChair
    {
        return $this->tx_service->transaction(function () use ($summit, $track_chair_id, $payload) {
            $current_member = $this->resource_server_ctx->getCurrentUser();
            if (is_null($current_member))
                throw new AuthzException("User not Found");

            $isAuth = $summit->isTrackChairAdmin($current_member);

            if (!$isAuth)
                throw new AuthzException(sprintf("User %s is not authorized to perform this action.", $current_member->getId()));

            $track_chair = $summit->getTrackChair($track_chair_id);

            if(is_null($track_chair))
                throw new EntityNotFoundException(sprintf("Track Chair %s not found.", $track_chair_id));

            $categories_2_remove = [];

            foreach ($track_chair->getCategories() as $category) {
                if (!in_array($category->getId(), $payload['categories']))
                    $categories_2_remove[] = $category;
            }

            foreach ($payload['categories'] as $track_id) {
                $category = $summit->getPresentationCategory(intval($track_id));
                if (is_null($category) || !$category instanceof PresentationCategory || !$category->isChairVisible())
                    throw new EntityNotFoundException(sprintf("Presentation Category %s not found.", $track_id));

                $track_chair->addCategory($category);
            }

            foreach ($categories_2_remove as $category){
                $track_chair->removeCategory($category);
            }

            return $track_chair;
        });
    }

    /**
     * @param Summit $summit
     * @param int $track_chair_id
     * @throws \Exception
     */
    public function deleteTrackChair(Summit $summit, int $track_chair_id): void
    {
        $this->tx_service->transaction(function () use ($summit, $track_chair_id) {
            $current_member = $this->resource_server_ctx->getCurrentUser();
            if (is_null($current_member))
                throw new AuthzException("User not Found");

            $isAuth = $summit->isTrackChairAdmin($current_member);

            if (!$isAuth)
                throw new AuthzException(sprintf("User %s is not authorized to perform this action.", $current_member->getId()));

            $track_chair = $summit->getTrackChair($track_chair_id);

            if(is_null($track_chair))
                throw new EntityNotFoundException(sprintf("Track Chair %s not found.", $track_chair_id));

            foreach($track_chair->getCategories() as $category){
                $track_chair->removeCategory($category);
            }

            $summit->removeTrackChair($track_chair);
        });
    }

    /**
     * @inheritDoc
     */
    public function addTrack2TrackChair(Summit $summit, int $track_chair_id, int $track_id): SummitTrackChair
    {
        return $this->tx_service->transaction(function () use ($summit, $track_chair_id, $track_id) {
            $current_member = $this->resource_server_ctx->getCurrentUser();
            if (is_null($current_member))
                throw new AuthzException("User not Found");

            $isAuth = $summit->isTrackChairAdmin($current_member);

            if (!$isAuth)
                throw new AuthzException(sprintf("User %s is not authorized to perform this action.", $current_member->getId()));

            $track_chair = $summit->getTrackChair($track_chair_id);

            if(is_null($track_chair))
                throw new EntityNotFoundException(sprintf("Track Chair %s not found.", $track_chair_id));

            $track = $summit->getPresentationCategory($track_id);
            if(is_null($track))
                throw new EntityNotFoundException(sprintf("Track %s not found.", $track_id));

            $track_chair->addCategory($track);

            return $track_chair;
        });
    }

    /**
     * @inheritDoc
     */
    public function removeFromTrackChair(Summit $summit, int $track_chair_id, int $track_id): SummitTrackChair
    {
        return $this->tx_service->transaction(function () use ($summit, $track_chair_id, $track_id) {
            $current_member = $this->resource_server_ctx->getCurrentUser();
            if (is_null($current_member))
                throw new AuthzException("User not Found");

            $isAuth = $summit->isTrackChairAdmin($current_member);

            if (!$isAuth)
                throw new AuthzException(sprintf("User %s is not authorized to perform this action.", $current_member->getId()));

            $track_chair = $summit->getTrackChair($track_chair_id);

            if(is_null($track_chair))
                throw new EntityNotFoundException(sprintf("Track Chair %s not found.", $track_chair_id));

            $track = $track_chair->getCategory($track_id);
            if(is_null($track))
                throw new EntityNotFoundException(sprintf("Track %s not found.", $track_id));

            $track_chair->removeCategory($track);

            return $track_chair;
        });
    }
}