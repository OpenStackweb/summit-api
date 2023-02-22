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

use App\Jobs\Emails\PresentationSubmissions\SelectionProcess\PresentationSpeakerSelectionProcessExcerptEmail;
use App\Jobs\Emails\ProcessSpeakersEmailRequestJob;
use App\Services\Model\AbstractService;
use App\Services\Model\Strategies\EmailActions\SpeakerActionsEmailStrategy;
use App\Services\Utils\Facades\EmailExcerpt;
use App\Services\Utils\Facades\EmailTest;
use App\Services\Utils\Facades\SpeakersAnnouncementEmailConfig;
use Illuminate\Support\Facades\Log;
use libs\utils\ITransactionService;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use models\main\IMemberRepository;
use models\main\Member;
use models\summit\ISummitRepository;
use models\summit\Summit;
use utils\Filter;
use utils\PagingInfo;

/**
 * Class SubmitterService
 * @package services\model
 */
final class SubmitterService
    extends AbstractService
    implements ISubmitterService
{
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
        ProcessSpeakersEmailRequestJob::dispatch($summit->getId(), $payload, $filter);
    }

    /**
     * @inheritDoc
     */
    public function sendEmails(int $summit_id, array $payload, Filter $filter = null): void
    {
        Log::debug
        (
            sprintf
            (
                "SubmitterService::send summit %s payload %s filter %s INIT",
                $summit_id,
                json_encode($payload),
                is_null($filter) ? "" : $filter->__toString()
            )
        );

//        EmailTest::clearEmailAddress();
//        EmailExcerpt::clearReport();
//        SpeakersAnnouncementEmailConfig::reset();

        $flow_event = trim($payload['email_flow_event'] ?? '');

        if(empty($flow_event))
            throw new ValidationException("email_flow_event is required.");

        $done = isset($payload['speaker_ids']); // we have provided only ids and not a criteria
        $outcome_email_recipient = $payload['outcome_email_recipient'] ?? null;
        if(isset($payload['test_email_recipient']))
            EmailTest::setEmailAddress($payload['test_email_recipient']);

        if(isset($payload['should_resend'])){
            SpeakersAnnouncementEmailConfig::setShouldResend(boolval($payload['should_resend']));
        }

        $page = 1;
        $count = 0;
        $maxPageSize = 100;

        Log::debug(sprintf("SubmitterService::send summit id %s flow_event %s filter %s",
            $summit_id, $flow_event, is_null($filter) ? '' : $filter->__toString()));

        EmailExcerpt::addInfoMessage(
            sprintf("Processing EMAIL %s for summit %s", $flow_event, $summit_id)
        );

        $summit = $this->tx_service->transaction(function () use($summit_id){
            $summit = $this->summit_repository->getById($summit_id);
            if (is_null($summit) || !$summit instanceof Summit) return null;
            return $summit;
        });

        if(is_null($summit)){
            Log::debug("SubmitterService::send summit is null");
            return;
        }

        do {

            $ids = $this->tx_service->transaction(function () use ($summit, $payload, $filter, $page, $maxPageSize) {
                if (isset($payload['submitter_ids'])) {
                    Log::debug(sprintf("SubmitterService::send summit id %s speakers_ids %s", $summit->getId(),
                        json_encode($payload['submitter_ids'])));
                    return $payload['submitter_ids'];
                }
                Log::debug(sprintf("SubmitterService::send summit id %s getting by filter", $summit->getId()));
                if (is_null($filter)) {
                    $filter = new Filter();
                }

                Log::debug(sprintf("SubmitterService::send page %s", $page));
                return $this->member_repository->getSubmittersIdsBySummit($summit, new PagingInfo($page, $maxPageSize), $filter);
            });

            Log::debug(sprintf("SubmitterService::send summit id %s flow_event %s filter %s page %s got %s records",
                $summit_id, $flow_event, is_null($filter) ? '' : $filter->__toString(), $page, count($ids)));

            if (!count($ids)) {
                // if we are processing a page, then break it
                Log::debug(sprintf("SubmitterService::send summit id %s page is empty, ending processing.", $summit_id));
                break;
            }

            $email_strategy = new SpeakerActionsEmailStrategy($summit, $flow_event);

            foreach ($ids as $submitter_id) {

                try {
                    $this->tx_service->transaction(function () use
                    (
                        $flow_event,
                        $summit,
                        $submitter_id,
                        $email_strategy,
                        $filter
                    ) {

                        Log::debug(sprintf("SubmitterService::send processing submitter id %s", $submitter_id));

                        $submitter = $this->member_repository->getByIdExclusiveLock(intval($submitter_id));

                        if (is_null($submitter) || !$submitter instanceof Member) {
                            throw new EntityNotFoundException('submitter not found!');
                        }

//                        // try to get a promo code
//                        $promo_code = $this->getPromoCode($summit, $speaker, $filter);
//
//                        // try to get an speaker assistance
//                        $assistance = $this->generateSpeakerAssistance($summit, $speaker, $filter);
//
//                        $email_strategy->process
//                        (
//                            $speaker,
//                            $filter,
//                            $promo_code,
//                            $assistance
//                        );
                    });
                } catch (\Exception $ex) {
                    Log::warning($ex);
                    EmailExcerpt::addErrorMessage($ex->getMessage());
                }
                $count++;
            }
            $page++;
        } while (!$done);

        EmailExcerpt::addInfoMessage(sprintf("TOTAL of %s submitter(s) processed.", $count));
        EmailExcerpt::generateEmailCountLine();

        if (!empty($outcome_email_recipient))
            PresentationSpeakerSelectionProcessExcerptEmail::dispatch($summit, $outcome_email_recipient);

        Log::debug(sprintf("SubmitterService::send summit id %s flow_event %s filter %s had processed %s records",
            $summit_id, $flow_event, is_null($filter) ? '' : $filter->__toString(), $count));
    }
}