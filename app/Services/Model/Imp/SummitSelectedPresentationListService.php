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
use App\Models\Foundation\Summit\SelectionPlan;
use App\Services\Model\AbstractService;
use App\Services\Model\ISummitSelectedPresentationListService;
use Illuminate\Support\Facades\Log;
use models\summit\PresentationCategory;
use libs\utils\ITransactionService;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use models\main\IMemberRepository;
use models\oauth2\IResourceServerContext;
use models\summit\ISummitRepository;
use models\summit\Presentation;
use models\summit\Summit;
use models\summit\SummitSelectedPresentation;
use models\summit\SummitSelectedPresentationList;
/**
 * Class SummitSelectedPresentationListService
 * @package App\Services\Model\Imp
 */
final class SummitSelectedPresentationListService
    extends AbstractService
    implements ISummitSelectedPresentationListService
{

    /**
     * @var ISummitRepository
     */
    private $summit_repository;

    /**
     * @var IMemberRepository
     */
    private $member_repository;

    /**
     * @var IResourceServerContext
     */
    private $resource_server_ctx;

    /**
     * SummitSelectedPresentationListService constructor.
     * @param IMemberRepository $member_repository
     * @param ISummitRepository $summit_repository
     * @param IResourceServerContext $resource_server_ctx
     * @param ITransactionService $tx_service
     */
    public function __construct
    (
        IMemberRepository $member_repository,
        ISummitRepository $summit_repository,
        IResourceServerContext $resource_server_ctx,
        ITransactionService $tx_service
    )
    {
        $this->summit_repository = $summit_repository;
        $this->member_repository = $member_repository;
        $this->resource_server_ctx = $resource_server_ctx;
        parent::__construct($tx_service);
    }

    /**
     * @inheritDoc
     */
    public function getTeamSelectionList(Summit $summit, int $track_id): SummitSelectedPresentationList
    {
        return $this->tx_service->transaction(function () use ($summit, $track_id) {
            $category = $summit->getPresentationCategory(intval($track_id));
            if (is_null($category)) throw new EntityNotFoundException("track not found.");

            $current_member = $this->resource_server_ctx->getCurrentUser();

            if(is_null($current_member))
                throw new AuthzException("Current Member not found.");

            $auth = $summit->isTrackChair($current_member, $category);
            if(!$auth){
                throw new AuthzException("Current user is not allowed to perform this operation.");
            }

            $selection_list = $category->getSelectionListByTypeAndOwner(SummitSelectedPresentationList::Group);
            if (is_null($selection_list)) throw new EntityNotFoundException("list not found.");

            return $selection_list;
        });
    }

    /**
     * @inheritDoc
     */
    public function createTeamSelectionList(Summit $summit, int $track_id): SummitSelectedPresentationList
    {
        return $this->tx_service->transaction(function () use ($summit, $track_id) {

            $category = $summit->getPresentationCategory(intval($track_id));
            if (is_null($category)) throw new EntityNotFoundException("track not found.");

            $current_member = $this->resource_server_ctx->getCurrentUser();

            if(is_null($current_member))
                throw new AuthzException("Current Member not found.");

            $auth = $summit->isTrackChair($current_member, $category);
            if(!$auth){
                throw new AuthzException("Current user is not allowed to perform this operation.");
            }

            return $category->createTeamSelectionList();
        });
    }

    /**
     * @inheritDoc
     */
    public function getIndividualSelectionList(Summit $summit, int $track_id, int $owner_id): SummitSelectedPresentationList
    {
        return $this->tx_service->transaction(function () use ($summit, $track_id, $owner_id) {
            $category = $summit->getPresentationCategory(intval($track_id));
            if (is_null($category)) throw new EntityNotFoundException("track not found.");

            $current_member = $this->resource_server_ctx->getCurrentUser();

            if(is_null($current_member))
                throw new AuthzException("Current Member not found.");

            $auth = $summit->isTrackChair($current_member, $category);
            if(!$auth){
                throw new AuthzException("Current user is not allowed to perform this operation.");
            }

            $member = $this->member_repository->getById(intval($owner_id));
            if (is_null($member)) throw new EntityNotFoundException("member not found.");

            $selection_list = $category->getSelectionListByTypeAndOwner(SummitSelectedPresentationList::Individual, $member);
            if (is_null($selection_list)) throw new EntityNotFoundException("list not found.");

            return $selection_list;
        });
    }

    /**
     * @inheritDoc
     */
    public function createIndividualSelectionList(Summit $summit, int $track_id): SummitSelectedPresentationList
    {
        return $this->tx_service->transaction(function () use ($summit, $track_id) {

            $category = $summit->getPresentationCategory(intval($track_id));
            if (is_null($category)) throw new EntityNotFoundException("track not found.");

            $current_member = $this->resource_server_ctx->getCurrentUser();

            if(is_null($current_member))
                throw new AuthzException("Current Member not found.");

            $auth = $summit->isTrackChair($current_member, $category);
            if(!$auth){
                throw new AuthzException("Current user is not allowed to perform this operation.");
            }

            return $category->createIndividualSelectionList($current_member);
        });
    }

    /**
     * @inheritDoc
     */
    public function reorderList(Summit $summit, int $track_id, int $list_id, array $payload): SummitSelectedPresentationList
    {
        return $this->tx_service->transaction(function () use ($summit, $track_id, $list_id, $payload) {

            Log::debug(sprintf("SummitSelectedPresentationListService::reorderList track %s list %s payload %s.", $track_id, $list_id, json_encode($payload)));

            $selection_plan = $summit->getCurrentSelectionPlanByStatus(SelectionPlan::STATUS_SELECTION);

            if(is_null($selection_plan))
                throw new ValidationException(sprintf("There is not current Selection Plan active on Selection Phase."));

            $category = $summit->getPresentationCategory(intval($track_id));

            if (is_null($category) || !$category instanceof PresentationCategory || !$category->isChairVisible()) throw new EntityNotFoundException("Track not found.");

            $selection_list = $category->getSelectionListById($list_id);
            if (is_null($selection_list))
                throw new EntityNotFoundException("List not found.");

            $current_member = $this->resource_server_ctx->getCurrentUser();

            if(is_null($current_member))
                throw new AuthzException("Current Member not found.");

            $auth = $summit->isTrackChair($current_member, $category);
            if(!$auth){
                throw new AuthzException("Current user is not allowed to perform this operation.");
            }

            // check if we can edit it

            if(!$selection_list->canEdit($current_member)){
                throw new AuthzException(sprintf("Member %s can not edit list %s", $current_member->getId(), $selection_list->getId()));
            }

            if (count($payload['presentations']) > $category->getTrackChairAvailableSlots() && trim($payload['collection']) == SummitSelectedPresentation::CollectionSelected) {
                throw new ValidationException(sprintf("You can not add more presentations (%s).", $category->getTrackChairAvailableSlots()));
            }

            if ($selection_list->isGroup()){

                $hash = $payload['hash'] ?? "";

                if(!$selection_list->compareHash(trim($hash)))
                    throw new ValidationException("The Teams List was modified by someone else. Please refresh the page.");
            }

            /**
             * Remove selections that are not on the provided new list
             * ex
             * current selection [1,3]
             * provided selection [3,4,5]
             * final state [3]
             */
            foreach($selection_list->getSelectedPresentationsByCollection(trim($payload['collection'])) as $selection){
                if(!in_array($selection->getPresentationId(), $payload['presentations'])){
                    $selection_list->removeSelection($selection);
                }
            }

            foreach ($payload['presentations'] as $order => $id) {
                // get the presentation

                $presentation = $summit->getEvent(intval($id));

                if (is_null($presentation)
                    || !$presentation instanceof Presentation
                    || $presentation->getStatus() !== Presentation::STATUS_RECEIVED
                    || $presentation->getProgress() !== Presentation::PHASE_COMPLETE
                    || $presentation->getSelectionPlanId() !== $selection_plan->getId())
                    throw new EntityNotFoundException(sprintf("Presentation %s not found.", $id));

                if($category->getId() !== $presentation->getCategoryId()){
                    throw new ValidationException(sprintf("Current member can not assign Presentation %s to his/her list [Presentation does not belong to category].", $id));
                }

                // check if the selection already exists on the current list
                $selection = $selection_list->getSelectionByPresentation($presentation);

                if(!is_null($selection) && $selection->getCollection() !== trim($payload['collection'])){
                    // we should remove it from original collection
                    $selection_list->removeSelection($selection);
                    $selection = null;
                }

                if(is_null($selection)) {
                    // selection does not exists , create it
                    $selection = SummitSelectedPresentation::create
                    (
                        $selection_list,
                        $presentation,
                        trim($payload['collection']),
                        $selection_list->isGroup() ? null : $current_member
                     );

                    $selection_list->addSelection($selection);

                    if ($selection_list->isGroup()) {
                        $presentation->addTrackChairNotification($current_member, '{member} added this presentation to the team list');
                    }
                }

                $selection->setOrder($order + 1);
            }

            if($selection_list->isGroup())
                $selection_list->recalculateHash();

            return $selection_list;
        });
    }

    /**
     * @param Summit $summit
     * @param int $track_id
     * @param string $collection
     * @param int $presentation_id
     * @return SummitSelectedPresentationList
     * @throws \Exception
     */
    public function assignPresentationToMyIndividualList
    (
        Summit $summit,
        int $track_id,
        string $collection,
        int $presentation_id
    ): SummitSelectedPresentationList
    {
        return $this->tx_service->transaction(function () use ($summit, $track_id, $collection, $presentation_id) {

            Log::debug
            (
                sprintf
                (
                    "SummitSelectedPresentationListService::assignPresentationToMyIndividualList summit %s track %s collection %s presentation %s",
                    $summit->getId(),
                    $track_id,
                    $collection,
                    $presentation_id
                )
            );

            $current_member = $this->resource_server_ctx->getCurrentUser();
            if(is_null($current_member))
                throw new AuthzException("User is missing.");

            $selection_plan = $summit->getCurrentSelectionPlanByStatus(SelectionPlan::STATUS_SELECTION);

            if(is_null($selection_plan))
                throw new ValidationException(sprintf("There is not current Selection Plan active on Selection Phase."));

            $category = $summit->getPresentationCategory(intval($track_id));
            if (is_null($category) || !$category instanceof PresentationCategory || !$category->isChairVisible())
                throw new EntityNotFoundException("Track not found.");

            $authz = $summit->isTrackChair($current_member, $category);

            if(!$authz)
                throw new AuthzException("User is not authorized to perform this action");

            $selection_list = $category->getSelectionListByTypeAndOwner(SummitSelectedPresentationList::Individual, $current_member);
            if (is_null($selection_list))
                throw new EntityNotFoundException(sprintf("Individual List not found for member %s and category %s", $current_member->getId(), $category->getId()));

            if(!$selection_list->canEdit($current_member)){
                throw new ValidationException(sprintf("Member %s can not edit list %s", $current_member->getId(), $selection_list->getId()));
            }

            $presentation = $summit->getEvent($presentation_id);

            if (is_null($presentation)
                || !$presentation instanceof Presentation
                || $presentation->getStatus() !== Presentation::STATUS_RECEIVED
                || $presentation->getProgress() !== Presentation::PHASE_COMPLETE
                || $presentation->getSelectionPlanId() !== $selection_plan->getId())
                throw new EntityNotFoundException(sprintf("Presentation %s not found.", $presentation_id));

            if($category->getId() !== $presentation->getCategoryId()){
                throw new ValidationException(sprintf("Current member can not assign Presentation %s to his/her list [Presentation does not belong to category].", $presentation_id));
            }

            $available_slots = $category->getTrackChairAvailableSlots();
            $highest_order_in_list = $selection_list->getHighestOrderInListByCollection($collection);

            if($collection == SummitSelectedPresentation::CollectionSelected && $highest_order_in_list >= $available_slots) {
                // will not add this presentation, list is full
               throw new ValidationException(sprintf("Presentation Selection list is full. Currently at %s. Limit is %s.", $highest_order_in_list, $available_slots));
            }

            Log::debug
            (
                sprintf
                (
                "SummitSelectedPresentationListService::assignPresentationToMyIndividualList list %s available_slots %s highest_order_in_list %s",
                    $selection_list->getId(),
                    $available_slots,
                    $highest_order_in_list
                )
            );

            $selected_presentation = $selection_list->getSelectionByPresentation($presentation);

            if (is_null($selected_presentation)) {
                $selected_presentation = new SummitSelectedPresentation;
                $selected_presentation->setPresentation($presentation);
                $selected_presentation->setMember($current_member);
            }

            $former_collection = $selected_presentation->getCollection();
            $selected_presentation->setCollection($collection);
            $selected_presentation->setOrder($highest_order_in_list + 1);
            $selection_list->addSelection($selected_presentation);

            // reorder list from where it was removed
            if (!empty($former_collection) && $former_collection != SummitSelectedPresentation::CollectionPass) {
                $left_selections = $selection_list->getSelectedPresentationsByCollection($former_collection);
                foreach ($left_selections as $order => $selection) {
                    $selection->setOrder($order+1);
                }
            }

            return $selection_list;
        });
    }

    /**
     * @param Summit $summit
     * @param int $track_id
     * @param int $presentation_id
     * @return SummitSelectedPresentationList
     * @throws \Exception
     */
    public function removePresentationFromMyIndividualList(Summit $summit, int $track_id, int $presentation_id): SummitSelectedPresentationList
    {
        return $this->tx_service->transaction(function () use ($summit, $track_id, $presentation_id) {
            Log::debug
            (
                sprintf
                (
                    "SummitSelectedPresentationListService::removePresentationFromMyIndividualList summit %s track %s presentation %s",
                    $summit->getId(),
                    $track_id,
                    $presentation_id
                )
            );

            $selection_plan = $summit->getCurrentSelectionPlanByStatus(SelectionPlan::STATUS_SELECTION);

            if(is_null($selection_plan))
                throw new ValidationException(sprintf("There is not current Selection Plan active on Selection Phase."));

            $category = $summit->getPresentationCategory(intval($track_id));
            if (is_null($category) || !$category instanceof PresentationCategory) throw new EntityNotFoundException("Track not found.");

            $current_member = $this->resource_server_ctx->getCurrentUser();

            if(is_null($current_member))
                throw new AuthzException("Current Member not found.");

            $auth = $summit->isTrackChair($current_member, $category);
            if(!$auth){
                throw new AuthzException("Current user is not allowed to perform this operation.");
            }

            $selection_list = $category->getSelectionListByTypeAndOwner(SummitSelectedPresentationList::Individual, $current_member);
            if (is_null($selection_list))
                throw new EntityNotFoundException(sprintf("Individual List not found for member %s and category %s", $current_member->getId(), $category->getId()));

            if(!$selection_list->canEdit($current_member)){
                throw new ValidationException(sprintf("Member %s can not edit list %s", $current_member->getId(), $selection_list->getId()));
            }

            $presentation = $summit->getEvent($presentation_id);

            if (is_null($presentation) || !$presentation instanceof Presentation)
                throw new EntityNotFoundException(sprintf("Presentation %s not found.", $presentation_id));

            $selection = $selection_list->getSelectionByPresentation($presentation);
            if(is_null($selection))
                throw new EntityNotFoundException("Selection not found.");

            $selection_list->removeSelection($selection);

            return $selection_list;
        });
    }
}