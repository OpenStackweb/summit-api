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
use App\Jobs\ProcessSelectionPlanAllowedMemberData;
use App\Models\Exceptions\AuthzException;
use App\Models\Foundation\Summit\Factories\SummitSelectionPlanFactory;
use App\Models\Foundation\Summit\SelectionPlan;
use App\Models\Foundation\Summit\SelectionPlanAllowedMember;
use App\Services\FileSystem\IFileDownloadStrategy;
use App\Services\FileSystem\IFileUploadStrategy;
use App\Services\Utils\CSVReader;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use libs\utils\ITransactionService;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use models\oauth2\IResourceServerContext;
use models\summit\ISummitRepository;
use models\summit\Presentation;
use models\summit\PresentationActionType;
use models\summit\PresentationType;
use models\summit\Summit;
use models\summit\SummitCategoryChange;
use models\summit\SummitPresentationComment;

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
     * @var IFileUploadStrategy
     */
    private $upload_strategy;

    /**
     * @var IFileDownloadStrategy
     */
    private $download_strategy;


    /**
     * @param ISummitRepository $summit_repository
     * @param IFileUploadStrategy $upload_strategy
     * @param IFileDownloadStrategy $download_strategy
     * @param IResourceServerContext $resource_server_ctx
     * @param ITransactionService $tx_service
     */
    public function __construct
    (
        ISummitRepository      $summit_repository,
        IFileUploadStrategy    $upload_strategy,
        IFileDownloadStrategy  $download_strategy,
        IResourceServerContext $resource_server_ctx,
        ITransactionService    $tx_service
    )
    {
        parent::__construct($tx_service);

        $this->summit_repository = $summit_repository;
        $this->resource_server_ctx = $resource_server_ctx;
        $this->upload_strategy = $upload_strategy;
        $this->download_strategy = $download_strategy;
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
        return $this->tx_service->transaction(function () use ($summit, $selection_plan_id, $presentation_id) {

            $current_member = $this->resource_server_ctx->getCurrentUser();
            if (is_null($current_member))
                throw new AuthzException("User not Found");

            $selection_plan = $summit->getSelectionPlanById(intval($selection_plan_id));
            if (is_null($selection_plan))
                throw new EntityNotFoundException("Selection Plan not found.");

            if (!$selection_plan->isSelectionOpen())
                throw new ValidationException(sprintf("Selection period is not open for selection plan %s", $selection_plan->getId()));

            $presentation = $selection_plan->getPresentation(intval($presentation_id));

            if (is_null($presentation))
                throw new EntityNotFoundException("Presentation not found.");

            $category = $presentation->getCategory();

            $summit = $presentation->getSummit();

            $isAuth = $summit->isTrackChairAdmin($current_member) || $summit->isTrackChair($current_member, $category);

            if (!$isAuth)
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
        int    $selection_plan_id,
        int    $presentation_id,
        array  $payload
    ): SummitPresentationComment
    {
        return $this->tx_service->transaction(function () use ($summit, $selection_plan_id, $presentation_id, $payload) {

            $current_member = $this->resource_server_ctx->getCurrentUser();

            if (is_null($current_member))
                throw new AuthzException("User not Found");

            $selection_plan = $summit->getSelectionPlanById(intval($selection_plan_id));
            if (is_null($selection_plan))
                throw new EntityNotFoundException("Selection Plan not found.");

            if (!$selection_plan->isSelectionOpen())
                throw new ValidationException(sprintf("Selection period is not open for selection plan %s", $selection_plan->getId()));

            $presentation = $selection_plan->getPresentation(intval($presentation_id));

            if (is_null($presentation))
                throw new EntityNotFoundException("Presentation not found.");

            $category = $presentation->getCategory();

            $summit = $presentation->getSummit();

            $isAuth = $summit->isTrackChairAdmin($current_member) || $summit->isTrackChair($current_member, $category);

            if (!$isAuth)
                throw new ValidationException(sprintf("Presentation %s has changed to track %s", $presentation->getTitle(), $category->getTitle()));

            return $presentation->addTrackChairComment($current_member, trim($payload['body']), boolval($payload['is_public']));
        });
    }


    /**
     * @inheritDoc
     */
    public function createPresentationCategoryChangeRequest(Summit $summit, int $selection_plan_id, int $presentation_id, int $new_category_id): ?SummitCategoryChange
    {
        return $this->tx_service->transaction(function () use ($summit, $selection_plan_id, $presentation_id, $new_category_id) {

            $current_member = $this->resource_server_ctx->getCurrentUser();
            if (is_null($current_member))
                throw new AuthzException("User not Found");

            $selection_plan = $summit->getSelectionPlanById(intval($selection_plan_id));
            if (is_null($selection_plan))
                throw new EntityNotFoundException("Selection Plan not found.");

            if (!$selection_plan->isSelectionOpen())
                throw new ValidationException(sprintf("Selection period is not open for selection plan %s", $selection_plan->getId()));

            $presentation = $selection_plan->getPresentation(intval($presentation_id));

            if (is_null($presentation))
                throw new EntityNotFoundException("Presentation not found.");

            $category = $presentation->getCategory();

            $summit = $presentation->getSummit();

            $new_category = $summit->getPresentationCategory($new_category_id);

            if (is_null($new_category) || !$new_category->isChairVisible())
                throw new EntityNotFoundException("New Category not found.");

            $isAuth = $summit->isTrackChairAdmin($current_member) || $summit->isTrackChair($current_member, $category);

            if (!$isAuth)
                throw new AuthzException(sprintf("User %s is not authorized to perform this action.", $current_member->getId()));

            $change_request = $presentation->addCategoryChangeRequest($current_member, $new_category);

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
        return $this->tx_service->transaction(function () use ($summit, $selection_plan_id, $presentation_id, $category_change_request_id, $payload) {

            $current_member = $this->resource_server_ctx->getCurrentUser();
            if (is_null($current_member))
                throw new AuthzException("User not Found");

            $selection_plan = $summit->getSelectionPlanById(intval($selection_plan_id));
            if (is_null($selection_plan))
                throw new EntityNotFoundException("Selection Plan not found.");

            if (!$selection_plan->isSelectionOpen())
                throw new ValidationException(sprintf("Selection period is not open for selection plan %s", $selection_plan->getId()));

            $presentation = $selection_plan->getPresentation(intval($presentation_id));

            if (is_null($presentation))
                throw new EntityNotFoundException("Presentation not found.");

            $category = $presentation->getCategory();

            $summit = $presentation->getSummit();

            $change_request = $presentation->getCategoryChangeRequest($category_change_request_id);

            if (is_null($change_request))
                throw new EntityNotFoundException("Category Change Request not found.");

            if (!$change_request->isPending()) {
                throw new ValidationException("Change request has already been  approved/rejected.");
            }

            $newCategory = $change_request->getNewCategory();

            $isAuth = $summit->isTrackChairAdmin($current_member) || $summit->isTrackChair($current_member, $newCategory);

            if (!$isAuth)
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

            $approved = (boolean)$payload['approved'];
            $reason = $payload['reason'] ?? 'No reason.';

            if ($approved) {
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

                foreach ($presentation->getPendingCategoryChangeRequests() as $pending_request) {
                    if ($pending_request->getId() == $change_request->getId()) continue;
                    $pending_request->reject($current_member, sprintf("Request ID %s was approved instead.", $change_request->getId()));
                }
            } else {
                $change_request->reject($current_member, $reason);
                $presentation->addTrackChairNotification(
                    $current_member,
                    sprintf(
                        "{member} rejected %s's request to move this presentation from %s to %s because : %s",
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
     * @inheritDoc
     */
    public function attachEventTypeToSelectionPlan(Summit $summit, int $selection_plan_id, int $event_type_id)
    {
        $this->tx_service->transaction(function () use ($summit, $selection_plan_id, $event_type_id) {

            $selection_plan = $summit->getSelectionPlanById($selection_plan_id);
            if (is_null($selection_plan))
                throw new EntityNotFoundException("Selection Plan not found.");

            $event_type = $summit->getEventType($event_type_id);
            if (is_null($event_type))
                throw new EntityNotFoundException("Event Type not found.");

            if (!$event_type instanceof PresentationType) {
                throw new ValidationException(trans(
                        'validation_errors.SummitSelectionPlanService.attachEventTypeToSelectionPlan.invalidPresentationType',
                        ['type_id' => $event_type->getIdentifier()])
                );
            }

            $selection_plan->addEventType($event_type);
        });
    }

    /**
     * @inheritDoc
     */
    public function detachEventTypeFromSelectionPlan(Summit $summit, int $selection_plan_id, int $event_type_id)
    {
        $this->tx_service->transaction(function () use ($summit, $selection_plan_id, $event_type_id) {

            $selection_plan = $summit->getSelectionPlanById($selection_plan_id);
            if (is_null($selection_plan))
                throw new EntityNotFoundException(trans
                ('not_found_errors.SummitSelectionPlanService.detachEventTypeFromSelectionPlan.SelectionPlanNotFound',
                    [
                        'selection_plan_id' => $selection_plan_id,
                        'summit_id' => $summit->getId()
                    ]
                ));

            $event_type = $summit->getEventType($event_type_id);
            if (is_null($event_type))
                throw new EntityNotFoundException(trans
                ('not_found_errors.SummitSelectionPlanService.detachEventTypeFromSelectionPlan.EventTypeNotFound',
                    [
                        '$event_type_id' => $event_type_id,
                        'summit_id' => $summit->getId()
                    ]
                ));
            $selection_plan->removeEventType($event_type);
        });
    }

    /**
     * @inheritDoc
     */
    public function upsertAllowedPresentationActionType(
        Summit $summit, int $selection_plan_id, int $type_id, array $payload): PresentationActionType
    {

        return $this->tx_service->transaction(function () use ($summit, $selection_plan_id, $type_id, $payload) {
            $selection_plan = $summit->getSelectionPlanById($selection_plan_id);
            if (is_null($selection_plan))
                throw new EntityNotFoundException("Selection Plan not found.");

            $presentation_action_type = $summit->getPresentationActionTypeById($type_id);
            if (is_null($presentation_action_type))
                throw new EntityNotFoundException("Presentation Action Type not found.");

            if (!$presentation_action_type instanceof PresentationActionType) {
                throw new ValidationException(trans(
                        'validation_errors.SummitSelectionPlanService.addAllowedPresentationActionType.invalidPresentationActionType',
                        ['type_id' => $presentation_action_type->getIdentifier()])
                );
            }

            $current_order = $selection_plan->getPresentationActionTypeOrder($presentation_action_type);
            $new_order = isset($payload['order']) ? intval($payload['order']) : null;

            if (!$selection_plan->isAllowedPresentationActionType($presentation_action_type)) {
                $selection_plan->addPresentationActionType($presentation_action_type);
            } else if ($new_order == null || $new_order === $current_order) {
                throw new ValidationException("Presentation Action Type is already assigned to this Selection Plan in the order specified.");
            }

            if ($new_order != null && $current_order != $new_order) {
                $selection_plan->recalculatePresentationActionTypeOrder($presentation_action_type, $new_order);
            }

            return $presentation_action_type;
        });
    }

    /**
     * @param Summit $summit
     * @param int $selection_plan_id
     * @param int $type_id
     * @return mixed|void
     * @throws \Exception
     */
    public function removeAllowedPresentationActionType(Summit $summit, int $selection_plan_id, int $type_id)
    {
        return $this->tx_service->transaction(function () use ($summit, $selection_plan_id, $type_id) {
            $selection_plan = $summit->getSelectionPlanById($selection_plan_id);
            if (is_null($selection_plan))
                throw new EntityNotFoundException(trans
                ('not_found_errors.SummitSelectionPlanService.removeAllowedPresentationActionType.SelectionPlanNotFound',
                    [
                        'selection_plan_id' => $selection_plan_id,
                        'summit_id' => $summit->getId()
                    ]
                ));
            $presentation_action_type = $summit->getPresentationActionTypeById($type_id);
            if (is_null($presentation_action_type))
                throw new EntityNotFoundException(trans
                ('not_found_errors.SummitSelectionPlanService.removeAllowedPresentationActionType.PresentationActionTypeNotFound',
                    [
                        '$presentation_action_type' => $presentation_action_type,
                        'summit_id' => $summit->getId()
                    ]
                ));
            $selection_plan->removePresentationActionType($presentation_action_type);
        });
    }

    /**
     * @param Summit $summit
     * @param int $selection_plan_id
     * @param string $email
     * @return SelectionPlanAllowedMember
     * @throws \Exception
     */
    public function addAllowedMember(Summit $summit, int $selection_plan_id, string $email): SelectionPlanAllowedMember
    {
        return $this->tx_service->transaction(function () use ($summit, $selection_plan_id, $email) {

            $selection_plan = $summit->getSelectionPlanById($selection_plan_id);
            if (!$selection_plan instanceof SelectionPlan)
                throw new EntityNotFoundException("Selection Plan not found.");

            if ($selection_plan->containsMember(trim($email)))
                throw new ValidationException("Member is already authorized on Selection Plan.");

            return $selection_plan->addAllowedMember($email);

        });
    }

    /**
     * @param Summit $summit
     * @param int $selection_plan_id
     * @param int $allowed_member_id
     * @throws \Exception
     */
    public function removeAllowedMember(Summit $summit, int $selection_plan_id, int $allowed_member_id): void
    {
        $this->tx_service->transaction(function () use ($summit, $selection_plan_id, $allowed_member_id) {

            $selection_plan = $summit->getSelectionPlanById($selection_plan_id);
            if (!$selection_plan instanceof SelectionPlan)
                throw new EntityNotFoundException("Selection Plan not found.");

           $allowed_member = $selection_plan->getAllowedMemberById($allowed_member_id);

           if(is_null($allowed_member))
                throw new ValidationException("Member is not authorized on Selection Plan.");

            $selection_plan->removeAllowedMember($allowed_member);
        });
    }

    /**
     * @param Summit $summit
     * @param int $selection_plan_id
     * @param UploadedFile $csv_file
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function importAllowedMembers(Summit $summit, int $selection_plan_id, UploadedFile $csv_file): void
    {
        Log::debug(sprintf("SelectionPlanService::importAllowedMembers - summit %s selection plan %s", $summit->getId(), $selection_plan_id));

        $selection_plan = $summit->getSelectionPlanById($selection_plan_id);
        if (!$selection_plan instanceof SelectionPlan)
            throw new EntityNotFoundException("Selection Plan not found.");

        $allowed_extensions = ['txt'];

        if (!in_array($csv_file->extension(), $allowed_extensions)) {
            throw new ValidationException("file does not has a valid extension ('csv').");
        }

        $real_path = $csv_file->getRealPath();
        $filename = pathinfo($real_path);
        $filename = $filename['filename'] ?? sprintf("file%s", time());
        $basename = sprintf("%s_%s.csv", $filename, time());
        $path = "tmp/selection_plans_allowed_members";
        $csv_data = File::get($real_path);
        if (empty($csv_data))
            throw new ValidationException("file content is empty!");

        $reader = CSVReader::buildFrom($csv_data);

        // check needed columns (headers names)
        /*
            columns
            * email ( mandatory)
         */

        // validate format with col names

        if (!$reader->hasColumn("email"))
            throw new ValidationException
            (
                "Email column is missing."
            );

        $this->upload_strategy->save($csv_file, $path, $basename);

        ProcessSelectionPlanAllowedMemberData::dispatch($summit->getId(), $selection_plan_id, $basename);
    }

    /**
     * @param int $summit_id
     * @param int $selection_plan_id
     * @param string $filename
     * @throws EntityNotFoundException
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function processAllowedMemberData(int $summit_id, int $selection_plan_id, string $filename): void
    {
        $path = sprintf("tmp/selection_plans_allowed_members/%s", $filename);

        Log::debug(sprintf("SelectionPlanService::processAllowedMemberData summit %s selection_plan_id %s filename %s", $summit_id, $selection_plan_id, $filename));

        if (!$this->download_strategy->exists($path)) {
            Log::warning
            (
                sprintf
                (
                    "SelectionPlanService::processAllowedMemberData file %s does not exist on storage %s",
                    $path,
                    $this->download_strategy->getDriver()
                )
            );

            throw new ValidationException(sprintf("file %s does not exists.", $filename));
        }

        $csv_data = $this->download_strategy->get($path);

        $selection_plan = $this->tx_service->transaction(function () use ($summit_id, $selection_plan_id) {
            $summit = $this->summit_repository->getById($summit_id);
            if (!$summit instanceof Summit)
                throw new EntityNotFoundException(sprintf("summit %s does not exists.", $summit_id));

            $selection_plan = $summit->getSelectionPlanById($selection_plan_id);
            if (!$selection_plan instanceof SelectionPlan)
                throw new EntityNotFoundException("Selection Plan not found.");

            return $selection_plan;
        });


        $reader = CSVReader::buildFrom($csv_data);

        foreach ($reader as $idx => $row) {

            $this->tx_service->transaction(function () use ($selection_plan, $reader, $row) {

                Log::debug(sprintf("SelectionPlanService::processAllowedMemberData processing row %s", json_encode($row)));

                $selection_plan->addAllowedMember($row['email']);
            });
        }

        Log::debug(sprintf("SelectionPlanService::processAllowedMemberData deleting file %s from storage %s", $path, $this->download_strategy->getDriver()));
        $this->download_strategy->delete($path);
    }
}