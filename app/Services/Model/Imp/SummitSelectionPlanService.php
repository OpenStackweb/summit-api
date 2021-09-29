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

use App\Jobs\Emails\PresentationSelections\PresentationCategoryChangeRequestCreatedEmail;
use App\Jobs\Emails\PresentationSelections\PresentationCategoryChangeRequestResolvedEmail;
use App\Jobs\Emails\PresentationSubmissions\SelectionProcess\PresentationNotificationToModeratorEMail;
use App\Jobs\Emails\PresentationSubmissions\SelectionProcess\PresentationNotificationToSpeakerEMail;
use App\Jobs\Emails\PresentationSubmissions\SelectionProcess\PresentationNotificationToSubmitterEMail;
use App\Jobs\SendPresentationNotificationsBySelectionPlan;
use App\Models\Exceptions\AuthzException;
use App\Models\Foundation\Summit\Factories\SummitSelectionPlanFactory;
use App\Models\Foundation\Summit\SelectionPlan;
use Illuminate\Support\Facades\Log;
use libs\utils\ITransactionService;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use models\oauth2\IResourceServerContext;
use models\summit\ISummitRepository;
use models\summit\Presentation;
use models\summit\Summit;
use models\summit\SummitCategoryChange;
use models\summit\SummitPresentationComment;
use services\model\IPresentationService;

/**
 * Class SummitSelectionPlanService
 * @package App\Services\Model
 */
