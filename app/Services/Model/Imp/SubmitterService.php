<?php namespace services\model;
/**
 * Copyright 2023 OpenStack Foundation
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

use App\Jobs\Emails\PresentationSubmissions\SelectionProcess\PresentationSubmitterSelectionProcessExcerptEmail;
use App\Jobs\Emails\ProcessSubmittersEmailRequestJob;
use App\Services\Model\AbstractService;
use App\Services\Model\Imp\Traits\ParametrizedSendEmails;
use App\Services\Model\Strategies\EmailActions\SubmitterActionsEmailStrategy;
use Exception;
use Illuminate\Support\Facades\Log;
use libs\utils\ITransactionService;
use models\exceptions\EntityNotFoundException;
use models\main\IMemberRepository;
use models\main\Member;
use models\summit\ISummitRepository;
use models\summit\Summit;
use utils\Filter;
use utils\FilterParser;

/**
 * Class SubmitterService
 * @package services\model
 */
final class SubmitterService
    extends AbstractService
    implements ISubmitterService
{
    use ParametrizedSendEmails;

    /**
     * @var IMemberRepository
     */
    private $member_repository;

    /**
     * @var ISummitRepository
     */
    private $summit_repository;

    /**
     * SubmitterService constructor.
     * @param IMemberRepository $member_repository
     * @param ISummitRepository $summit_repository
     * @param ITransactionService $tx_service
     */
    public function __construct
    (
        IMemberRepository   $member_repository,
        ISummitRepository   $summit_repository,
        ITransactionService $tx_service
    )
    {
        parent::__construct($tx_service);
        $this->member_repository = $member_repository;
        $this->summit_repository = $summit_repository;
    }

    /**
     * @inheritDoc
     */
    public function triggerSendEmails(Summit $summit, array $payload, $filter = null): void
    {
        ProcessSubmittersEmailRequestJob::dispatch($summit->getId(), $payload, $filter);
    }

    /**
     * @inheritDoc
     */
    public function sendEmails(int $summit_id, array $payload, Filter $filter = null): void
    {
        $this->_sendEmails(
            $summit_id,
            $payload,
            "submitter",
            function ($summit, $paging_info, $filter) {
                return $this->member_repository->getSubmittersIdsBySummit($summit, $paging_info, $filter);
            },
            function
            (
                $summit,
                $flow_event,
                $submitter_id,
                $test_email_recipient,
                $email_config,
                $filter,
                $onDispatchSuccess,
                $onDispatchError,
                $onDispatchInfo
            ) {
                try {
                    $this->tx_service->transaction(function () use (
                        $flow_event,
                        $summit,
                        $submitter_id,
                        $filter,
                        $test_email_recipient,
                        $onDispatchSuccess,
                        $onDispatchError,
                        $onDispatchInfo
                    ) {
                        $email_strategy = new SubmitterActionsEmailStrategy($summit, $flow_event);

                        Log::debug("SubmitterService::send processing submitter id {$submitter_id}");

                        $submitter = $this->member_repository->getByIdExclusiveLock(intval($submitter_id));

                        if (!$submitter instanceof Member) {
                            throw new EntityNotFoundException('Submitter not found!');
                        }

                        $original_filter = $payload["original_filter"] ?? null;
                        if(!is_null($original_filter) && is_array($original_filter) && count($original_filter) > 0){
                            // in case that we are sending the original filter on the payload
                            try {

                                Log::debug
                                (
                                    sprintf
                                    (
                                        "SubmitterService::send replacing current filter by original filter %s",
                                        json_encode($original_filter)
                                    )
                                );

                                $original_filter = FilterParser::parse($original_filter, [
                                    'id' => ['=='],
                                    'not_id' => ['=='],
                                    'first_name' => ['=@', '@@', '=='],
                                    'last_name' => ['=@', '@@', '=='],
                                    'email' => ['=@', '@@', '=='],
                                    'full_name' => ['=@', '@@', '=='],
                                    'member_id' => ['=='],
                                    'member_user_external_id' => ['=='],
                                    'has_accepted_presentations' => ['=='],
                                    'has_alternate_presentations' => ['=='],
                                    'has_rejected_presentations' => ['=='],
                                    'presentations_track_id' => ['=='],
                                    'presentations_selection_plan_id' => ['=='],
                                    'presentations_type_id' => ['=='],
                                    'presentations_title' => ['=@', '@@', '=='],
                                    'presentations_abstract' => ['=@', '@@', '=='],
                                    'presentations_submitter_full_name' => ['=@', '@@', '=='],
                                    'presentations_submitter_email' => ['=@', '@@', '=='],
                                    'is_speaker' => ['=='],
                                ]) ;
                            }
                            catch (\Exception $ex){
                                Log::warning($ex);
                                $original_filter = null;
                            }
                        }

                        $email_strategy->process
                        (
                            $submitter,
                            $test_email_recipient,
                            !is_null($original_filter) ? $original_filter : $filter,
                            $onDispatchSuccess,
                            $onDispatchInfo,
                            $onDispatchError
                        );
                    });
                } catch (Exception $ex) {
                    Log::warning($ex);
                    if(!is_null($onDispatchError))
                        $onDispatchError($ex->getMessage());
                }
            },
            function ($summit, $outcome_email_recipient, $report) {
                PresentationSubmitterSelectionProcessExcerptEmail::dispatch($summit, $outcome_email_recipient, $report);
            },
            $filter);
    }
}