<?php namespace App\Services\Model\Imp\Traits;
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
use App\Services\Utils\Email\SpeakersAnnouncementEmailConfigDTO;
use App\Services\Utils\Facades\EmailExcerpt;
use Illuminate\Support\Facades\Log;
use models\exceptions\ValidationException;
use models\summit\Summit;
use ReflectionClass;
use utils\Filter;
use utils\PagingInfo;
use Exception;

const MaxPageSize = 100;

/**
 * Trait ParametrizedSendEmails
 * @package App\Services\Model\Imp\Traits
 */
trait ParametrizedSendEmails
{

    /**
     * @param int $summit_id
     * @param array $payload
     * @param string $subject
     * @param callable $getIdsBySummit
     * @param callable $processCurrentId
     * @param callable|null $sendEmailExcerpt
     * @param Filter|null $filter
     * @throws ValidationException
     */
    private function _sendEmails(
        int $summit_id,
        array $payload,
        string $subject,
        callable $getIdsBySummit,
        callable $processCurrentId,
        callable $sendEmailExcerpt = null,
        Filter $filter = null
    ): void
    {
        $caller = (new ReflectionClass($this))->getShortName();
        $subject_ids_key = $subject . '_ids';   //We assume that the payload key for the ids array starts with the prefix that contains $subject
        $exclude_subject_ids_key = 'excluded_' . $subject_ids_key;

        Log::debug
        (
            sprintf
            (
                "%s::send summit %s payload %s filter %s INIT",
                $caller,
                $summit_id,
                json_encode($payload),
                is_null($filter) ? "" : $filter->__toString()
            )
        );

        EmailExcerpt::clearReport();

        $email_config = new SpeakersAnnouncementEmailConfigDTO();

        $flow_event = trim($payload['email_flow_event'] ?? '');

        if(empty($flow_event))
            throw new ValidationException("email_flow_event is required.");

        $done = isset($payload[$subject_ids_key]); // we have provided only ids and not a criteria
        $outcome_email_recipient = $payload['outcome_email_recipient'] ?? null;

        $test_email_recipient = null;
        if(isset($payload['test_email_recipient']))
            $test_email_recipient = $payload['test_email_recipient'];

        if(isset($payload['should_resend'])){
            $email_config->setShouldResend(boolval($payload['should_resend']));
        }
        if(isset($payload['should_send_copy_2_submitter'])){
            $email_config->setShouldSendCopy2Submitter(boolval($payload['should_send_copy_2_submitter']));
        }

        $page = 1;
        $count = 0;

        Log::debug
        (
            sprintf
            (
                "%s::send summit id %s flow_event %s filter %s",
            $caller,
                $summit_id,
                $flow_event,
                is_null($filter) ? '' : $filter->__toString()
            )
        );

        EmailExcerpt::addInfoMessage
        (
            sprintf("Processing EMAIL %s for summit %s", $flow_event, $summit_id)
        );

        $summit = $this->tx_service->transaction(function () use($summit_id){
            $summit = $this->summit_repository->getById($summit_id);
            if (!$summit instanceof Summit) return null;
            return $summit;
        });

        if(is_null($summit)){
            Log::debug(sprintf("%s::send summit is null", $caller));
            return;
        }

        do {
            $ids = $this->tx_service->transaction(function () use ($summit,
                $caller,
                $payload,
                $subject_ids_key,
                $filter,
                &$page,
                $getIdsBySummit,
                $processCurrentId
            ) {
                if (isset($payload[$subject_ids_key])) {
                    $res = $payload[$subject_ids_key];
                    Log::debug(sprintf("%s::send summit id %s %s %s",
                        $caller,
                        $summit->getId(),
                        $subject_ids_key,
                        json_encode($res)));
                    return $res;
                }

                Log::debug(sprintf("%s::send summit id %s getting by filter", $caller, $summit->getId()));
                if (is_null($filter)) {
                    $filter = new Filter();
                }

                Log::debug(sprintf("%s::send page %s", $caller, $page));

                return $getIdsBySummit($summit, new PagingInfo($page, MaxPageSize), $filter, function() use($caller, &$page){
                    $page = 0;
                    Log::debug(sprintf("%s::send page has been reset to zero...", $caller));
                });

            });

            Log::debug
            (
                sprintf
                (
                    "%s::send summit id %s flow_event %s filter %s page %s got %s records",
                $caller,
                    $summit_id,
                    $flow_event,
                    is_null($filter) ? '' : $filter->__toString(),
                    $page,
                    count($ids)
                )
            );

            if (!count($ids)) {
                // if we are processing a page, then break it
                Log::debug(sprintf("%s::send summit id %s page is empty, ending processing.", $caller, $summit_id));
                break;
            }
            // explicit exclude ids
            $exclude_ids = [];
            if (isset($payload[$exclude_subject_ids_key])) {
                $exclude_ids = $payload[$exclude_subject_ids_key];
                Log::debug
                (
                    sprintf
                    (
                        "%s::send summit id %s excluded ids %s",
                        $caller,
                        $summit->getId(),
                        json_encode($exclude_ids)
                    )
                );
            }

            foreach ($ids as $subject_id) {
                try {
                    if (in_array($subject_id, $exclude_ids)) {
                        Log::debug(sprintf("%s::send summit id %s %s id %s is excluded",
                            $caller,
                            $summit->getId(),
                            $subject,
                            $subject_id));
                        continue;
                    };

                    $processCurrentId
                    (
                        $summit,
                        $flow_event,
                        $subject_id,
                        $test_email_recipient,
                        $email_config,
                        $filter,
                        function($recipient_email, $type, $flow_event) {
                            EmailExcerpt::add(
                                [
                                    'type'          => $type,
                                    'subject_email' => $recipient_email,
                                    'email_type'    => $flow_event,
                                ]
                            );
                            EmailExcerpt::addEmailSent();
                        },
                        function ($error){
                            EmailExcerpt::addErrorMessage($error);
                        },
                        function($info) {
                            EmailExcerpt::addInfoMessage($info);
                        }
                    );
                    $count++;
                } catch (Exception $ex) {
                    Log::warning($ex);
                    EmailExcerpt::addErrorMessage($ex->getMessage());
                }
            }
            $page++;
        } while (!$done);

        EmailExcerpt::addInfoMessage
        (
            sprintf
            (
                "TOTAL of %s %s(s) processed.", $count, $subject
            )
        );

        EmailExcerpt::generateEmailCountLine();

        if (!empty($outcome_email_recipient) && !is_null($sendEmailExcerpt) && is_callable($sendEmailExcerpt)) {
            $sendEmailExcerpt($summit, $outcome_email_recipient, EmailExcerpt::getReport());
        }

        Log::debug
        (
            sprintf
            (
                "%s::send summit id %s flow_event %s filter %s had processed %s records",
            $caller,
                $summit_id,
                $flow_event,
                is_null($filter) ? '' : $filter->__toString(),
                $count
            )
        );
    }
}