final class SummitSelectionPlanService
    extends AbstractService
    implements ISummitSelectionPlanService
{
    /**
     * @var ISummitRepository
     */
    private $summit_repository;

    /**
     * @var IResourceServerContext
     */
    private $resource_server_ctx;

    /**
     * @var IPresentationService
     */
    private $presentation_service;

    /**
     * SummitSelectionPlanService constructor.
     * @param ISummitRepository $summit_repository
     * @param IResourceServerContext $resource_server_ctx
     * @param IPresentationService $presentation_service
     * @param ITransactionService $tx_service
     */
    public function __construct
    (
        ISummitRepository $summit_repository,
        IResourceServerContext $resource_server_ctx,
        IPresentationService $presentation_service,
        ITransactionService $tx_service
    )
    {
        $this->summit_repository = $summit_repository;
        parent::__construct($tx_service);
        $this->presentation_service = $presentation_service;
        $this->resource_server_ctx = $resource_server_ctx;
    }

    /**
     * @param Summit $summit
     * @param array $payload
     * @return SelectionPlan
     * @throws ValidationException
     */
    public function addSelectionPlan(Summit $summit, array $payload)
    {
        return $this->tx_service->transaction(function () use ($summit, $payload) {

            $selection_plan = SummitSelectionPlanFactory::build($payload, $summit);

            $former_selection_plan = $summit->getSelectionPlanByName($selection_plan->getName());

            if (!is_null($former_selection_plan)) {
                throw new ValidationException(trans(
                    'validation_errors.SummitSelectionPlanService.addSelectionPlan.alreadyExistName',
                    [
                        'summit_id' => $summit->getId()
                    ]
                ));
            }

            // validate selection plan

            $summit->checkSelectionPlanConflicts($selection_plan);

            $summit->addSelectionPlan($selection_plan);

            return $selection_plan;
        });
    }

    /**
     * @param Summit $summit
     * @param int $selection_plan_id
     * @param array $payload
     * @return SelectionPlan
     * @throws ValidationException
     * @throws EntityNotFoundException
     */
    public function updateSelectionPlan(Summit $summit, $selection_plan_id, array $payload)
    {
        return $this->tx_service->transaction(function () use ($summit, $selection_plan_id, $payload) {

            $selection_plan = $summit->getSelectionPlanById($selection_plan_id);
            if (is_null($selection_plan))
                throw new EntityNotFoundException(trans
                ('not_found_errors.SummitSelectionPlanService.updateSelectionPlan.SelectionPlanNotFound',
                    [
                        'selection_plan_id' => $selection_plan_id,
                        'summit_id' => $summit->getId()
                    ]
                ));

            if (isset($payload['name'])) {
                $former_selection_plan = $summit->getSelectionPlanByName($payload['name']);
                if (!is_null($former_selection_plan) && $former_selection_plan->getId() != $selection_plan_id) {
                    throw new ValidationException(trans(
                        'validation_errors.SummitSelectionPlanService.updateSelectionPlan.alreadyExistName',
                        [
                            'summit_id' => $summit->getId()
                        ]
                    ));
                }
            }

            SummitSelectionPlanFactory::populate($selection_plan, $payload, $summit);
            // check conflict on current summits ( selections plans can not conflict inside summit)
            $summit->checkSelectionPlanConflicts($selection_plan);

            return $selection_plan;
        });
    }

    /**
     * @param Summit $summit
     * @param int $selection_plan_id
     * @return void
     * @throws EntityNotFoundException
     */
    public function deleteSelectionPlan(Summit $summit, $selection_plan_id)
    {
        return $this->tx_service->transaction(function () use ($summit, $selection_plan_id) {

            $selection_plan = $summit->getSelectionPlanById($selection_plan_id);
            if (is_null($selection_plan))
                throw new EntityNotFoundException(trans
                ('not_found_errors.SummitSelectionPlanService.deleteSelectionPlan.SelectionPlanNotFound',
                    [
                        'selection_plan_id' => $selection_plan_id,
                        'summit_id' => $summit->getId()
                    ]
                ));

            $summit->removeSelectionSelectionPlan($selection_plan);
        });
    }

    /**
     * @param Summit $summit
     * @param int $selection_plan_id
     * @param int $track_group_id
     * @return void
     * @throws ValidationException
     * @throws EntityNotFoundException
     */
    public function addTrackGroupToSelectionPlan(Summit $summit, $selection_plan_id, $track_group_id)
    {
        return $this->tx_service->transaction(function () use ($summit, $selection_plan_id, $track_group_id) {

            $selection_plan = $summit->getSelectionPlanById($selection_plan_id);
            if (is_null($selection_plan))
                throw new EntityNotFoundException(trans
                ('not_found_errors.SummitSelectionPlanService.addTrackGroupToSelectionPlan.SelectionPlanNotFound',
                    [
                        'selection_plan_id' => $selection_plan_id,
                        'summit_id' => $summit->getId()
                    ]
                ));

            $track_group = $summit->getCategoryGroupById($track_group_id);
            if (is_null($track_group))
                throw new EntityNotFoundException(trans
                ('not_found_errors.SummitSelectionPlanService.addTrackGroupToSelectionPlan.TrackGroupNotFound',
                    [
                        'track_group_id' => $track_group_id,
                        'summit_id' => $summit->getId()
                    ]
                ));
            $selection_plan->addTrackGroup($track_group);
        });
    }

    /**
     * @param Summit $summit
     * @param int $selection_plan_id
     * @param int $track_group_id
     * @return void
     * @throws ValidationException
     * @throws EntityNotFoundException
     */
    public function deleteTrackGroupToSelectionPlan(Summit $summit, $selection_plan_id, $track_group_id)
    {
        return $this->tx_service->transaction(function () use ($summit, $selection_plan_id, $track_group_id) {

            $selection_plan = $summit->getSelectionPlanById($selection_plan_id);
            if (is_null($selection_plan))
                throw new EntityNotFoundException(trans
                ('not_found_errors.SummitSelectionPlanService.deleteTrackGroupToSelectionPlan.SelectionPlanNotFound',
                    [
                        'selection_plan_id' => $selection_plan_id,
                        'summit_id' => $summit->getId()
                    ]
                ));

            $track_group = $summit->getCategoryGroupById($track_group_id);
            if (is_null($track_group))
                throw new EntityNotFoundException(trans
                ('not_found_errors.SummitSelectionPlanService.deleteTrackGroupToSelectionPlan.TrackGroupNotFound',
                    [
                        'track_group_id' => $track_group_id,
                        'summit_id' => $summit->getId()
                    ]
                ));
            $selection_plan->removeTrackGroup($track_group);
        });
    }

    /**
     * @param Summit $summit
     * @param string $status
     * @return SelectionPlan|null
     * @throws \Exception
     */
    public function getCurrentSelectionPlanByStatus(Summit $summit, $status)
    {
        return $this->tx_service->transaction(function () use ($summit, $status) {
            $selection_plan = $summit->getCurrentSelectionPlanByStatus($status);
            if (is_null($selection_plan))
                throw new EntityNotFoundException("Selection Plan not found.");
            if (!$selection_plan->IsEnabled())
                throw new EntityNotFoundException("Selection Plan not found.");
            return $selection_plan;
        });
    }

    /**
     * @param Summit $summit
     * @param int $selection_plan_id
     * @param int $presentation_id
     * @return Presentation
     * @throws \Exception
     */
    public function markPresentationAsViewed(Summit $summit, int $selection_plan_id, int $presentation_id): Presentation
    {
        return $this->tx_service->transaction(function() use($summit, $selection_plan_id, $presentation_id){

            $current_member = $this->resource_server_ctx->getCurrentUser();
            if(is_null($current_member))
                throw new AuthzException("User not Found");

            $selection_plan = $summit->getSelectionPlanById(intval($selection_plan_id));
            if (is_null($selection_plan))
                throw new EntityNotFoundException("Selection Plan not found.");

            if(!$selection_plan->isSelectionOpen())
                throw new ValidationException(sprintf("Selection period is not open for selection plan %s", $selection_plan->getId()));

            $presentation = $selection_plan->getPresentation(intval($presentation_id));

            if(is_null($presentation))
                throw new EntityNotFoundException("Presentation not found.");

            $category = $presentation->getCategory();

            $summit = $presentation->getSummit();

            $isAuth = $summit->isTrackChairAdmin($current_member) || $summit->isTrackChair($current_member, $category);

            if(!$isAuth)
                throw new ValidationException(sprintf("Presentation %s has changed to track %s", $presentation->getTitle(), $category->getTitle()));

            $presentation->addTrackChairView($current_member);

            return $presentation;
        });
    }

    /**
     * @inheritDoc
     */
    public function addPresentationComment
    (
        Summit $summit,
        int $selection_plan_id,
        int $presentation_id,
        array $payload
    ): SummitPresentationComment
    {
        return $this->tx_service->transaction(function() use($summit, $selection_plan_id, $presentation_id, $payload){

            $current_member = $this->resource_server_ctx->getCurrentUser();

            if(is_null($current_member))
                throw new AuthzException("User not Found");

            $selection_plan = $summit->getSelectionPlanById(intval($selection_plan_id));
            if (is_null($selection_plan))
                throw new EntityNotFoundException("Selection Plan not found.");

            if(!$selection_plan->isSelectionOpen())
                throw new ValidationException(sprintf("Selection period is not open for selection plan %s", $selection_plan->getId()));

            $presentation = $selection_plan->getPresentation(intval($presentation_id));

            if(is_null($presentation))
                throw new EntityNotFoundException("Presentation not found.");

            $category = $presentation->getCategory();

            $summit = $presentation->getSummit();

            $isAuth = $summit->isTrackChairAdmin($current_member) || $summit->isTrackChair($current_member, $category);

            if(!$isAuth)
                throw new ValidationException(sprintf("Presentation %s has changed to track %s", $presentation->getTitle(), $category->getTitle()));

            return $presentation->addTrackChairComment($current_member, trim($payload['body']), boolval($payload['is_public']));
        });
    }


    /**
     * @inheritDoc
     */
    public function createPresentationCategoryChangeRequest(Summit $summit, int $selection_plan_id, int $presentation_id, int $new_category_id): ?SummitCategoryChange
    {
        return $this->tx_service->transaction(function() use($summit, $selection_plan_id, $presentation_id, $new_category_id){

            $current_member = $this->resource_server_ctx->getCurrentUser();
            if(is_null($current_member))
                throw new AuthzException("User not Found");

            $selection_plan = $summit->getSelectionPlanById(intval($selection_plan_id));
            if (is_null($selection_plan))
                throw new EntityNotFoundException("Selection Plan not found.");

            if(!$selection_plan->isSelectionOpen())
                throw new ValidationException(sprintf("Selection period is not open for selection plan %s", $selection_plan->getId()));

            $presentation = $selection_plan->getPresentation(intval($presentation_id));

            if(is_null($presentation))
                throw new EntityNotFoundException("Presentation not found.");

            $category = $presentation->getCategory();

            $summit = $presentation->getSummit();

            $new_category = $summit->getPresentationCategory($new_category_id);

            if(is_null($new_category) || !$new_category->isChairVisible())
                throw new EntityNotFoundException("New Category not found.");

            $isAuth = $summit->isTrackChairAdmin($current_member) || $summit->isTrackChair($current_member, $category);

            if(!$isAuth)
                throw new AuthzException(sprintf("User %s is not authorized to perform this action.", $current_member->getId()));

            $change_request = $presentation->addCategoryChangeRequest($current_member,  $new_category);

            $presentation->addTrackChairNotification
            (
                $current_member,
                sprintf
                (
                    "%s submitted a request to change the category from %s to %s",
                    $current_member->getFullName(),
                    $category->getTitle(),
                    $new_category->getTitle()
                )
            );

            PresentationCategoryChangeRequestCreatedEmail::dispatch($change_request);

            return $change_request;
        });

    }

    /**
     * @inheritDoc
     */
    public function resolvePresentationCategoryChangeRequest(Summit $summit, int $selection_plan_id, int $presentation_id, int $category_change_request_id, array $payload): ?SummitCategoryChange
    {
        return $this->tx_service->transaction(function() use($summit, $selection_plan_id, $presentation_id, $category_change_request_id, $payload){

            $current_member = $this->resource_server_ctx->getCurrentUser();
            if(is_null($current_member))
                throw new AuthzException("User not Found");

            $selection_plan = $summit->getSelectionPlanById(intval($selection_plan_id));
            if (is_null($selection_plan))
                throw new EntityNotFoundException("Selection Plan not found.");

            if(!$selection_plan->isSelectionOpen())
                throw new ValidationException(sprintf("Selection period is not open for selection plan %s", $selection_plan->getId()));

            $presentation = $selection_plan->getPresentation(intval($presentation_id));

            if(is_null($presentation))
                throw new EntityNotFoundException("Presentation not found.");

            $category = $presentation->getCategory();

            $summit = $presentation->getSummit();

            $change_request = $presentation->getCategoryChangeRequest($category_change_request_id);

            if(is_null($change_request))
                throw new EntityNotFoundException("Category Change Request not found.");

            if(!$change_request->isPending()){
                throw new ValidationException("Change request has already been  approved/rejected.");
            }

            $newCategory = $change_request->getNewCategory();

            $isAuth = $summit->isTrackChairAdmin($current_member) || $summit->isTrackChair($current_member, $newCategory);

            if(!$isAuth)
                throw new AuthzException(sprintf("User %s is not authorized to perform this action.", $current_member->getId()));

            if ($presentation->isSelectedByAnyone()) {
                throw new ValidationException("The presentation has already been selected by chairs.");
            }

            if ($presentation->isGroupSelected()) {
                throw new ValidationException("The presentation is on the Team List.");
            }

            if ($category->getId() == $newCategory->getId()) {
                throw new ValidationException("The presentation is already in this category.");
            }

            $approved = (boolean) $payload['approved'];
            $reason =  $payload['reason'] ?? 'No reason.';

            if($approved){
                $change_request->approve($current_member, $reason);
                $presentation->clearViews();;
                $presentation->setCategory($newCategory);
                $presentation->addTrackChairNotification
                (
                    $current_member,
                    sprintf
                    (
                        "{member} approved %s's request to move this presentation from %s to %s",
                        $change_request->getRequester()->getFullName(),
                        $category->getTitle(),
                        $newCategory->getTitle()
                    )
                );

                foreach($presentation->getPendingCategoryChangeRequests() as $pending_request){
                    if($pending_request->getId() == $change_request->getId()) continue;
                    $pending_request->reject($current_member, sprintf( "Request ID %s was approved instead.", $change_request->getId()));
                }
            }

            else{
                $change_request->reject($current_member, $reason);
                $presentation->addTrackChairNotification(
                    $current_member,
                    sprintf(
                        "{member} rejected %s's request to move this presentation from %s to %s because : %s" ,
                        $change_request->getRequester()->getFullName(),
                        $category->getTitle(),
                        $newCategory->getTitle(),
                        $reason
                    )
                );
            }

            PresentationCategoryChangeRequestResolvedEmail::dispatch($change_request);

            return $change_request;

        });
    }

    /**
     * @param Summit $summit
     * @param int $selection_plan_id
     * @param array $payload
     * @throws \Exception
     */
    public function sendPresentationNotifications(Summit $summit, int $selection_plan_id, array $payload): void
    {
        $this->tx_service->transaction(function() use($summit, $selection_plan_id, $payload){

            $selection_plan = $summit->getSelectionPlanById($selection_plan_id);

            if(is_null($selection_plan)){
                throw new EntityNotFoundException("Selection Plan not found.");
            }

            $templates = [
                PresentationNotificationToSubmitterEMail::EVENT_SLUG => $payload['submitter_notification_template'],
                PresentationNotificationToSpeakerEMail::EVENT_SLUG => $payload['speaker_notification_template'],
                PresentationNotificationToModeratorEMail::EVENT_SLUG => $payload['moderator_notification_template']
            ];

            // updates event with new templates templates
            foreach ($templates as $slug => $template_id) {
                Log::debug(sprintf("SummitSelectionPlanService::sendPresentationNotifications trying to retrieve email event by type %s", $slug));
                $event = $summit->getEmailEventFlowByTypeSlug($slug);
                if (is_null($event))
                    throw new EntityNotFoundException("Email Event not found");
                $event->setEmailTemplateIdentifier($template_id);
                Log::debug(sprintf("SummitSelectionPlanService::sendPresentationNotifications updated email event %s with template %s", $event->getId(), $template_id));
            }
        });

        SendPresentationNotificationsBySelectionPlan::dispatch($summit, $selection_plan_id, $payload['dry_run']);

    }

    /**
     * @param int $summit_id
     * @param int $selection_plan_id
     * @param bool $dry_run
     * @throws \Exception
     */
    public function processPresentationNotifications(int $summit_id, int $selection_plan_id, bool $dry_run):void {

        Log::debug
        (
            sprintf
            (
                "SummitSelectionPlanService::processPresentationNotifications summit id %s selection plan id %s dry run %b",
                $summit_id,
                $selection_plan_id,
                $dry_run
            )
        );

        $this->tx_service->transaction(function() use($summit_id, $selection_plan_id, $dry_run){

            $summit = $this->summit_repository->getById($summit_id);
            if(is_null($summit) || !$summit instanceof Summit)
                throw new EntityNotFoundException("Summit not found.");

            $selection_plan = $summit->getSelectionPlanById($selection_plan_id);

            if(is_null($selection_plan) || !$selection_plan instanceof SelectionPlan){
                throw new EntityNotFoundException("Selection Plan not found.");
            }

            foreach ($selection_plan->getPresentations() as $presentation){
                try {
                    Log::debug(sprintf("SummitSelectionPlanService::processPresentationNotifications processing presentation %s", $presentation->getId()));
                    $this->presentation_service->processPresentationNotification($presentation->getId(), $dry_run);
                }
                catch (\Exception $ex){
                    Log::error($ex);
                }
            }
        });
    }
}